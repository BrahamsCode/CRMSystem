<?php

namespace App\Services\Customers;

use App\Models\AdminMaster;
use App\Models\Customers\Customer;
use App\Models\Customers\Visit;
use App\Models\Promotions\PointLog;
use App\Models\Staff;
use App\Services\Promotions\PointRuleResolver;
use App\Services\Promotions\PointService;
use App\Services\Promotions\StampService;
use App\Support\LegacyText;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 来店履歴の追加・修正 (`system/category1/inc/kanren_record_register.php`, `kanren_del_record.php`):
 * alta o corrección de una visita con su movimiento de puntos (`tb_point_log`, `tb_customer_point`),
 * de sellos (`tb_stamp_log`, `tb_customer.stamp_count`) y sus campos 関連情報 (`tb_customer_sub` con
 * `category_inner_group`), todo en una transacción como el legacy. Recalcula `shuki_heikin`/`last_raiten_date`.
 *
 * Configuración: `admin_master.related_features` = 'flag<>tipo<>tb_search_cate.cd' (tipo '1' ポイント, '2' スタンプ).
 * Puntos: se escriben las tablas como el legacy (el movimiento lleva 会計, 差引, 還元率… que Point::setPoint no guarda),
 * con la caducidad de `PointService::expiryFrom`. Sellos: regla del día con `PointRuleResolver::stamps`, saldo con
 * `StampService::setCount` (como `pushStampCount` 「新規登録」: sin tocar last_stamp_time) y log con `StampService::log`.
 *
 * Lo usan la pestaña 9 de la ficha (S2) y `tool/api/category1/setRaitenDetail.php` (S5).
 */
class VisitRecordService
{
    /** Mensaje del legacy para la fecha de la visita (clave 'raiten' del error) */
    public const DATE_ERROR = '※日付の入力をご確認ください。';

    public function __construct(
        private VisitService $visits,
        private CustomerFormService $form,
        private CustomerRegistrationService $registration,
        private PointService $points,
        private StampService $stamps,
        private PointRuleResolver $rules,
    ) {}

