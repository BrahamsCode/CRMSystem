<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Models\AdminMaster;
use App\Models\Promotions\StampConfig;
use App\Services\Promotions\LegacyTime;
use App\Services\Promotions\StaffContext;
use App\Support\LegacyText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * スタンプ仕様管理 (category3/stamp/stamp_config.php + stamp_config_register.php): varios formularios, cada uno
 * con su `act`, que escriben tb_stamp_config (intervalo por tienda, máximo y bonus en la fila de tb_shop_cd = 1),
 * admin_master (caducidad, horas, aviso previo, 来店時のスタンプ) y tb_shop (還元). Tras guardar vuelve con
 * `end=1#<ancla>` como el legacy. AJAX ajax_stamp_shikkou_kakunin.php: afectados al activar la caducidad.
 * El legacy rehacía el crontab al cambiar la caducidad o sus horas; aquí el Scheduler lee admin_master en cada pasada.
 */
class StampConfigController extends Controller
{
    /** Intervalos del selector 間隔: 30分, 1〜24時間 y テスト (0 s) */
    public const INTERVALS = [1800 => '30分', 3600 => '1時間', 7200 => '2時間', 10800 => '3時間', 14400 => '4時間', 18000 => '5時間',
        21600 => '6時間', 25200 => '7時間', 28800 => '8時間', 32400 => '9時間', 36000 => '10時間', 39600 => '11時間', 43200 => '12時間',
        46800 => '13時間', 50400 => '14時間', 54000 => '15時間', 57600 => '16時間', 61200 => '17時間', 64800 => '18時間', 68400 => '19時間',
        72000 => '20時間', 75600 => '21時間', 79200 => '22時間', 82800 => '23時間', 86400 => '24時間', 0 => 'テスト'];

    /** マイページ表示スタンプ数 (999999999 = 無制限) */
    public const MAX_OPTIONS = ['5個' => 5, '10個' => 10, '20個' => 20, '30個' => 30, '40個' => 40, '50個' => 50, '60個' => 60, '75個' => 75,
        '100個' => 100, '200個' => 200, '300個' => 300, '400個' => 400, '500個' => 500, '無制限' => 999999999];

    /** Acciones de un solo campo de admin_master */
    private const SIMPLE = [
        'change_raiten_stamp_flg' => 'raiten_stamp_flg',
        'change_stamp_shikkou_kikan' => 'stamp_shikkou_kikan',
        'change_stamp_update_type_flg' => 'stamp_update_type_flg',
    ];

    public function __construct(private readonly StaffContext $staff) {}

    public function edit(Request $request): View
    {
        $shops = $this->staff->shops();

        // Cada tienda tiene su fila en tb_stamp_config; si falta se crea con 1800 s (30分), como el legacy
        $intervals = [];
        foreach ($shops as $shop) {
            $row = DB::table('tb_stamp_config')->where('tb_shop_cd', $shop->cd)->first();
            if (! $row) {
                StampConfig::create(['tb_shop_cd' => $shop->cd, 'limit_flg' => '1800']);
            }
            $intervals[$shop->cd] = ! $row || (string) $row->limit_flg === '' ? 1800 : (int) $row->limit_flg;
        }

        return view('admin.promocion.stamp.stamp_config', [
            'shops' => $shops,
            'intervals' => $intervals,
            'general' => (array) DB::table('tb_stamp_config')->where('tb_shop_cd', 1)->first(),
            'admin' => AdminMaster::current()->getAttributes(),
            'isMaster' => $this->staff->isMaster(),
            'isDevelop' => Gate::allows('nucleo.developer'),
            'isTablet' => PointConfigController::isTablet($request),
            'stampAccounting' => DB::table('tb_base_system_grandchild')->where('system_name', 'スタンプ会計処理')->where('system_flg', '1')->exists(),
            'rateShops' => DB::table('tb_shop')->where('disp_flg', '1')->orderBy('cd')->get(['cd', 'kaishamei', 'stamp_percentage_genkin', 'stamp_percentage_card']),
            'ended' => (string) $request->input('end') === '1',
        ]);
    }

