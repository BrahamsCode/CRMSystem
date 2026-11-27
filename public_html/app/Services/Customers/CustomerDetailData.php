<?php

namespace App\Services\Customers;

use App\Models\BaseSystem;
use App\Models\BaseSystemChild;
use App\Models\BaseSystemGrandchild;
use App\Models\ContentsMaster;
use App\Models\Customers\CustomCategory;
use App\Models\Customers\Customer;
use App\Models\Staff;
use App\Services\Integraciones\MobileShop\MobileShopClient;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Datos de la ficha 顧客登録情報 (`system/category1/inc/customer_detail.php`) y de cada pestaña
 * (`detail_tab*.php`, `detail_tabcustom.php`): un método por pestaña con las variables que documenta su Blade.
 * Las tablas de otros módulos (puntos, sellos, cupones, rangos: S3) se leen con DB::table(), como el legacy.
 */
class CustomerDetailData
{
    /** Pestañas con plantilla propia (resources/views/admin/clientes/tabs); el resto cae en tab1 como el is_file del legacy */
    private const TABS = ['2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', 'custom'];

    private ?array $relatedFeaturesConfig = null;

    private ?array $shopList = null;

    public function __construct(
        private readonly CustomFieldRenderer $fields,
        private readonly MobileShopClient $mobileShop,
    ) {}

    /** Cliente (también los borrados, para poder restaurarlos) con shoukaisha_name ('【id】  name' del 紹介者) */
    public function customer(int $cd): ?Customer
    {
        $customer = Customer::withDeleted()->find($cd);
        if ($customer) {
            $referrer = $customer->shoukai_tb_customer_cd
                ? DB::table('tb_customer')->where('cd', $customer->shoukai_tb_customer_cd)->first(['id', 'name'])
                : null;
            // El legacy concatenaba en SQL: sin 紹介者 el resultado es NULL
            $customer->setAttribute('shoukaisha_name', $referrer ? '【' . $referrer->id . '】  ' . $referrer->name : null);
        }

        return $customer;
    }

    /** `tab` como lo valida el legacy (/^(\d{1,2}|custom)?$/; otro valor = '') */
    public function normalizeTab(mixed $tab): string
    {
        $tab = is_string($tab) ? $tab : '';

        return preg_match('/^(\d{1,2}|custom)?$/', $tab) ? $tab : '';
    }

    /** Variables de show.blade.php y de la pestaña elegida */
    public function forShow(Customer $customer, string $tab, Request $request, bool $popup): array
    {
        $searchCateCd = (string) $request->input('search_cate_cd', '');
        $searchCates = CustomCategory::query()->forCustomers()->where('hyouji_flg', '1')->orderBy('rank')->get();

        $common = [
            'customer' => $customer,
            'tab' => $tab,
            'searchCateCd' => $searchCateCd,
            'relatedFeaturesConfig' => $this->relatedFeaturesConfig(),
            'packages' => $this->packages(),
            'searchCates' => $searchCates,
            'karuteTabLabel' => $this->manageWord('カルテ', 'c1_customer_detail[カルテ]'),
            'appLayoutMode' => $this->appLayoutMode(),
            'popup' => $popup,
            'isTablet' => $this->isTablet($request),
            'shopList' => $this->shopList(),
            'isMaster' => (bool) $request->user('admin')?->isMaster(),
        ];

        // La pestaña personalizada exige una categoría de la barra (si no, el legacy no tenía datos): cae en tab1
        $searchCate = $tab === 'custom' ? $searchCates->firstWhere('cd', (int) $searchCateCd) : null;
        $tabKey = in_array($tab, self::TABS, true) && ($tab !== 'custom' || $searchCate) ? $tab : '1';

        $data = match ($tabKey) {
            '1' => $this->tab1($customer),
            '2' => $this->tab2(),
            '3' => $this->tab3($customer),
            '4' => $this->tab4($customer, $request),
            '5' => $this->tab5($customer, $request),
            '6' => $this->tab6($customer, $request),
            '7' => $this->tab7($customer, $request),
            '8' => $this->tab8($customer, $request),
            '9' => $this->tab9($customer, $request),
            '10' => $this->tab10($customer, $request),
            '11' => $this->tab11($customer),
            '12' => $this->tab12($customer),
            '13' => $this->tab13($customer, $request),
            '14' => $this->tab14($customer),
            'custom' => $this->custom($customer, $searchCate->toArray(), $request),
        };

        // show.blade.php elige la plantilla con $tab: una categoría inexistente debe caer también en tab1
        if ($tab === 'custom' && ! $searchCate) {
            $common['tab'] = '';
        }

        return $common + $data;
    }