    /**
     * Alta o corrección de una visita con sus datos 関連情報.
     *
     * $data usa las MISMAS claves que el formulario legacy (y que setRaitenDetail):
     *   related_features_cd (corrección de esa visita: solo fecha, tienda y campos; no toca puntos ni sellos),
     *   raiten_cd (alta enlazada a una visita ya registrada: 「既に取得済みの来店履歴から流用する」; vacío = visita nueva),
     *   raiten_y, raiten_m, raiten_d, raiten_h, raiten_i, kaikei, shiharai_houhou ('1' efectivo, '2' tarjeta),
     *   shouhi_point, sounyu_point, sounyu_point_add_remove_flg ('2' = 減算), selected_shop_cd (vacío = tienda del cliente),
     *   meisai_mail y los campos 関連情報 `form<tb_search_form.cd>` (y sus variantes) de la categoría de
     *   `admin_master.related_features`. Los textos se limpian como el legacy (`form2Db` sobre todo el request).
     *
     * @param  Staff|null  $staff  personal que registra (tb_staff_cd del movimiento; null = sin personal, p. ej. tool/api)
     * @return int  `tb_raiten_rireki.cd` creado o corregido
     *
     * @throws VisitRecordValidationException  con los errores del legacy (`errors()`: campo => mensaje)
     */
    public function save(Customer $customer, array $data, ?Staff $staff = null): int
    {
        $config = $this->config();
        $cateCd = $this->categoryCd($config);
        if ($cateCd === null) {
            // El legacy terminaba la página en blanco (exit) sin categoría configurada
            throw new VisitRecordValidationException(['related_features' => '※関連情報のカテゴリが設定されていません。']);
        }

        $d = $this->clean($data);
        $fields = $this->fields($cateCd);
        $errors = $this->validate($d, $fields);
        if ($errors !== []) {
            throw new VisitRecordValidationException($errors);
        }

        $at = $this->visitTime($d);
        $packType = (string) ($config[1] ?? '');
        $shopCd = (int) (($d['selected_shop_cd'] ?? '') !== '' ? $d['selected_shop_cd'] : $customer->tb_shop_cd);
        // createHatsuban($_SESSION['shop_cd']) para el cd de tb_customer_sub
        $hatsubanShop = (int) ($staff?->shop_cd ?? $shopCd);

        return DB::transaction(function () use ($customer, $d, $staff, $cateCd, $fields, $at, $packType, $shopCd, $hatsubanShop) {
            // Serializa las altas del mismo cliente (el legacy bloqueaba hatuban y tb_customer_sub enteras)
            $row = DB::table('tb_customer')->where('cd', $customer->cd)->lockForUpdate()->first();
            $customer = Customer::withDeleted()->find($customer->cd) ?? $customer;
            $now = now();
            $age = $customer->ageAt($at);
            $businessDay = $this->visits->businessDay($at);

            $newGroup = 1 + (int) DB::table('tb_customer_sub')
                ->where('tb_customer_cd', $customer->cd)->where('tb_search_cate_cd', $cateCd)
                ->max(DB::raw("NULLIF(category_inner_group, '')::int"));

            $updateCd = (int) ($d['related_features_cd'] ?? 0);
            if ($updateCd > 0) {
                // ★ 更新: solo la visita y sus campos (los movimientos de puntos/sellos no se modifican)
                $visit = DB::table('tb_raiten_rireki')->where('cd', $updateCd)->lockForUpdate()->first();
                if (! $visit || (int) $visit->user_cd !== (int) $customer->cd) {
                    throw new VisitRecordValidationException(['related_features_cd' => '※来店履歴が見つかりません。']);
                }
                $group = (string) $visit->tb_customer_sub_inner_group !== '' ? (string) $visit->tb_customer_sub_inner_group : (string) $newGroup;
                // tb_raiten_rireki no tiene touroku_date: el legacy usaba siempre la hora actual
                $recordingTime = $now->getTimestamp();

                DB::table('tb_raiten_rireki')->where('cd', $updateCd)->update([
                    'shop_cd' => $shopCd,
                    'raiten_time' => (string) $at->getTimestamp(),
                    'raiten_hiduke' => $businessDay,
                    'customer_age' => $age,
                    'manual_flg' => '1',
                    'tb_search_cate_cd' => $cateCd,
                    'tb_customer_sub_inner_group' => $group,
                ]);
                $visitCd = $updateCd;
            } else {
                // ★ 新規
                $group = (string) $newGroup;
                $recordingTime = $now->getTimestamp();
                $pointLogCd = $packType === '1' ? $this->pointMovement($customer, $d, $shopCd, $staff, $now) : null;
                $stampLogCd = $packType === '2' ? $this->stampMovement($customer, (int) $row->stamp_count, $d, $shopCd, $staff, $at, $now) : null;

                $values = [
                    'shop_cd' => $shopCd,
                    'raiten_time' => (string) $at->getTimestamp(),
                    'raiten_hiduke' => $businessDay,
                    'customer_age' => $age,
                    'manual_flg' => '1',
                    'tb_point_log_cd' => $pointLogCd,
                    'tb_search_cate_cd' => $cateCd,
                    'tb_customer_sub_inner_group' => $group,
                    'tb_stamp_log_cd' => $stampLogCd,
                ];

                $raitenCd = (int) ($d['raiten_cd'] ?? 0);
                if ($raitenCd > 0) {
                    // Visita ya registrada (lista 「既に取得済みの来店履歴」): se le enlazan el movimiento y los campos
                    DB::table('tb_raiten_rireki')->where('cd', $raitenCd)->where('user_cd', $customer->cd)->update($values);
                    $visitCd = $raitenCd;
                } else {
                    $visitCd = Visit::create($values + [
                        'user_id' => $customer->id,
                        'user_cd' => $customer->cd,
                        'stamp' => 0,
                        'customer_sex' => $customer->kojin_sex,
                        'customer_job' => $customer->job,
                    ])->cd;
                }
            }

            $this->saveFields($customer, $d, $cateCd, $fields, $group, $recordingTime, $hatsubanShop);
            $this->visits->syncVisitStats((int) $customer->cd);

            return (int) $visitCd;
        });
    }