    /** stamp_config_register.php: aplica el `act` y vuelve a la pantalla con end=1 y el ancla del formulario */
    public function update(Request $request): RedirectResponse
    {
        $act = (string) $request->input('act', '');
        $value = fn (string $key) => LegacyText::db($request->input($key, ''));

        if (isset(self::SIMPLE[$act])) {
            $this->admin([self::SIMPLE[$act] => $value(self::SIMPLE[$act])]);
        }

        match ($act) {
            'up' => $this->updateBehaviour($request),
            'change_stamp_shikkou_sousa' => $this->updateExpiryUse($value('stamp_shikkou_sousa')),
            'change_stamp_shikkou_time' => $this->admin(['stamp_shikkou_time' => $value('stamp_shikkou_time_h').':'.$value('stamp_shikkou_time_m')]),
            'change_stamp_shikkou_alert' => $this->admin([
                'stamp_shikkou_alert' => $value('stamp_shikkou_alert'),
                'stamp_shikkou_alert_time' => $value('stamp_shikkou_alert_time_h').':'.$value('stamp_shikkou_alert_time_m'),
            ]),
            'change_stamp_settings' => $this->updateRates((array) $request->input('stamp_settings', [])),
            default => null,
        };

        $anchor = preg_replace('/[^A-Za-z0-9_\-]/', '', (string) $request->input('anchor_id', ''));

        return redirect()->to(route('admin.promocion.stamps.config', ['end' => 1]).($anchor !== '' ? '#'.$anchor : ''));
    }

    /** ajax_stamp_shikkou_kakunin.php: "ng" si la caducidad ya está activa; si no, cuántos usuarios se verán afectados */
    public function expiryPreview(): Response
    {
        $admin = AdminMaster::current();
        if ((string) $admin->stamp_shikkou_sousa === '1') {
            return response('ng');
        }
        $days = (string) $admin->stamp_shikkou_kikan === '' ? 365 : (int) $admin->stamp_shikkou_kikan;
        $count = DB::table('tb_customer')->where('stamp_count', '>', 0)->where('disp_flg', '1')->where('touroku_kubun', '2')->count();
        $limit = Carbon::now(LegacyTime::ZONE)->addDays($days)->format('Y/m/d');

        return response("有効アカウント{$count}人(スタンプを1個以上保有するユーザー)に対して\n<span style=\"color:#FF0000;\">有効期限{$limit}を設定します。</span>");
    }

    // ------------------------------------------------------------------

    private function admin(array $values): void
    {
        DB::table('admin_master')->where('cd', AdminMaster::ROW_CD)->update($values);
    }

    /** スタンプ動作設定: 間隔 por tienda; máximo, bonus y formato de クーポン solo los envía el master */
    private function updateBehaviour(Request $request): void
    {
        foreach ((array) $request->input('kankaku', []) as $shopCd => $seconds) {
            if (is_numeric($shopCd)) {
                DB::table('tb_stamp_config')->where('tb_shop_cd', (int) $shopCd)
                    ->update(['limit_flg' => (string) (int) $seconds, 'update_date' => (string) time()]);
            }
        }
        DB::table('tb_stamp_config')->where('tb_shop_cd', 1)->update([
            'stamp_max' => (int) $request->input('stamp_max'),
            'touroku_bonus' => (int) $request->input('touroku_bonus'),
        ]);
        $this->admin(['mypage_stamp_hyouji_flg' => LegacyText::db($request->input('mypage_stamp_hyouji_flg', ''))]);
    }

    /** Activar la caducidad ('1') renueva la de los clientes con sellos: hoy + (periodo + 1) días − 1 s */
    private function updateExpiryUse(string $flag): void
    {
        $this->admin(['stamp_shikkou_sousa' => $flag]);
        if ($flag === '1') {
            $days = (string) AdminMaster::current()->stamp_shikkou_kikan;
            $days = $days === '' ? 365 : (int) $days;
            $limit = Carbon::now(LegacyTime::ZONE)->startOfDay()->addDays($days + 1)->getTimestamp() - 1;
            DB::table('tb_customer')->where('stamp_count', '>', 0)->where('touroku_kubun', '2')->where('disp_flg', '1')
                ->update(['stamp_yukoukigen' => $limit]);
        }
    }

    /** 各店舗ごとのスタンプ還元設定: [cd, 現金, カード]; vacíos → 1000 */
    private function updateRates(array $rows): void
    {
        foreach ($rows as $row) {
            $row = array_values((array) $row);
            DB::table('tb_shop')->where('cd', (int) ($row[0] ?? 0))->update([
                'stamp_percentage_genkin' => (float) (($row[1] ?? '') !== '' ? $row[1] : 1000),
                'stamp_percentage_card' => (float) (($row[2] ?? '') !== '' ? $row[2] : 1000),
            ]);
        }
    }
}