    // ---------------------------------------------------------------- pestañas

    /** 顧客情報 (detail_tab.php): incluye 個人情報 (tab2) y 販促用情報 (tab3) */
    public function tab1(Customer $customer): array
    {
        $staffEnabled = $this->childActive('スタッフ管理');
        $staffName = $staffEnabled && $customer->tb_staff_cd
            ? Staff::query()->withoutGlobalScopes()->where('cd', $customer->tb_staff_cd)->value('name')
            : null;

        return [
            'staffEnabled' => $staffEnabled,
            'staffName' => $staffName,
            'rankRows' => $this->rankRows($customer),
        ] + $this->tab2() + $this->tab3($customer);
    }

    /** 個人情報/法人情報 (detail_tab2.php) */
    public function tab2(): array
    {
        // checkImeiItem(): existe el項目 imei en tb_koumoku
        return ['showImei' => DB::table('tb_koumoku')->where('system_cate', '1')->where('tb_koumoku', 'imei')->exists()];
    }

    /** 販促用情報 (detail_tab3.php) */
    public function tab3(Customer $customer): array
    {
        return [
            'showReminderMail' => DB::table('tb_base_system')->where('system_flg', '1')->whereIn('cd', [9, 11])->exists(),
            'customerGroupName' => $customer->tb_customer_group_cd
                ? DB::table('tb_customer_group')->where('cd', $customer->tb_customer_group_cd)->value('name') : null,
            'raitenDoukiName' => $customer->tb_raiten_douki_master_cd
                ? DB::table('tb_raiten_douki_master')->where('cd', $customer->tb_raiten_douki_master_cd)->value('name') : null,
        ];
    }

    /** 来店履歴 (detail_tab4.php) */
    public function tab4(Customer $customer, Request $request): array
    {
        $query = DB::table('tb_raiten_rireki as tb1')
            ->join('tb_shop as tb2', 'tb1.shop_cd', '=', 'tb2.cd')
            ->where('tb1.user_cd', $customer->cd)
            ->whereRaw("coalesce(tb1.disp_flg, '') <> '9'")
            ->orderByDesc('tb1.raiten_time')
            ->select('tb1.*', 'tb2.kaishamei');

        return [
            'raitenList' => $this->paginate($query, $request, 20),
            'sessionShopCd' => session('shop_cd'),
        ];
    }

    /** カルテ (detail_tab5.php) */
    public function tab5(Customer $customer, Request $request): array
    {
        $base = fn () => DB::table('tb_karute as tb1')
            ->join('tb_sejutsu_cate as tb2', 'tb1.tb_sejutsu_cate_cd', '=', 'tb2.cd')
            ->select('tb1.*', 'tb2.name as sejutsu_name');

        $selected = filled($request->input('karute_cd'))
            ? $base()->where('tb1.cd', (int) $request->input('karute_cd'))->first()
            : $base()->where('tb1.tb_customer_cd', $customer->cd)->orderByDesc('uketsuke_date')->orderByDesc('tb1.cd')->first();

        $list = $base()->where('tb1.tb_customer_cd', $customer->cd)->orderByDesc('uketsuke_date')->orderByDesc('tb1.cd');

        return ['karuteSelected' => $selected, 'karuteList' => $this->paginate($list, $request, 20)];
    }

    /** マイクーポン (detail_tab6.php): vigentes o usadas (?used=true), 10 por página fijo */
    public function tab6(Customer $customer, Request $request): array
    {
        $used = $request->input('used') === 'true';
        $query = DB::table(($used ? 'tb_mycoupon_used' : 'tb_mycoupon') . ' as tb1')
            ->join('tb_mycoupon_master as tb2', 'tb1.tb_mycoupon_master_cd', '=', 'tb2.cd')
            ->leftJoin('tb_shop as tb3', 'tb1.tb_shop_cd', '=', 'tb3.cd')
            ->where('tb1.tb_customer_cd', $customer->cd)
            ->select('tb1.*', 'tb2.title', 'tb2.comment', 'tb3.kaishamei');

        if ($used) {
            $query->orderByDesc('tb1.used_date');
        } else {
            $query->where('tb1.time_limit', '>', (string) time())
                ->where(fn ($q) => $q->where('tb1.used_flg', '!=', '1')->orWhereNull('tb1.used_flg'))
                ->orderByDesc('tb1.time_limit');
        }

        $list = $this->paginate($query, $request, 10, fixedKensu: 10);
        $list->getCollection()->each(function ($row) {
            $row->shop_name = (string) $row->tb_shop_cd === '-1' ? '全店舗' : $row->kaishamei;
        });

        return ['couponUsed' => $used, 'couponList' => $list];
    }

