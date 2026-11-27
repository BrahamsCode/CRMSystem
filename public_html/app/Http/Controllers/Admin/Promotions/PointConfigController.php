<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Models\AdminMaster;
use App\Services\Promotions\LegacyTime;
use App\Services\Promotions\StaffContext;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * ポイント仕様管理 (category3/point/point_config.php): una pantalla con varios formularios, cada uno con su `act`;
 * escribe admin_master, tb_shop (incluidas las configuraciones serializadas de マイコレ), tb_customer_group y
 * point_shikkou_alert. AJAX ajax_point_yukoukigen_kakunin.php (usuarios afectados al activar la caducidad).
 * El legacy rehacía el crontab tras algunos cambios; aquí el Scheduler lee la configuración en cada pasada.
 */
class PointConfigController extends Controller
{
    /** Acciones de un solo campo de admin_master */
    private const SIMPLE = [
        'change_coming_use_beacon' => 'coming_use_beacon',
        'change_point_shikkou_sousa' => 'point_shikkou_sousa',
        'change_point_shikkou_kikan' => 'point_shikkou_kikan',
    ];

    public function __construct(private readonly StaffContext $staff) {}

    public function edit(Request $request): View
    {
        $act = (string) $request->input('act', '');
        if ($act !== '') {
            $this->update($act, $request);
        }

        $this->ensureAlertRows();
        $admin = AdminMaster::current()->getAttributes();

        return view('admin.promocion.point.point_config', [
            'admin' => $admin,
            'alerts' => DB::table('point_shikkou_alert')->where('flg', '1')->orderBy('sort')->orderBy('cd')->get()
                ->map(function ($row) {
                    [$row->alert_time_h, $row->alert_time_m] = array_pad(explode(':', (string) $row->alert_time), 2, null);

                    return $row;
                })->values(),
            'shops' => DB::table('tb_shop')->where('disp_flg', '1')->orderBy('cd')
                ->get(['cd', 'kaishamei', 'keisan_flg', 'shiyou_point_jyougen', 'point_percentage_genkin', 'point_percentage_card',
                    'login_point_settings', 'introduce_settings', 'customer_registration_settings', 'login_default_point_settings'])
                ->map(function ($shop) {
                    foreach (['login_point_settings', 'introduce_settings', 'customer_registration_settings', 'login_default_point_settings'] as $col) {
                        $shop->{$col} = self::unserialize($shop->{$col});
                    }

                    return $shop;
                }),
            'groups' => DB::table('tb_customer_group')->where('disp_flg', '1')->orderBy('rank')
                ->get(['cd', 'name', 'keisan_flg', 'shiyou_point_jyougen', 'point_percentage_genkin', 'point_percentage_card']),
            'paidMember' => self::unserialize($admin['paid_member_settings'] ?? null),
            'stampEnabled' => DB::table('tb_base_system_child')->where('system_name', 'スタンプ管理')->where('tb_base_system_cd', 3)->where('system_flg', '1')->exists(),
            'pointEnabled' => DB::table('tb_base_system_grandchild')->where('system_name', 'ポイント管理')->where('tb_base_system_cd', 1)->where('system_flg', '1')->exists(),
            'paidMemberEnabled' => DB::table('contents_master')->where('category', 'mycolle')->where('field', 'app_layout_mode')->where('disp_flg', '1')->where('text_data', '1')->exists(),
        ]);
    }

    /** ajax_point_yukoukigen_kakunin.php: "ng" si la caducidad ya está activa; si no, cuántos usuarios se verán afectados */
    public function expiryPreview(): Response
    {
        $admin = AdminMaster::current();
        if ((string) $admin->point_yukoukigen_flg !== '2') {
            return response('ng');
        }
        $days = (string) $admin->point_shikkou_kikan === '' ? 365 : (int) $admin->point_shikkou_kikan;
        $count = DB::table('tb_customer as tb1')->leftJoin('tb_customer_point as tb2', 'tb1.cd', '=', 'tb2.tb_customer_cd')
            ->whereRaw('tb2.service_point::int > 0')->whereNotNull('tb2.service_point')
            ->where('tb1.disp_flg', '1')->where('tb1.touroku_kubun', '2')->count();
        $limit = Carbon::now(LegacyTime::ZONE)->addDays($days)->format('Y/m/d');

        return response("有効アカウント{$count}人(ポイントを1個以上保有するユーザー)に対して\n<span style=\"color:#FF0000;\">有効期限{$limit}を設定します。</span>");
    }