    /** Borra la visita y anula sus movimientos de puntos/sellos y sus campos (`kanren_del_record.php`) */
    public function delete(Customer $customer, int $raitenCd, ?Staff $staff = null): void
    {
        DB::transaction(function () use ($customer, $raitenCd) {
            $visit = DB::table('tb_raiten_rireki')->where('user_cd', $customer->cd)->where('cd', $raitenCd)->lockForUpdate()->first();
            if (! $visit) {
                return;
            }

            if ($visit->tb_search_cate_cd !== null) {
                DB::table('tb_customer_sub')
                    ->where('tb_search_cate_cd', $visit->tb_search_cate_cd)
                    ->where('tb_customer_cd', $customer->cd)
                    ->where('category_inner_group', (string) $visit->tb_customer_sub_inner_group)
                    ->update(['disp_flg' => '9']);
            }
            // Solo se ocultan los movimientos: el saldo actual de puntos/sellos no cambia (como el legacy)
            if ($visit->tb_point_log_cd !== null) {
                DB::table('tb_point_log')->where('cd', $visit->tb_point_log_cd)->update(['disp_flg' => '9']);
            }
            if ($visit->tb_stamp_log_cd !== null) {
                DB::table('tb_stamp_log')->where('cd', $visit->tb_stamp_log_cd)->update(['disp_flg' => '9']);
            }
            DB::table('tb_raiten_rireki')->where('cd', $visit->cd)->where('user_cd', $customer->cd)->update(['disp_flg' => '9']);

            $this->visits->syncVisitStats((int) $customer->cd);
        });
    }

    // ---------------------------------------------------------------- configuración (también para la pantalla)

    /** explode('<>', admin_master.related_features) */
    public function config(): array
    {
        return explode('<>', (string) AdminMaster::current()->related_features);
    }

    /** tb_search_cate.cd de la configuración (null si no hay: el legacy hacía exit) */
    public function categoryCd(array $config): ?int
    {
        $cd = trim((string) ($config[2] ?? ''));

        return ctype_digit($cd) ? (int) $cd : null;
    }

    /** Campos de la categoría (tb_search_form: disp_flg <> 9, hyouji_flg 1 o 2, ORDER BY rank) como arrays */
    public function fields(int $cateCd): array
    {
        return DB::table('tb_search_form')
            ->where('tb_search_cate_cd', $cateCd)
            ->whereRaw("COALESCE(disp_flg, '') <> '9'")
            ->whereIn('hyouji_flg', ['1', '2'])
            ->orderBy('rank')
            ->get()->map(fn ($f) => (array) $f)->all();
    }

    /** Valores guardados de los campos de una visita (createform_insert_request con su category_inner_group) */
    public function fieldValues(Customer $customer, array $fields, ?string $group): array
    {
        $values = [];
        foreach ($fields as $form) {
            $sub = DB::table('tb_customer_sub')
                ->where('tb_search_form_cd', $form['cd'])->where('tb_customer_cd', $customer->cd)
                ->whereRaw("COALESCE(disp_flg, '') <> '9'")->where('category_inner_group', (string) $group)
                ->orderBy('cd')->first();
            $values = array_replace_recursive($values, $this->form->fieldValues($form, $sub));
        }

        return $values;
    }

    /** ajax_shop_point_config.php: ポイントルール de la tienda con los valores por defecto del legacy */
    public function shopPointConfig(int $shopCd): array
    {
        $shop = DB::table('tb_shop')->where('cd', $shopCd)->first();
        $num = fn ($v, $default) => ($v === null || $v === '') ? $default : $v + 0;

        return [
            'shop_name' => (string) ($shop->kaishamei ?? ''),
            'keisan_flg' => (string) (($shop->keisan_flg ?? '') === '' ? '1' : $shop->keisan_flg),
            'shiyou_point_jyougen' => $num($shop->shiyou_point_jyougen ?? null, 1000),
            'point_percentage_genkin' => $num($shop->point_percentage_genkin ?? null, 1),
            'point_percentage_card' => $num($shop->point_percentage_card ?? null, 1),
        ];
    }

    /**
     * ajax_shop_stamp_config.php: スタンプルール de la tienda (還元: X円につき1個, 1000 si no está configurado)
     * y el multiplicador de la regla vigente en $at (cStamp::getStampCount).
     */
    public function shopStampConfig(int $shopCd, ?CarbonInterface $at = null): array
    {
        $shop = DB::table('tb_shop')->where('cd', $shopCd)->first();
        $percent = fn ($v) => (int) $v > 0 ? $v + 0 : 1000;
        $at = ($at ?? now())->copy()->setTimezone('Asia/Tokyo');

        return [
            'shop_name' => (string) ($shop->kaishamei ?? ''),
            'stamp_percentage_genkin' => $percent($shop->stamp_percentage_genkin ?? null),
            'stamp_percentage_card' => $percent($shop->stamp_percentage_card ?? null),
            'stamp_count' => $this->rules->stamps($shopCd, $at),
            'date_disp' => $at->format('Y/m/d H:i'),
        ];
    }