    /** マイアルバム (detail_tab7.php): 5 por página fijo */
    public function tab7(Customer $customer, Request $request): array
    {
        $base = fn () => DB::table('tb_photo_album')->where('disp_flg', '1');

        $selected = filled($request->input('photoalbum_cd'))
            ? $base()->where('cd', (int) $request->input('photoalbum_cd'))->first()
            : $base()->where('tb_customer_cd', $customer->cd)->orderByDesc('disp_date')->orderByDesc('cd')->first();

        $list = $base()->where('tb_customer_cd', $customer->cd)->orderByDesc('disp_date')->orderByDesc('cd');

        return ['albumSelected' => $selected, 'albumList' => $this->paginate($list, $request, 5, fixedKensu: 5)];
    }

    /** ポイント (detail_tab8.php) */
    public function tab8(Customer $customer, Request $request): array
    {
        // Point::fetchCurrentPoint()
        $current = DB::table('tb_customer_point')->where('tb_customer_cd', $customer->cd)->first(['service_point', 'point_yukoukigen']);
        $shukei = DB::table('tb_point_log')->where('tb_customer_cd', $customer->cd)->whereRaw("coalesce(disp_flg, '') <> '9'")
            ->selectRaw('SUM(kaikei) AS kaikei_sum, AVG(kaikei)::bigint AS kaikei_avg')->first();

        $logs = DB::table('tb_point_log as tb1')
            ->leftJoin('tb_staff as tb2', 'tb1.tb_staff_cd', '=', 'tb2.cd')
            ->leftJoin('tb_terminal as tb3', 'tb1.tb_terminal_cd', '=', 'tb3.cd')
            ->leftJoin('tb_shop as tb4', 'tb1.tb_shop_cd', '=', 'tb4.cd')
            ->where('tb1.tb_customer_cd', $customer->cd)
            ->whereRaw("coalesce(tb1.disp_flg, '') <> '9'")
            ->orderByDesc('tb1.touroku_date')
            ->selectRaw(<<<'SQL'
                tb1.cd, tb1.tb_shop_cd, tb1.tb_staff_cd, tb1.tb_customer_cd, tb1.tb_terminal_cd, tb1.touroku_date,
                tb1.shori_mae_point, tb1.kaikei, tb1.sashihiki_kaikei, tb1.shiharai_houhou, tb1.percentage, tb1.shouhi_point,
                tb1.jidou_point, tb1.sounyu_point, tb1.meisai_mail, tb1.raiten_shori, tb1.action_flg,
                tb2.name AS staff_name, tb3.terminal_no, tb4.kaishamei,
                CASE
                    WHEN tb1.action_flg LIKE '%mycolle:pointUse%' THEN tb1.shouhi_point
                    WHEN tb1.sounyu_point > 0 THEN tb1.sounyu_point
                    WHEN tb1.shouhi_point > 0 THEN tb1.shouhi_point
                    ELSE 0
                END AS use_point
                SQL);

        return [
            'customerPoint' => (int) ($current->service_point ?? 0),
            'pointYukoukigen' => isset($current->point_yukoukigen) ? (int) $current->point_yukoukigen : null,
            'pointYukoukigenFlg' => $this->adminMasterValue('point_yukoukigen_flg'),
            'kaikeiSum' => (int) ($shukei->kaikei_sum ?? 0),
            'kaikeiAvg' => (int) ($shukei->kaikei_avg ?? 0),
            'pointLogs' => $this->asArrays($this->paginate($logs, $request, 20)),
        ];
    }

