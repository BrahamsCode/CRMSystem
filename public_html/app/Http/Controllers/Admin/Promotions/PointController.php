<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Enums\PointExtendMode;
use App\Enums\PointMovement;
use App\Enums\SignupPointTiming;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\CustomerGroupPointRule;
use App\Models\CustomerPoint;
use App\Models\PointSetting;
use App\Services\Promotions\LoyaltyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

/** Puntos (ポイント仕様管理) */
class PointController extends ModuleController
{
    public function edit(): View
    {
        $shop = $this->shop();
        $groups = CustomerGroup::active()->where('shop_id', $shop?->id)->ordered()->get();

        return view('admin.promotions.points', [
            'shop' => $shop,
            'setting' => $shop ? PointSetting::forShop($shop) : null,
            'groups' => $groups,
            'groupRules' => CustomerGroupPointRule::whereIn('customer_group_id', $groups->pluck('id'))->get()->keyBy('customer_group_id'),
            'extendModes' => PointExtendMode::cases(),
            'timings' => SignupPointTiming::cases(),
            'recent' => CustomerPoint::with('customer')->where('shop_id', $shop?->id)->latest()->take(10)->get(),
            'totals' => [
                'customers' => Customer::where('point_balance', '>', 0)->count(),
                'points' => (int) Customer::sum('point_balance'),
                'month' => (int) CustomerPoint::where('shop_id', $shop?->id)->where('points', '>', 0)->where('created_at', '>=', now()->startOfMonth())->sum('points'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'expiry_flg' => ['nullable', 'in:0,1'],
            'extend_mode' => ['required', new Enum(PointExtendMode::class)],
            'expiry_days' => ['nullable', 'required_if:expiry_flg,1', 'integer', 'between:0,999'],
            'expiry_hour' => ['required', 'integer', 'between:0,23'],
            'expiry_minute' => ['required', 'in:0,30'],
            'notices' => ['nullable', 'array', 'max:5'],
            'notices.*.days' => ['nullable', 'integer', 'between:1,365'],
            'notices.*.hour' => ['nullable', 'integer', 'between:0,23'],
            'notices.*.minute' => ['nullable', 'in:0,30'],
            'use_limit' => ['nullable', 'integer', 'min:1'],
            'cash_rate' => ['required', 'numeric', 'between:0,100'],
            'card_rate' => ['required', 'numeric', 'between:0,100'],
            'include_used_flg' => ['nullable', 'in:0,1'],
            'referral_points' => ['required', 'integer', 'min:0'],
            'referral_limit' => ['nullable', 'integer', 'min:1'],
            'referee_points' => ['required', 'integer', 'min:0'],
            'referral_comment' => ['nullable', 'string', 'max:255'],
            'signup_points' => ['required', 'integer', 'min:0'],
            'signup_timing' => ['required', new Enum(SignupPointTiming::class)],
            'signup_comment' => ['nullable', 'string', 'max:255'],
            'groups' => ['nullable', 'array'],
            'groups.*.use_flg' => ['nullable', 'in:0,1'],
            'groups.*.display_flg' => ['nullable', 'in:0,1'],
            'groups.*.rate' => ['nullable', 'numeric', 'between:0,100'],
        ], [], ['expiry_days' => 'días de validez', 'cash_rate' => '% en efectivo', 'card_rate' => '% con tarjeta']);

        $setting = PointSetting::forShop($this->shop());
        $setting->fill([
            'expiry_flg' => (int) ($data['expiry_flg'] ?? 0),
            'include_used_flg' => (int) ($data['include_used_flg'] ?? 0),
            // Solo los avisos completos
            'notices' => collect($data['notices'] ?? [])->filter(fn ($n) => filled($n['days'] ?? null))
                ->map(fn ($n) => ['days' => (int) $n['days'], 'hour' => (int) ($n['hour'] ?? 10), 'minute' => (int) ($n['minute'] ?? 0)])
                ->values()->all(),
        ] + collect($data)->except(['expiry_flg', 'include_used_flg', 'notices', 'groups'])->all())->save();

        $groupIds = CustomerGroup::where('shop_id', $this->shop()?->id)->pluck('id');
        foreach ($data['groups'] ?? [] as $groupId => $rule) {
            if (! $groupIds->contains((int) $groupId)) {
                continue;
            }
            CustomerGroupPointRule::updateOrCreate(['customer_group_id' => $groupId], [
                'use_flg' => (int) ($rule['use_flg'] ?? 0),
                'display_flg' => (int) ($rule['display_flg'] ?? 0),
                'rate' => filled($rule['rate'] ?? null) ? $rule['rate'] : null,
            ]);
        }

        return back()->with('status', 'Reglas de puntos guardadas.');
    }

    public function adjust(Request $request, LoyaltyService $loyalty): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
            'points' => ['required', 'integer', 'not_in:0', 'between:-1000000,1000000'],
            'type' => ['required', 'in:' . PointMovement::Adjustment->value . ',' . PointMovement::Use->value],
            'note' => ['nullable', 'string', 'max:255'],
        ], [], ['code' => 'nº de socio', 'points' => 'puntos']);

        $customer = Customer::where('code', $data['code'])->first();
        if (! $customer) {
            return back()->withErrors(['code' => 'No hay ningún cliente con ese número de socio.'])->withInput();
        }

        $type = PointMovement::from((int) $data['type']);
        $points = $type === PointMovement::Use ? -abs((int) $data['points']) : (int) $data['points'];
        $setting = PointSetting::where('shop_id', $this->shop()?->id)->first();

        if ($points < 0 && $customer->point_balance + $points < 0) {
            return back()->withErrors(['points' => "El cliente solo tiene {$customer->point_balance} puntos."])->withInput();
        }
        if ($type === PointMovement::Use && $setting?->use_limit && abs($points) > $setting->use_limit) {
            return back()->withErrors(['points' => "El máximo por uso es {$setting->use_limit} puntos."])->withInput();
        }

        $loyalty->addPoints($customer, $this->shop(), $points, $type, $data['note'] ?? null);

        return back()->with('status', "Puntos de {$customer->greetingName()}: {$customer->fresh()->point_balance}.");
    }
}