    // ------------------------------------------------------------------

    private function update(string $act, Request $request): void
    {
        if (isset(self::SIMPLE[$act])) {
            $this->admin([self::SIMPLE[$act] => (string) $request->input(self::SIMPLE[$act], '')]);

            return;
        }

        match ($act) {
            'change_point_shikkou_time' => $this->admin(['point_shikkou_time' => $request->input('point_shikkou_time_h').':'.$request->input('point_shikkou_time_m')]),
            'change_point_shikkou_alert' => $this->updateAlerts($request),
            'change_point_yukoukigen_flg' => $this->updateExpiryFlag($request),
            'change_point_settings' => $this->updateRates('tb_shop', (array) $request->input('point_settings', [])),
            'change_group_point_settings' => $this->updateRates('tb_customer_group', (array) $request->input('point_settings', [])),
            'change_login_point_settings' => $this->updateMycolle($request),
            'change_paid_member_point_settings' => $request->has('paid_member_settings') ? $this->admin(['paid_member_settings' => serialize([
                'points' => (int) $request->input('paid_member_settings.points'),
                'months' => (int) $request->input('paid_member_settings.months'),
                'day' => (int) $request->input('paid_member_settings.day'),
            ])]) : null,
            default => null,
        };
    }

    private function admin(array $values): void
    {
        DB::table('admin_master')->where('cd', AdminMaster::ROW_CD)->update($values);
    }

    /** 1. el aviso principal en admin_master; 2〜10. cada fila se cierra (flg 0) y se crea otra con los mismos textos */
    private function updateAlerts(Request $request): void
    {
        $this->admin([
            'point_shikkou_alert' => (string) $request->input('point_shikkou_alert', ''),
            'point_shikkou_alert_time' => $request->input('point_shikkou_alert_time_h').':'.$request->input('point_shikkou_alert_time_m'),
        ]);

        DB::transaction(function () use ($request) {
            foreach ((array) $request->input('point_shikkou_alert_cd', []) as $key => $cd) {
                // cd bigint de 19 cifras: se compara como texto validado (un float perdería precisión)
                $row = preg_match('/^[1-9][0-9]*$/', (string) $cd) ? DB::table('point_shikkou_alert')->where('cd', (string) $cd)->first() : null;
                if (! $row) {
                    continue;
                }
                DB::table('point_shikkou_alert')->where('cd', $row->cd)->update(['flg' => '0']);
                DB::table('point_shikkou_alert')->insert([
                    'cd' => $this->hatsuban(),
                    'tb_shop_cd' => (int) $this->staff->shopCd(),
                    'flg' => '1',
                    'touroku_date' => time(),
                    'title' => $row->title,
                    'message' => $row->message,
                    'alert_day' => (string) $request->input("point_shikkou_alert_days.$key", ''),
                    'alert_time' => $request->input("point_shikkou_alert_times_h.$key").':'.$request->input("point_shikkou_alert_times_m.$key"),
                    'sort' => (int) $key,
                ]);
            }
        });
    }

    /** Activar la caducidad renueva la de todos los saldos: hoy + (periodo + 1) días − 1 s */
    private function updateExpiryFlag(Request $request): void
    {
        $flag = (string) $request->input('point_yukoukigen_flg', '');
        $this->admin(['point_yukoukigen_flg' => $flag]);
        if ($flag !== '2') {
            $days = (string) AdminMaster::current()->point_shikkou_kikan;
            $days = $days === '' ? 365 : (int) $days;
            $limit = Carbon::now(LegacyTime::ZONE)->startOfDay()->addDays($days + 1)->getTimestamp() - 1;
            DB::table('tb_customer_point')->where('service_point', '>', 0)->update(['point_yukoukigen' => $limit]);
        }
    }