    /** カルテ / 関連情報 (detail_tab9.php): admin_master.related_features = 'flag<>tipo<>tb_search_cate.cd' */
    public function tab9(Customer $customer, Request $request): array
    {
        $config = $this->relatedFeaturesConfig();
        $cateCd = (string) ($config[2] ?? '');
        $searchCate = ctype_digit($cateCd) ? DB::table('tb_search_cate')->where('cd', (int) $cateCd)->first() : null;

        $data = [
            'searchCateData' => $searchCate ? (array) $searchCate : null,
            'karuteLabel' => $this->manageWord('カルテ', 'c1_customer_detail[カルテ]'),
            'packName' => null,
            'packTab' => null,
            'relatedFeature' => null,
            'relatedLog' => null,
            'relatedFields' => [],
            'timeline' => new LengthAwarePaginator([], 0, 15, 1),
        ];
        if (! $searchCate || (string) $searchCate->disp_flg !== '1') {
            return $data;
        }

        // ポイント (8) o スタンプ (10) según el tipo de relación, si su módulo está activo
        $packType = (string) ($config[1] ?? '');
        if ($packType === '1' && $this->grandchildActive('ポイント管理')) {
            [$data['packName'], $data['packTab']] = ['ポイント', 8];
        } elseif ($packType === '2' && $this->childActive('スタンプ管理')) {
            [$data['packName'], $data['packTab']] = ['スタンプ', 10];
        }

        // 来店 seleccionado (?related_features_cd) o el último
        $visits = DB::table('tb_raiten_rireki')->where('user_cd', $customer->cd)->whereRaw("COALESCE(disp_flg, '') <> '9'");
        $feature = filled($request->input('related_features_cd'))
            ? $visits->where('cd', (int) $request->input('related_features_cd'))->first()
            : $visits->orderByDesc('raiten_time')->orderByDesc('cd')->first();

        if ($feature) {
            $data['relatedFeature'] = (array) $feature;
            $logTable = match (true) {
                $packType === '1' && $feature->tb_point_log_cd !== null => ['tb_point_log', $feature->tb_point_log_cd],
                $packType === '2' && $feature->tb_stamp_log_cd !== null => ['tb_stamp_log', $feature->tb_stamp_log_cd],
                default => null,
            };
            if ($logTable) {
                $log = DB::table($logTable[0] . ' as tb1')->join('tb_staff as tb2', 'tb1.tb_staff_cd', '=', 'tb2.cd')
                    ->where('tb1.cd', $logTable[1])->select('tb1.*', 'tb2.name as staff_name')->first();
                $data['relatedLog'] = $log ? (array) $log : null;
            }
            $data['relatedFields'] = $this->customFields($customer, (int) $searchCate->cd, (string) $feature->tb_customer_sub_inner_group);
        }

        $data['timeline'] = $this->asArrays($this->paginate($this->timelineQuery($customer, (int) $searchCate->cd, $packType), $request, 15, fixedKensu: 15));

        return $data;
    }

    /** スタンプ (detail_tab10.php) */
    public function tab10(Customer $customer, Request $request): array
    {
        // cStamp::getStampData(): contadores de tb_customer y totales de tb_stamp_log (number_format, como el legacy)
        $shukei = DB::table('tb_stamp_log')->where('tb_customer_cd', $customer->cd)->whereRaw("coalesce(disp_flg, '') <> '9'")
            ->selectRaw('SUM(kaikei) AS kaikei_sum, AVG(kaikei)::bigint AS kaikei_avg')->first();
        $stampInfo = [
            'stamp_count' => (int) $customer->stamp_count,
            'stamp_yukoukigen' => $customer->stamp_yukoukigen,
            'last_stamp_time' => $customer->last_stamp_time,
            'kaikei_sum' => number_format((int) ($shukei->kaikei_sum ?? 0)),
            'kaikei_avg' => number_format((int) ($shukei->kaikei_avg ?? 0)),
        ];

        // cStamp::getStampConfig(1)
        $config = DB::table('tb_stamp_config')->where('cd', 1)->first();

        $logs = DB::table('tb_stamp_log as tb1')
            ->leftJoin('tb_staff as tb2', 'tb1.tb_staff_cd', '=', 'tb2.cd')
            ->leftJoin('tb_terminal as tb3', 'tb1.tb_terminal_cd', '=', 'tb3.cd')
            ->where('tb1.tb_customer_cd', $customer->cd)
            ->whereRaw("coalesce(tb1.disp_flg, '') <> '9'")
            ->orderByDesc('tb1.touroku_date')->orderByDesc('tb1.cd')
            ->select('tb1.cd', 'tb1.tb_shop_cd', 'tb1.tb_staff_cd', 'tb1.tb_customer_cd', 'tb1.tb_terminal_cd', 'tb1.touroku_date',
                'tb1.shori_mae_stamp', 'tb1.kaikei', 'tb1.shiharai_houhou', 'tb1.percentage', 'tb1.jidou_stamp', 'tb1.sounyu_stamp',
                'tb1.meisai_mail', 'tb1.raiten_shori', 'tb1.shouhi_stamp', 'tb1.action_flg', 'tb2.name as staff_name', 'tb3.terminal_no');

        return [
            'stampInfo' => $stampInfo,
            'stampConf' => ['stamp_max' => (int) ($config->stamp_max ?? 0), 'touroku_bonus' => (int) ($config->touroku_bonus ?? 0)],
            'stampShikkouSousa' => $this->adminMasterValue('stamp_shikkou_sousa'),
            'stampLogs' => $this->asArrays($this->paginate($logs, $request, 20)),
            'karuteLabel' => $this->manageWord('カルテ', 'c1_customer_detail[カルテ]'),
        ];
    }