    // ---------------------------------------------------------------- auxiliares

    /** form2Db() de inc/db.php sobre todo el request: 半角カナ → 全角 y recorte de espacios 半角/全角 */
    private function clean(array $data): array
    {
        $clean = fn ($v) => LegacyText::trim(mb_convert_kana((string) $v, 'KV'));

        return collect($data)->map(fn ($v) => match (true) {
            is_array($v) => array_map(fn ($x) => is_array($x) ? '' : $clean($x), $v),
            $v === null => '',
            default => $clean($v),
        })->all();
    }

    /** Comprobaciones de kanren_record_new.php: fecha válida y createform_errorcheck de cada campo */
    private function validate(array $d, array $fields): array
    {
        $errors = [];
        $y = (string) ($d['raiten_y'] ?? '');
        $m = (string) ($d['raiten_m'] ?? '');
        $day = (string) ($d['raiten_d'] ?? '');
        if (! ctype_digit($y) || ! ctype_digit($m) || ! ctype_digit($day) || ! checkdate((int) $m, (int) $day, (int) $y)) {
            $errors['raiten'] = self::DATE_ERROR;
        }

        foreach ($fields as $form) {
            $message = $this->form->customFieldError($form, $d);
            if ($message !== '') {
                $errors['form' . $form['cd']] = $message;
            }
        }

        return $errors;
    }

    /** Fecha y hora de la visita (hora y minuto vacíos = 00, como el legacy) en hora de Japón */
    private function visitTime(array $d): CarbonInterface
    {
        $hour = (int) ($d['raiten_h'] ?? 0);
        $minute = (int) ($d['raiten_i'] ?? 0);

        return Carbon::create((int) $d['raiten_y'], (int) $d['raiten_m'], (int) $d['raiten_d'],
            $hour >= 0 && $hour <= 23 ? $hour : 0, $minute >= 0 && $minute <= 59 ? $minute : 0, 0, 'Asia/Tokyo');
    }

    /**
     * ポイントログ新規 (solo si hay 会計 > 0): 使用ポイント limitado por el tope de la tienda, el saldo y el 会計;
     * 獲得 = floor(importe × 還元率 / 100) sobre el 会計 o el 差引 (keisan_flg); 手動 con 減算 sin dejar el saldo negativo.
     */
    private function pointMovement(Customer $customer, array $d, int $shopCd, ?Staff $staff, CarbonInterface $now): ?int
    {
        $kaikei = (int) ($d['kaikei'] ?? 0);
        if ($kaikei <= 0) {
            return null;
        }

        $shouhi = (int) ($d['shouhi_point'] ?? 0);
        $sounyu = (int) ($d['sounyu_point'] ?? 0);
        $houhou = (string) ($d['shiharai_houhou'] ?? '');

        $balance = DB::table('tb_customer_point')->where('tb_customer_cd', $customer->cd)->lockForUpdate()->first();
        $current = (int) ($balance->service_point ?? 0);
        $shop = $this->shopPointConfig($shopCd);

        $shouhi = min($shouhi, min($shop['shiyou_point_jyougen'], $current), $kaikei);
        $sashihiki = $kaikei - $shouhi;
        $percent = $houhou === '2' ? $shop['point_percentage_card'] : $shop['point_percentage_genkin'];
        $amount = $shop['keisan_flg'] === '2' ? $kaikei : $sashihiki;
        $jidou = (int) floor($amount * $percent / 100);

        if (($d['sounyu_point_add_remove_flg'] ?? '') === '2' && $sounyu > 0) {
            $temp = $current - $shouhi + $jidou;
            $sounyu = $temp > $sounyu ? -$sounyu : -$temp;
        }

        $log = PointLog::create([
            'tb_shop_cd' => $shopCd,
            'tb_staff_cd' => $staff?->cd ?: null,
            'tb_customer_cd' => $customer->cd,
            'tb_terminal_cd' => -1,
            'touroku_date' => $now->getTimestamp(),
            'shori_mae_point' => $current,
            'kaikei' => $kaikei,
            'sashihiki_kaikei' => $sashihiki,
            'shiharai_houhou' => $houhou,
            'percentage' => $percent,
            'shouhi_point' => $shouhi,
            'jidou_point' => $jidou,
            'sounyu_point' => $sounyu,
            'meisai_mail' => (string) ($d['meisai_mail'] ?? ''),
            'raiten_shori' => '1',
        ]);

        $newPoints = $current - $shouhi + $jidou + $sounyu;
        // El legacy siempre renovaba la caducidad aquí (point_shikkou_sousa quedaba en '1' al leer una variable no definida)
        $expires = $this->points->expiryFrom($now);

        if ($balance) {
            DB::table('tb_customer_point')->where('tb_customer_cd', $customer->cd)
                ->update(['service_point' => $newPoints, 'point_yukoukigen' => $expires]);
        } else {
            DB::table('tb_customer_point')->insert([
                'cd' => $customer->cd, 'tb_customer_cd' => $customer->cd, 'service_point' => $newPoints,
                'point_yukoukigen' => $expires, 'touroku_date' => (string) $now->getTimestamp(),
            ]);
        }

        return (int) $log->cd;
    }