    /** ポイントルール por tienda o grupo: [cd, 上限, 現金%, カード%, 使用ポイントを含む]; vacíos → 1000 / 1 / 1 */
    private function updateRates(string $table, array $rows): void
    {
        foreach ($rows as $row) {
            $row = array_values((array) $row);
            DB::table($table)->where('cd', (int) ($row[0] ?? 0))->update([
                'keisan_flg' => ($row[4] ?? '') !== '' ? '2' : '1',
                'shiyou_point_jyougen' => (float) (($row[1] ?? '') !== '' ? $row[1] : 1000),
                'point_percentage_genkin' => (float) (($row[2] ?? '') !== '' ? $row[2] : 1),
                'point_percentage_card' => (float) (($row[3] ?? '') !== '' ? $row[3] : 1),
            ]);
        }
    }

    /**
     * マイコレ設定: por tienda, login_point_settings, login_default_point_settings (solo con ビーコン),
     * introduce_settings y customer_registration_settings como array PHP serializado (los leen la app y S5).
     * El legacy machacaba login_default_point_settings con ceros si el bloque no estaba en pantalla; aquí se conserva.
     */
    private function updateMycolle(Request $request): void
    {
        foreach ((array) $request->input('login_point_settings', []) as $i => $settings) {
            $settings = (array) $settings;
            $settings['shopCd'] = (int) ($settings['shopCd'] ?? 0);
            $settings['earnPoints'] = (int) ($settings['earnPoints'] ?? 0);
            $settings['title'] = htmlentities((string) ($settings['title'] ?? ''), ENT_QUOTES, 'UTF-8');
            $settings['body'] = htmlentities((string) ($settings['body'] ?? ''), ENT_QUOTES, 'UTF-8');

            $introduce = (array) $request->input("introduce_settings.$i", []);
            $introduce['shopCd'] = (int) ($introduce['shopCd'] ?? 0);
            $introduce['introducePoints'] = (int) ($introduce['introducePoints'] ?? 0);
            $introduce['registrationPoints'] = (int) ($introduce['registrationPoints'] ?? 0);
            $introduce['body'] = htmlentities((string) ($introduce['body'] ?? ''), ENT_QUOTES, 'UTF-8');

            $registration = (array) $request->input("customer_registration_settings.$i", []);
            $registration['shopCd'] = (int) ($registration['shopCd'] ?? 0);
            $registration['body'] = htmlentities((string) ($registration['body'] ?? ''), ENT_QUOTES, 'UTF-8');
            $registration['days'] = max(0, (int) ($registration['days'] ?? 0));
            if (! $registration['days']) {
                $registration['hours'] = $registration['minutes'] = '';
            }

            $values = [
                'login_point_settings' => serialize($settings),
                'introduce_settings' => serialize($introduce),
                'customer_registration_settings' => serialize($registration),
            ];
            if ($request->has("login_default_point_settings.$i")) {
                $default = (array) $request->input("login_default_point_settings.$i");
                $default['shopCd'] = (int) ($default['shopCd'] ?? 0);
                $default['earnPoints'] = (int) ($default['earnPoints'] ?? 0);
                $default['body'] = htmlentities((string) ($default['body'] ?? ''), ENT_QUOTES, 'UTF-8');
                $values['login_default_point_settings'] = serialize($default);
            }

            DB::table('tb_shop')->where('cd', $settings['shopCd'])->update($values);
        }
    }

    /** Si point_shikkou_alert está vacía, crea las 9 filas de avisos 2〜10 */
    private function ensureAlertRows(): void
    {
        if (DB::table('point_shikkou_alert')->exists()) {
            return;
        }
        DB::transaction(function () {
            foreach (range(0, 8) as $sort) {
                DB::table('point_shikkou_alert')->insert(['cd' => $this->hatsuban(), 'tb_shop_cd' => (int) $this->staff->shopCd(), 'flg' => '1', 'touroku_date' => time(), 'sort' => $sort]);
            }
        });
    }

    /** createHatsuban(): epoch + tienda (3 cifras) + aleatorio (6 cifras) */
    private function hatsuban(): string
    {
        return time().sprintf('%03s', (int) $this->staff->shopCd()).sprintf('%06d', random_int(0, 999999));
    }

    /** Array PHP serializado de la BD (sin objetos) o [] */
    public static function unserialize(?string $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        $value = @unserialize($raw, ['allowed_classes' => false]);

        return is_array($value) ? $value : [];
    }
}