    /** 購買履歴 (detail_tab11.php): el historial viene del POS (fuera de alcance, PosGateway nulo); aquí solo los selectores */
    public function tab11(Customer $customer): array
    {
        $formCd = $this->adminMasterValue('pos_koubai_tb_search_form_cd');
        $formName = null;
        $options = [];
        if ($formCd) {
            $formName = DB::table('tb_search_form')->where('cd', (int) $formCd)->value('name');
            $options = DB::table('tb_customer_sub')->where('disp_flg', '1')->where('tb_customer_cd', $customer->cd)
                ->where('tb_search_form_cd', (int) $formCd)->get(['cd', 'text_data'])->map(fn ($r) => (array) $r)->all();
        }

        return [
            'posShops' => DB::table('tb_shop')->whereRaw("coalesce(pos_domain, '') <> ''")->orderBy('cd')
                ->get(['cd', 'pos_domain', 'kaishamei'])->map(fn ($r) => (array) $r)->all(),
            'posKoubaiFormName' => $formName,
            'posKoubaiOptions' => $options,
        ];
    }

    /** 予約状況 (detail_tab12.php): reservas desde hoy */
    public function tab12(Customer $customer): array
    {
        $today = Carbon::now('Asia/Tokyo')->format('Y/m/d');
        $rows = DB::table('tb_yoyaku as tb1')
            ->join('tb_yoyaku_uketsuke_day as tb2', 'tb1.tb_yoyaku_uketsuke_day_cd', '=', 'tb2.cd')
            ->where('tb1.tb_customer_cd', $customer->cd)
            ->where('tb2.moushikomi_date', '>=', $today)
            ->where('tb1.disp_flg', '1')
            ->orderByDesc('tb2.moushikomi_date')->orderByDesc('tb1.start_time')->orderBy('tb1.cd')
            ->select('tb1.*', 'tb2.moushikomi_date', 'tb2.tb_staff_cd')
            ->get();

        $reservations = $rows->map(function ($row) {
            $day = Carbon::parse(str_replace('/', '-', (string) $row->moushikomi_date), 'Asia/Tokyo')->startOfDay();
            $staff = DB::table('tb_staff')->where('cd', $row->tb_staff_cd)->first(['name', 'shop_cd']);
            $shopName = $staff ? DB::table('tb_shop')->where('disp_flg', '1')->where('cd', $staff->shop_cd)->value('kaishamei') : null;

            return [
                'moushikomi_date' => $row->moushikomi_date,
                'start_label' => $this->hourLabel($day, (int) $row->start_time),
                'end_label' => $this->hourLabel($day, (int) $row->end_time),
                'shop_name' => $shopName,
                'staff_name' => $staff->name ?? null,
                'menu' => $this->reservationMenu($row),
                'comment' => $row->comment,
            ];
        })->all();

        return ['staffLabel' => $this->manageWord('担当', 'c9_yoyaku[スタッフ]'), 'reservations' => $reservations];
    }

    /** 紹介実績 (detail_tab13.php) */
    public function tab13(Customer $customer, Request $request): array
    {
        $query = DB::table('tb_customer')->where('shoukai_tb_customer_cd', $customer->cd)
            ->where('disp_flg', '1')->where('touroku_kubun', Customer::MEMBER_KUBUN)
            ->orderByDesc('touroku_date')
            ->select('cd', 'id', 'kanri_id', 'name', 'touroku_date');

        $staff = $request->user('admin');
        $referrals = $this->asArrays($this->paginate($query, $request, 20));
        $referrals->setCollection($referrals->getCollection()->map(function (array $row) use ($staff) {
            $visits = DB::table('tb_raiten_rireki')->where('user_cd', $row['cd'])->whereRaw("coalesce(disp_flg, '') <> '9'");
            // No master: solo las visitas de la tienda de la sesión, como el legacy
            if (! $staff?->isMaster()) {
                $visits->where('shop_cd', (int) session('shop_cd'));
            }

            return $row + ['raiten_count' => $visits->count()];
        }));

        return ['referrals' => $referrals];
    }

    /**
     * 有料会員状況 (detail_tab14.php): estado en la API de la tienda móvil (S5). Sin URL configurada
     * o si la API falla, no hay baja programada y queda el paid_member_flg guardado.
     */
    public function tab14(Customer $customer): array
    {
        $paidMember = false;
        try {
            $token = $this->mobileShop->fetchToken();
            if ($token !== false) {
                $paidMember = $this->mobileShop->fetchPaidMemberFlg($token, $customer->id);
                // fetchPaidMemberFlg actualiza tb_customer.paid_member_flg
                $customer->paid_member_flg = DB::table('tb_customer')->where('cd', $customer->cd)->value('paid_member_flg');
            }
        } catch (Throwable $e) {
            Log::warning('有料会員状況: MobileShop no responde', ['customer' => $customer->cd, 'error' => $e->getMessage()]);
        }

        return ['paidMemberCancellation' => is_object($paidMember) && ($paidMember->can_ymd ?? '') != ''];
    }