    /**
     * スタンプログ新規 (solo si hay 会計 > 0): 獲得 = floor(会計 / 還元) × multiplicador de la regla del momento de la
     * visita, sin pasar del máximo; 手動 suma (hasta el máximo) o resta (sin bajar de 0). action_flg '8' (カルテ入力).
     */
    private function stampMovement(Customer $customer, int $current, array $d, int $shopCd, ?Staff $staff, CarbonInterface $at, CarbonInterface $now): ?int
    {
        $kaikei = (int) ($d['kaikei'] ?? 0);
        if ($kaikei <= 0) {
            return null;
        }

        $sounyu = (int) ($d['sounyu_point'] ?? 0);
        $houhou = (string) ($d['shiharai_houhou'] ?? '');
        $config = $this->stamps->config($now);
        $max = $config['stamp_max'];
        $shop = $this->shopStampConfig($shopCd, $at);

        $percent = $houhou === '2' ? $shop['stamp_percentage_card'] : $shop['stamp_percentage_genkin'];
        $jidou = ($percent ? (int) floor($kaikei / $percent) : 0) * $shop['stamp_count'];
        if ($current + $jidou > $max) {
            $jidou = $max - $current;
        }

        $temp = $current + $jidou;
        if (($d['sounyu_point_add_remove_flg'] ?? '') === '2') {
            $sounyu = $temp > $sounyu ? -$sounyu : -$temp;
        } elseif ($max < $temp + $sounyu) {
            $sounyu = $max - $temp;
        }

        $log = $this->stamps->log([
            'tb_shop_cd' => $shopCd,
            'tb_staff_cd' => $staff?->cd ?: null,
            'tb_customer_cd' => $customer->cd,
            'tb_terminal_cd' => -1,
            'shori_mae_stamp' => $current,
            'kaikei' => $kaikei,
            'shiharai_houhou' => $houhou,
            'percentage' => $percent,
            'jidou_stamp' => $jidou,
            'sounyu_stamp' => $sounyu,
            'meisai_mail' => (string) ($d['meisai_mail'] ?? ''),
            'raiten_shori' => '1',
            'action_flg' => '8',
        ], $now);

        $this->stamps->setCount($customer, min($current + $jidou + $sounyu, $max), $config['stamp_yukoukigen'], signup: true, at: $now);

        return (int) $log->cd;
    }

    /** カスタマイズUPDATE: borra los campos del grupo y los vuelve a crear (createform_sql) */
    private function saveFields(Customer $customer, array $d, int $cateCd, array $fields, string $group, int $recordingTime, int $hatsubanShop): void
    {
        $cateName = DB::table('tb_search_cate')->where('cd', $cateCd)->value('name');
        if ($cateName === null && ! DB::table('tb_search_cate')->where('cd', $cateCd)->exists()) {
            return;
        }

        DB::table('tb_customer_sub')
            ->where('tb_search_cate_cd', $cateCd)->where('tb_customer_cd', $customer->cd)
            ->where('category_inner_group', $group)->delete();

        $updateTime = (string) time();
        foreach ($fields as $form) {
            DB::table('tb_customer_sub')->insert($this->registration->customFieldValues($form, $d) + [
                'cd' => $this->registration->uniqueCd($hatsubanShop),
                'tb_customer_cd' => $customer->cd,
                'tb_search_cate_cd' => $cateCd,
                'tb_search_form_cd' => $form['cd'],
                'form_title' => $cateName . '<>' . $form['name'],
                'form_type' => '1',
                'disp_flg' => '1',
                'touroku_date' => (string) $recordingTime,
                'update_date' => $updateTime,
                'category_inner_group' => $group,
            ]);
        }
    }
}
