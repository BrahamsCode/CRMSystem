<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Enums\CouponDiscountType;
use App\Enums\CouponUsage;
use App\Enums\CouponValidity;
use App\Enums\LotteryRank;
use App\Models\Coupon;
use App\Models\CouponCustomer;
use App\Models\Customer;
use App\Services\Promotions\CouponIssuer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;
use RuntimeException;

/** Cupones (マイクーポン): alta, entregas, uso y estadísticas */
class CouponController extends ModuleController
{
    public function index(Request $request): View
    {
        $usage = CouponUsage::tryFrom($request->integer('usage'));
        $shopId = $request->integer('shop') ?: null;

        return view('admin.promotions.coupons.index', [
            'coupons' => Coupon::with('shop')
                ->withCount(['issued', 'issued as used_count' => fn ($q) => $q->whereNotNull('used_at')])
                ->when($usage, fn ($q) => $q->where('usage_type', $usage))
                ->when($shopId, fn ($q) => $q->forShop($shopId))
                ->orderByDesc('id')
                ->paginate(20)->withQueryString(),
            'usage' => $usage,
            'shopId' => $shopId,
            'shops' => $this->shops(),
        ]);
    }

    public function create(): View
    {
        return view('admin.promotions.coupons.form', $this->formData(new Coupon([
            'type' => CouponDiscountType::Percentage,
            'usage_type' => CouponUsage::Newsletter,
            'validity_type' => CouponValidity::OneMonth,
            'display_flg' => 1,
            'reissue_flg' => 0,
            'cost' => 0,
            'notes' => [],
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $coupon = Coupon::create($this->validated($request));

        return redirect()->route('admin.promotions.coupons.show', $coupon)->with('status', "Cupón «{$coupon->name}» creado.");
    }

    public function show(Coupon $coupon, Request $request): View
    {
        $issued = $coupon->issued()->with(['customer', 'usedShop']);

        match ($request->input('state')) {
            'available' => $issued->usable(),
            'used' => $issued->whereNotNull('used_at'),
            'expired' => $issued->whereNull('used_at')->where('expires_at', '<=', now()),
            default => null,
        };

        return view('admin.promotions.coupons.show', [
            'coupon' => $coupon->load('shop'),
            'issued' => $issued->latest('issued_at')->paginate(15)->withQueryString(),
            'state' => $request->input('state'),
            'totals' => [
                'issued' => $coupon->issued()->count(),
                'used' => $coupon->issued()->whereNotNull('used_at')->count(),
                'available' => $coupon->issued()->usable()->count(),
                'expired' => $coupon->issued()->whereNull('used_at')->where('expires_at', '<=', now())->count(),
            ],
            'shops' => $this->shops(),
        ]);
    }

    public function edit(Coupon $coupon): View
    {
        return view('admin.promotions.coupons.form', $this->formData($coupon));
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $coupon->update($this->validated($request));

        return redirect()->route('admin.promotions.coupons.show', $coupon)->with('status', 'Cupón guardado.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();

        return redirect()->route('admin.promotions.coupons.index')->with('status', "Cupón «{$coupon->name}» eliminado. Los ya entregados siguen siendo válidos.");
    }

    /** Entregar a un cliente por su Nº de socio; si el cupón es de canje, descuenta sellos o puntos */
    public function issue(Request $request, Coupon $coupon, CouponIssuer $issuer): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string']], [], ['code' => 'nº de socio']);
        $customer = Customer::where('code', $data['code'])->first();

        if (! $customer) {
            return back()->withErrors(['code' => 'No hay ningún cliente con ese número de socio.'])->withInput();
        }

        try {
            $issued = $coupon->usage_type->hasCost()
                ? $issuer->exchange($coupon, $customer, $customer->shop ?? $this->shop())
                : ($issuer->issue($coupon, $customer) ?? throw new RuntimeException('El cliente ya tiene este cupón o el cupón no está disponible.'));
        } catch (RuntimeException $e) {
            return back()->withErrors(['code' => $e->getMessage()])->withInput();
        }

        return back()->with('status', "Cupón entregado a {$customer->greetingName()}" . ($issued->expires_at ? ', vence el ' . $issued->expires_at->format('d/m/Y') : '') . '.');
    }

    /** Uso en caja */
    public function use(Request $request, CouponCustomer $issued, CouponIssuer $issuer): RedirectResponse
    {
        $shopId = $request->integer('shop_id') ?: $this->shop()?->id;

        try {
            $issuer->markUsed($issued, $this->shops()->firstWhere('id', $shopId) ?? $this->shop());
        } catch (RuntimeException $e) {
            return back()->with('status', $e->getMessage());
        }

        return back()->with('status', "Cupón de {$issued->customer->greetingName()} marcado como usado.");
    }

    /** Ranking de uso y estadísticas por periodo (使用数ランキング / 統計) */
    public function stats(Request $request): View
    {
        $period = $request->input('period', 'month');
        [$from, $to] = match ($period) {
            'today' => [today(), now()],
            'week' => [now()->subWeek(), now()],
            'year' => [now()->subYear(), now()],
            'custom' => [$request->date('from') ?? now()->subMonth(), ($request->date('to') ?? now())->endOfDay()],
            default => [now()->subMonth(), now()],
        };
        $usage = CouponUsage::tryFrom($request->integer('usage'));
        $shopId = $request->integer('shop') ?: null;

        $used = CouponCustomer::query()
            ->join('coupons', 'coupons.id', '=', 'coupon_customer.coupon_id')
            ->whereBetween('coupon_customer.used_at', [$from, $to])
            ->when($usage, fn ($q) => $q->where('coupons.usage_type', $usage))
            ->when($shopId, fn ($q) => $q->where('coupon_customer.used_shop_id', $shopId));

        return view('admin.promotions.coupons.stats', [
            'period' => $period, 'from' => Carbon::parse($from), 'to' => Carbon::parse($to),
            'usage' => $usage, 'shopId' => $shopId, 'shops' => $this->shops(),
            'ranking' => (clone $used)
                ->groupBy('coupons.id', 'coupons.name', 'coupons.usage_type', 'coupons.shop_id')
                ->orderByRaw('count(*) desc')
                ->limit(20)
                ->get(['coupons.id', 'coupons.name', 'coupons.usage_type', 'coupons.shop_id', DB::raw('count(*) as uses')]),
            'byDay' => (clone $used)
                ->selectRaw("to_char(coupon_customer.used_at, 'YYYY-MM-DD') as dia, count(*) as total")
                ->groupBy('dia')->orderBy('dia')->pluck('total', 'dia'),
            'totalUsed' => (clone $used)->count(),
            'totalIssued' => CouponCustomer::whereBetween('issued_at', [$from, $to])->count(),
        ]);
    }

    private function formData(Coupon $coupon): array
    {
        return [
            'coupon' => $coupon,
            'shops' => $this->shops(),
            'usages' => CouponUsage::cases(),
            'validities' => CouponValidity::cases(),
            'ranks' => LotteryRank::cases(),
            'types' => CouponDiscountType::cases(),
        ];
    }

    private function validated(Request $request): array
    {
        $usage = CouponUsage::tryFrom($request->integer('usage_type'));
        $validity = CouponValidity::tryFrom($request->integer('validity_type'));

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'shop_id' => ['nullable', 'exists:shops,id'],
            'usage_type' => ['required', new Enum(CouponUsage::class)],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['required', new Enum(CouponDiscountType::class)],
            'value' => ['required', 'integer', 'min:1', $request->integer('type') === 1 ? 'max:100' : 'max:100000000'],
            'cost' => [$usage?->hasCost() ? 'required' : 'nullable', 'integer', $usage?->hasCost() ? 'min:1' : 'min:0'],
            'validity_type' => ['required', new Enum(CouponValidity::class)],
            'validity_days' => [$validity === CouponValidity::Days ? 'required' : 'nullable', 'integer', 'min:1', 'max:3650'],
            'valid_until' => [$validity === CouponValidity::UntilDate ? 'required' : 'nullable', 'date'],
            'reissue_flg' => ['nullable', 'in:0,1'],
            'lottery_rank' => ['nullable', new Enum(LotteryRank::class)],
            'notes' => ['nullable', 'array', 'max:5'],
            'notes.*' => ['nullable', 'string', 'max:255'],
            'display_flg' => ['nullable', 'in:0,1'],
            'status' => ['nullable', 'in:0,1'],
        ], [
            'value.max' => 'Un descuento en porcentaje no puede pasar del 100 %.',
        ], [
            'name' => 'título', 'value' => 'descuento', 'cost' => 'costo', 'validity_days' => 'días de validez', 'valid_until' => 'fecha límite',
        ]);

        return [
            'cost' => $usage?->hasCost() ? (int) $data['cost'] : 0,
            'validity_days' => $validity === CouponValidity::Days ? $data['validity_days'] : null,
            'valid_until' => $validity === CouponValidity::UntilDate ? $data['valid_until'] : null,
            'notes' => array_values(array_filter($data['notes'] ?? [], fn ($n) => filled($n))),
            'reissue_flg' => (int) ($data['reissue_flg'] ?? 0),
            'display_flg' => (int) ($data['display_flg'] ?? 0),
            'status' => (int) ($data['status'] ?? 1),
        ] + $data;
    }
}