    /** Categoría personalizada (detail_tabcustom.php) */
    public function custom(Customer $customer, array $searchCate, Request $request): array
    {
        $cateCd = (int) $searchCate['cd'];
        $multiple = (float) ($searchCate['record_type'] ?? 0) == 1;
        $data = [
            'searchCateData' => $searchCate,
            'fukusuGroupCd' => null,
            'kanrenCd' => null,
            'firstSearchForm' => null,
            'posKoubaiSearchFormCd' => $this->adminMasterValue('pos_koubai_tb_search_form_cd'),
            'customRecords' => new LengthAwarePaginator([], 0, 5, 1),
        ];

        $group = null;
        if ($multiple) {
            $group = filled($request->input('fukusu_group_cd'))
                ? (string) $request->input('fukusu_group_cd')
                : DB::table('tb_customer_sub')->where('disp_flg', '1')->where('tb_customer_cd', $customer->cd)
                    ->where('tb_search_cate_cd', $cateCd)->max(DB::raw('cast(category_inner_group as int)'));
            $data['fukusuGroupCd'] = $group !== null ? (string) $group : null;
        }

        // 関連済: visita enlazada a este registro (bloquea el borrado)
        if (! $multiple || $data['fukusuGroupCd']) {
            $data['kanrenCd'] = DB::table('tb_raiten_rireki')->where('user_cd', $customer->cd)->where('tb_search_cate_cd', $cateCd)
                ->where('tb_customer_sub_inner_group', (string) $data['fukusuGroupCd'])->value('cd');
        }
        $data['customFields'] = $this->customFields($customer, $cateCd, $multiple ? (string) $data['fukusuGroupCd'] : null);

        if ($multiple) {
            $first = DB::table('tb_search_form')->where('tb_search_cate_cd', $cateCd)->whereIn('disp_flg', ['1', '2'])
                ->orderBy('rank')->first(['cd', 'name']);
            $data['firstSearchForm'] = $first ? (array) $first : null;

            $list = DB::table('tb_customer_sub as tb1')
                ->leftJoin('tb_raiten_rireki as tb2', function ($join) {
                    $join->on('tb1.tb_search_cate_cd', '=', 'tb2.tb_search_cate_cd')
                        ->on('tb1.category_inner_group', '=', 'tb2.tb_customer_sub_inner_group')
                        ->on('tb1.tb_customer_cd', '=', 'tb2.user_cd');
                })
                ->where('tb1.tb_search_cate_cd', $cateCd)->where('tb1.disp_flg', '1')->where('tb1.tb_customer_cd', $customer->cd)
                ->selectRaw('distinct(tb1.category_inner_group::integer) as category_inner_group, tb1.touroku_date, tb1.update_date, tb2.cd as raiten_cd')
                ->orderByDesc('category_inner_group');

            $records = $this->asArrays($this->paginate($list, $request, 5, fixedKensu: 5));
            $records->setCollection($records->getCollection()->map(function (array $row) use ($customer, $cateCd, $first) {
                $text = $first ? DB::table('tb_customer_sub')->where('tb_search_cate_cd', $cateCd)->where('tb_search_form_cd', $first->cd)
                    ->where('disp_flg', '1')->where('tb_customer_cd', $customer->cd)
                    ->where('category_inner_group', (string) $row['category_inner_group'])->first(['cd', 'text_data']) : null;

                return $row + ['text_data' => $text->text_data ?? null, 'text_data_cd' => $text->cd ?? null];
            }));
            $data['customRecords'] = $records;
        }

        return $data;
    }

    // ---------------------------------------------------------------- comunes

    /** explode('<>', admin_master.related_features) */
    public function relatedFeaturesConfig(): array
    {
        return $this->relatedFeaturesConfig ??= explode('<>', (string) $this->adminMasterValue('related_features'));
    }

    /** Módulos activos que muestran pestañas (las mismas consultas de la barra del legacy) */
    public function packages(): array
    {
        return [
            'raiten' => $this->childActive('来店履歴管理'),
            'coupon' => $this->childActive('マイクーポン管理'),
            'album' => $this->grandchildActive('マイアルバム管理'),
            'point' => $this->grandchildActive('ポイント管理'),
            'stamp' => $this->childActive('スタンプ管理'),
            'kounyu' => $this->grandchildActive('購買履歴'),
            'yoyaku' => BaseSystem::query()->where('cd', 9)->where('system_name', '予約管理')->where('system_flg', '1')->exists(),
        ];
    }

