<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Enums\CouponUsage;
use App\Enums\StampDisplayMode;
use App\Enums\StampMovement;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\CustomerStamp;
use App\Models\StampRule;
use App\Models\StampSetting;
use App\Services\Promotions\LoyaltyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

/** Tarjeta de sellos (スタンプデザイン・仕様・発行ルール・クーポンお知らせ) */
class StampController extends ModuleController
{
    public const ICONS = ['star', 'heart', 'gift', 'coin', 'award', 'check'];

    public function edit(): View
    {
        $shop = $this->shop();

        return view('admin.promotions.stamps', [
            'shop' => $shop,
            'setting' => $shop ? StampSetting::forShop($shop) : null,
            'rules' => $shop ? $shop->stampRules()->with('coupon')->get() : collect(),
            'coupons' => Coupon::active()->where('usage_type', CouponUsage::StampExchange)->forShop($shop?->id)->get(),
            'allCoupons' => Coupon::active()->forShop($shop?->id)->orderBy('name')->get(['id', 'name']),
            'icons' => self::ICONS,
            'modes' => StampDisplayMode::cases(),
            'recent' => CustomerStamp::with('customer')->where('shop_id', $shop?->id)->latest()->take(10)->get(),
            'totals' => [
                'customers' => Customer::where('stamp_balance', '>', 0)->count(),
                'stamps' => (int) Customer::sum('stamp_balance'),
                'month' => (int) CustomerStamp::where('shop_id', $shop?->id)->where('quantity', '>', 0)->where('created_at', '>=', now()->startOfMonth())->sum('quantity'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'card_size' => ['nullable', 'integer', 'in:0,5,10,20,30,40,50,60,75,100,200,300,400,500'],
            'signup_bonus' => ['required', 'integer', 'between:0,10'],
            'visit_stamp_flg' => ['nullable', 'in:0,1'],
            'interval_seconds' => ['required', 'integer', 'min:0', 'max:86400'],
            'display_mode' => ['required', new Enum(StampDisplayMode::class)],
            'icon' => ['required', Rule::in(self::ICONS)],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'expiry_flg' => ['nullable', 'in:0,1'],
            'expiry_days' => ['nullable', 'required_if:expiry_flg,1', 'integer', 'between:1,999'],
            'expiry_hour' => ['required', 'integer', 'between:0,23'],
            'expiry_minute' => ['required', 'in:0,30'],
            'notice_flg' => ['nullable', 'in:0,1'],
            'notice_days' => ['nullable', 'required_if:notice_flg,1', 'integer', 'between:1,365'],
            'notice_hour' => ['required', 'integer', 'between:0,23'],
            'notice_minute' => ['required', 'in:0,30'],
        ], [], ['expiry_days' => 'días de validez', 'notice_days' => 'días de aviso']);

        $setting = StampSetting::forShop($this->shop());
        $setting->fill([
            'card_size' => ($data['card_size'] ?? null) ?: null,
            'signup_bonus' => $data['signup_bonus'],
            'visit_stamp_flg' => (int) ($data['visit_stamp_flg'] ?? 0),
            'interval_seconds' => $data['interval_seconds'],
            'display_mode' => $data['display_mode'],
            'design' => ['icon' => $data['icon'], 'color' => $data['color']],
            'expiry_flg' => (int) ($data['expiry_flg'] ?? 0),
            'expiry_days' => $data['expiry_days'] ?? null,
            'expiry_hour' => $data['expiry_hour'],
            'expiry_minute' => $data['expiry_minute'],
            'notice_flg' => (int) ($data['notice_flg'] ?? 0),
            'notice_days' => $data['notice_days'] ?? null,
            'notice_hour' => $data['notice_hour'],
            'notice_minute' => $data['notice_minute'],
        ])->save();

        return back()->with('status', 'Tarjeta de sellos guardada.');
    }

    public function storeRule(Request $request): RedirectResponse
    {
        $shop = $this->shop();
        $data = $request->validate([
            'stamp_count' => ['required', 'integer', 'min:1', 'max:500',
                Rule::unique('stamp_rules')->where('shop_id', $shop?->id)->whereNull('deleted_at')],
            'coupon_id' => ['nullable', 'exists:coupons,id'],
            'notify_flg' => ['nullable', 'in:0,1'],
            'reset_flg' => ['nullable', 'in:0,1'],
            'message' => ['nullable', 'string', 'max:2000'],
        ], ['stamp_count.unique' => 'Ya hay una regla para ese número de sellos.'], ['stamp_count' => 'nº de sellos']);

        StampRule::create([
            'shop_id' => $shop->id,
            'notify_flg' => (int) ($data['notify_flg'] ?? 0),
            'reset_flg' => (int) ($data['reset_flg'] ?? 0),
        ] + $data);

        return back()->with('status', "Regla para {$data['stamp_count']} sellos añadida.");
    }

    public function destroyRule(StampRule $rule): RedirectResponse
    {
        $rule->forceDelete();

        return back()->with('status', 'Regla eliminada.');
    }

    /** Ajuste manual: sumar o quitar sellos a un cliente */
    public function adjust(Request $request, LoyaltyService $loyalty): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
            'quantity' => ['required', 'integer', 'not_in:0', 'between:-500,500'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [], ['code' => 'nº de socio', 'quantity' => 'sellos']);

        $customer = Customer::where('code', $data['code'])->first();
        if (! $customer) {
            return back()->withErrors(['code' => 'No hay ningún cliente con ese número de socio.'])->withInput();
        }
        if ($data['quantity'] < 0 && $customer->stamp_balance + $data['quantity'] < 0) {
            return back()->withErrors(['quantity' => "El cliente solo tiene {$customer->stamp_balance} sellos."])->withInput();
        }

        $loyalty->addStamps($customer, $this->shop(), (int) $data['quantity'], StampMovement::Adjustment, $data['note'] ?? null);

        return back()->with('status', "Sellos de {$customer->greetingName()}: {$customer->fresh()->stamp_balance}.");
    }
}