    /** tb_shop (disp_flg != 9) cd => kaishamei */
    public function shopList(): array
    {
        return $this->shopList ??= DB::table('tb_shop')->where('disp_flg', '!=', '9')->orderBy('cd')->pluck('kaishamei', 'cd')->all();
    }

    /** manageWord() del legacy: texto de tb_manage_words o el de por defecto */
    public function manageWord(string $default, string $contentName): string
    {
        $naiyou = DB::table('tb_manage_words')->where('content_name', $contentName)->value('naiyou');

        return filled($naiyou) ? (string) $naiyou : $default;
    }

    /** $isTablet de kihon.php: cUserAgent DEVICE_NAME en ('CRMRW107', 'CRMH580VT_A') */
    public function isTablet(Request $request): bool
    {
        return str_contains((string) $request->userAgent(), 'CRMRW107')
            || str_contains((string) $request->userAgent(), 'CRMH580VT_A');
    }

    // ---------------------------------------------------------------- auxiliares

    /**
     * Paginación como inc/page_control.php: ?kensu (por defecto $defaultKensu, o fijo), ?page desde 1
     * y sin pasar de la última página.
     */
    private function paginate(Builder|EloquentBuilder $query, Request $request, int $defaultKensu, ?int $fixedKensu = null): LengthAwarePaginator
    {
        $kensu = $fixedKensu ?? (int) $request->input('kensu', $defaultKensu);
        if ($kensu < 1) {
            $kensu = $defaultKensu;
        }
        $total = DB::query()->fromSub($query, 'tb_count')->count();
        $page = min(max(1, (int) $request->input('page', 1)), max(1, (int) ceil($total / $kensu)));
        $items = $total > 0 ? (clone $query)->forPage($page, $kensu)->get() : collect();

        return new LengthAwarePaginator($items, $total, $kensu, $page);
    }

    /** Las plantillas que leen las filas como array ($row['col']) */
    private function asArrays(LengthAwarePaginator $paginator): LengthAwarePaginator
    {
        return $paginator->setCollection($paginator->getCollection()->map(fn ($row) => (array) $row));
    }

    /** Campos de la categoría (hyouji_flg 1 o 2) con su valor pintado; $group = category_inner_group (null = sin filtrar) */
    private function customFields(Customer $customer, int $cateCd, ?string $group): array
    {
        return DB::table('tb_search_form')->where('tb_search_cate_cd', $cateCd)
            ->where('disp_flg', '<>', '9')->whereIn('hyouji_flg', ['1', '2'])->orderBy('rank')->get()
            ->map(function ($field) use ($customer, $group) {
                $sub = DB::table('tb_customer_sub')->where('tb_search_form_cd', $field->cd)->where('tb_customer_cd', $customer->cd)
                    ->where('disp_flg', '<>', '9')
                    ->when($group !== null, fn ($q) => $q->where('category_inner_group', $group))
                    ->first();

                return [
                    'name' => $field->name,
                    'form_type' => $field->form_type,
                    'unit' => $field->unit,
                    'comment' => $field->comment,
                    'html' => $this->fields->render($field, $sub),
                ];
            })->all();
    }

    /** UNION de detail_tab9.php: visitas, puntos o sellos sueltos y registros de la categoría sin visita */
    private function timelineQuery(Customer $customer, int $cateCd, string $packType): Builder
    {
        $cd = $customer->cd;
        $query = DB::table('tb_raiten_rireki')->where('user_cd', $cd)->whereRaw("COALESCE(disp_flg, '') <> '9'")
            ->selectRaw("cd, raiten_time::bigint AS shori_time, shop_cd AS tb_shop_cd, tb_point_log_cd AS data1, tb_search_cate_cd AS data2, tb_customer_sub_inner_group::integer AS data3, tb_stamp_log_cd||'' AS data4, 'raiten' AS cate");

        if ($packType === '1') {
            $query->union(DB::table('tb_point_log')->where('tb_customer_cd', $cd)->whereRaw("COALESCE(disp_flg, '') <> '9'")
                ->whereRaw('cd NOT IN (select tb_point_log_cd from tb_raiten_rireki where user_cd = ? and tb_point_log_cd is not null)', [$cd])
                ->selectRaw("cd, touroku_date, tb_shop_cd, jidou_point, sounyu_point, shouhi_point, action_flg, 'point' AS cate"));
        } elseif ($packType === '2') {
            $query->union(DB::table('tb_stamp_log')->where('tb_customer_cd', $cd)->whereRaw("COALESCE(disp_flg, '') <> '9'")
                ->whereRaw('cd NOT IN (select tb_stamp_log_cd from tb_raiten_rireki where user_cd = ? and tb_stamp_log_cd is not null)', [$cd])
                ->selectRaw("cd, touroku_date, tb_shop_cd, jidou_stamp, sounyu_stamp, shouhi_stamp, action_flg, 'stamp' AS cate"));
        }

        $query->union(DB::table('tb_customer_sub')->where('tb_customer_cd', $cd)->where('tb_search_cate_cd', $cateCd)
            ->whereRaw("category_inner_group NOT IN (select coalesce(tb_customer_sub_inner_group, '') from tb_raiten_rireki where user_cd = ?)", [$cd])
            ->groupByRaw('touroku_date::bigint, category_inner_group::int')
            ->selectRaw("category_inner_group::int, touroku_date::bigint, null, null, null, null, null, 'karute'"));

        return DB::query()->fromSub($query, 'tl')->orderByDesc('shori_time');
    }

    /** Fila de ランク por módulo 顧客ランク% activo (金額 o 来店) */
    private function rankRows(Customer $customer): array
    {
        $modules = DB::table('tb_base_system_grandchild')->where('system_name', 'like', '顧客ランク%')->where('system_flg', '1')->pluck('system_name');
        if ($modules->isEmpty()) {
            return [];
        }

        $ranks = fn (string $table) => DB::table($table)->where('disp_flg', '1')->orderBy('rank')->orderBy('cd')->pluck('name', 'cd');
        $rows = [];
        foreach ($modules as $name) {
            if (str_contains($name, '金額')) {
                $list = $ranks('tb_kingaku_rank_master');
                $rows[] = ['title' => '会計金額によるランク', 'now' => $list[$customer->kingaku_rank_now] ?? null, 'pre' => $list[$customer->kingaku_rank_pre] ?? null];
            }
            if (str_contains($name, '来店')) {
                $list = $ranks('tb_raiten_rank_master');
                $rows[] = ['title' => '来店回数によるランク', 'now' => $list[$customer->raiten_rank_now] ?? null, 'pre' => $list[$customer->raiten_rank_pre] ?? null];
            }
        }

        return $rows;
    }

    /** 'H:i' del día + segundos, con la hora +24 si pasa de 86400 (como detail_tab12.php) */
    private function hourLabel(Carbon $day, int $seconds): string
    {
        $time = $day->copy()->addSeconds($seconds);
        $hour = (int) $time->format('H') + ($seconds > 86400 ? 24 : 0);

        return sprintf('%02d', $hour) . ':' . $time->format('i');
    }

    /** Nombres de las メニュー (base + opción) de la reserva vía tb_staff_works del responsable */
    private function reservationMenu(object $row): string
    {
        $cds = array_filter(
            explode(',', trim($row->base_tb_yoyaku_koumoku_master_cd . ',' . $row->option_tb_yoyaku_koumoku_master_cd, ',')),
            fn ($cd) => ctype_digit(trim($cd))
        );
        if ($cds === [] || ! $row->tb_staff_cd) {
            return '';
        }

        return DB::table('tb_staff_works as tb1')
            ->join('tb_yoyaku_koumoku_master as tb2', 'tb1.tb_yoyaku_koumoku_master_cd', '=', 'tb2.cd')
            ->where('tb1.tb_staff_cd', $row->tb_staff_cd)
            ->whereIn('tb1.tb_yoyaku_koumoku_master_cd', array_map('intval', $cds))
            ->where('tb1.disp_flg', '1')->where('tb2.disp_flg', '1')->where('tb1.hyouji_flg', '1')->where('tb2.hyouji_flg', '1')
            ->orderBy('tb2.type_flg')->orderByDesc('tb2.tb_shop_cd')->orderBy('tb2.cd')
            ->pluck('tb2.name')
            ->map(fn ($name) => mb_convert_kana((string) $name, 'k') . ' ')
            ->implode('');
    }

    private function childActive(string $name): bool
    {
        return BaseSystemChild::query()->where('system_name', $name)->where('system_flg', '1')->exists();
    }

    private function grandchildActive(string $name): bool
    {
        return BaseSystemGrandchild::query()->where('system_name', $name)->where('system_flg', '1')->exists();
    }

    /** Columna de admin_master (cd = 1); null si no hay fila */
    private function adminMasterValue(string $column): ?string
    {
        $value = DB::table('admin_master')->where('cd', 1)->value($column);

        return $value === null ? null : (string) $value;
    }

    /** contents_master mycolle/app_layout_mode (1 → pestaña 有料会員状況) */
    private function appLayoutMode(): int
    {
        return (int) ContentsMaster::text('mycolle', 'app_layout_mode', '0');
    }
}
