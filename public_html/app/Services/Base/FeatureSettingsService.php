<?php

namespace App\Services\Base;

use App\Models\ContentsMaster;
use App\Support\LegacyText;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 機能設定 (system/category0/config/features_config.php): cada bloque de la pantalla es un formulario con su
 * `act`, que guarda en `admin_master` (cd = 1), `contents_master` o `tb_customer` sin pantalla de confirmación.
 */
class FeatureSettingsService
{
    /** Sonidos del 端末音声 (el índice 6 no se ofrece en los desplegables) */
    public const SOUNDS = ['sound1', 'sound2', 'sound3', 'sound4', 'ありがとうございます', 'いらっしゃいませ', 'クーポンのご利用ありがとうございます'];

    public const MAIL_FOOTERS = ['住所', '電話番号', 'メールアドレス', 'URL'];

    /** Bloques con su `act`; los que exigen `nucleo.developer` se comprueban en el controlador */
    public const ACTS = [
        'change_name_input_mode_flg', 'change_reserve_button_position', 'change_shop_category_flg', 'change_terminal_sound',
        'change_mail_footer', 'change_shuki_limit', 'change_mail_send_filter', 'change_terminal_logout_time',
        'change_id_raiten_type', 'change_id_smtp', 'privacy_policy_flg', 'mail_send_status', 'disabled_push_title_flg',
        'mobileshop_auth_passcode', 'reset_error_send_cnt', 'change_sended_log',
    ];

    public function apply(string $act, Request $request): void
    {
        $in = fn (string $key): string => LegacyText::db($request->input($key));

        match ($act) {
            'change_name_input_mode_flg' => ContentsMaster::putLegacy('tb_c1_customer', 'name_input_mode_flg', $in('name_input_mode_flg'), false),
            'change_reserve_button_position' => ContentsMaster::putLegacy('tb_c1_customer', 'reserve_button_position', $in('reserve_button_position'), false),
            'change_shop_category_flg' => $this->admin(['shop_category_flg' => (int) $in('shop_category_flg')]),
            'change_terminal_sound' => $this->terminalSound($request),
            'change_mail_footer' => $this->admin(['mail_footer' => implode('<>', array_map(
                fn (int $i) => isset($request->input('mail_footer', [])[$i]) ? '' : '9', range(0, 3)))]),
            'change_shuki_limit' => $this->shukiLimit((int) $in('shuki_limit1'), (int) $in('shuki_limit2')),
            'change_mail_send_filter' => $this->admin(['mail_send_filter' => $in('mail_send_filter')]),
            'change_terminal_logout_time' => $this->admin(['terminal_logout_time' => $in('terminal_logout_time')]),
            'change_id_raiten_type' => $this->admin(['id_raiten_type' => $in('id_raiten_type')]),
            'change_id_smtp' => $this->admin([
                'smtp_domain' => $in('smtp_domain'), 'smtp_port' => $in('smtp_port'),
                'smtp_login_id' => $in('smtp_login_id'), 'smtp_login_pass' => $in('smtp_login_pass'),
            ]),
            'privacy_policy_flg' => ContentsMaster::putLegacy('mypage', 'privacy_policy_text', $in('privacy_policy_text'), true),
            'mail_send_status' => ContentsMaster::putLegacy('tb_c9_yoyaku', 'mail_send_status', $in('mail_send_status'), true),
            'disabled_push_title_flg' => ContentsMaster::putLegacy('mycolle', 'disabled_push_title_flg', $in('disabled_push_title_flg'), true),
            'mobileshop_auth_passcode' => $this->passcodes((array) $request->input('shop_cd', []), (array) $request->input('mobileshop_auth_passcode', [])),
            'reset_error_send_cnt' => $this->resetErrorSendCount(),
            'change_sended_log' => $this->admin(['mailmag_sended_log' => (int) $in('sendedLogFlg') . '<>' . min((int) $in('maxSendedLog'), 1500)]),
            default => null,
        };
    }

    /**
     * Datos de la pantalla. `strict` = lectura por la fila más reciente sin filtrar disp_flg (privacy, reservas,
     * push), como el legacy; el resto lee la fila con disp_flg = '1'.
     */
    public function viewData(): array
    {
        $admin = (array) (DB::table('admin_master')->where('cd', 1)->first() ?? []);
        $get = fn (string $key) => $admin[$key] ?? null;

        $sound = (string) $get('sound_select') !== '' ? explode('<>', (string) $get('sound_select')) : [2, 3, 4];
        $footer = (string) $get('mail_footer') !== '' ? explode('<>', (string) $get('mail_footer')) : array_fill(0, 4, '');
        [$shuki1, $shuki2] = (string) $get('shuki_limit') === '' ? ['20', '100'] : array_pad(explode('<>', (string) $get('shuki_limit')), 2, null);
        [$sendedLogFlg, $maxSendedLog] = array_pad(explode('<>', (string) $get('mailmag_sended_log')), 2, null);

        $appLayoutMode = ContentsMaster::text('mycolle', 'app_layout_mode');

        return [
            'admin' => $admin,
            'nameInputModeFlg' => ContentsMaster::text('tb_c1_customer', 'name_input_mode_flg'),
            'reserveButtonPosition' => ContentsMaster::text('tb_c1_customer', 'reserve_button_position'),
            'soundSelect' => $sound,
            'mailFooter' => $footer,
            'showShuki' => $this->childEnabled('来店履歴管理'),
            'shuki1' => $shuki1,
            'shuki2' => $shuki2,
            'sendedLogFlg' => $sendedLogFlg,
            'maxSendedLog' => (int) $maxSendedLog,
            'privacyPolicyText' => $this->latest('mypage', 'privacy_policy_text'),
            'mailSendStatus' => $this->latest('tb_c9_yoyaku', 'mail_send_status'),
            'showPushTitle' => (int) $get('application_use_flg') === 1,
            'disabledPushTitleFlg' => $this->latest('mycolle', 'disabled_push_title_flg'),
            'mobileshopShops' => $appLayoutMode === '1' ? $this->shopsWithPasscode() : null,
            'showTerminal' => $this->childEnabled('スタンプ管理')
                || DB::table('tb_base_system_grandchild')->where('system_name', 'ポイント管理')->where('tb_base_system_cd', 1)->where('system_flg', '1')->exists(),
            'terminalLogoutTime' => (string) $get('terminal_logout_time') === '' ? 30 : $get('terminal_logout_time'),
        ];
    }

    private function admin(array $values): void
    {
        DB::table('admin_master')->where('cd', 1)->update($values);
    }

    /** Solo si llega sound_select (array); si llega como texto el legacy guarda NULL */
    private function terminalSound(Request $request): void
    {
        if ($request->has('sound_select')) {
            $value = $request->input('sound_select');
            $this->admin(['sound_select' => is_array($value) ? implode('<>', array_map([LegacyText::class, 'db'], $value)) : null]);
        }
    }

    /**
     * 来店周期のフォローメール: guarda "min<>max" (0 = valor por defecto 20/100, o 1 si solo hay máximo) y reescribe
     * el filtro de los envíos de seguimiento por ciclo (mailmag_master con send_parameter[1] = '4').
     */
    private function shukiLimit(int $low, int $high): void
    {
        [$lo, $hi] = match (true) {
            $low === 0 && $high === 0 => [20, 100],
            $low === 0 && $high > 0 => [1, $high],
            $low > 0 && $high === 0 => [$low, 100],
            default => [$low, $high],
        };

        DB::transaction(function () use ($lo, $hi) {
            $this->admin(['shuki_limit' => $lo . '<>' . $hi]);

            $rows = DB::table('mailmag_master')->select('cd', '振分配信')->where('type_flg', 'like', 'follow%')
                ->whereRaw("SPLIT_PART(send_parameter, '<>', 2) = '4'")->orderBy('cd')->get();
            foreach ($rows as $row) {
                $current = (string) $row->{'振分配信'};
                $replace = 'AND shuki_heikin >= ' . $lo . ' AND shuki_heikin <= ' . $hi;
                $pos = strpos($current, 'AND shuki_heikin >=');
                $value = $pos !== false ? substr($current, 0, $pos) . $replace : $current . ' ' . $replace;
                DB::table('mailmag_master')->where('cd', $row->cd)->update(['振分配信' => $value]);
            }
        });
    }

    private function latest(string $category, string $field): ?string
    {
        return DB::table('contents_master')->where('category', $category)->where('field', $field)
            ->orderByDesc('update_date')->value('text_data');
    }

    private function childEnabled(string $name): bool
    {
        return DB::table('tb_base_system_child')->where('system_name', $name)->where('tb_base_system_cd', 3)->where('system_flg', '1')->exists();
    }

    /** Tiendas activas con su パスコード de モバイルショップ (fila más reciente por update_date) */
    private function shopsWithPasscode(): array
    {
        return DB::table('tb_shop')->select('cd', 'kaishamei')->where('disp_flg', '1')->orderBy('cd')->get()
            ->map(function ($shop) {
                $shop->mobileshop_auth_passcode = DB::table('contents_master')->where('category', 'mobileshop')
                    ->where('field', 'mobileshop_auth_passcode')->where('int_data', $shop->cd)
                    ->orderByDesc('update_date')->value('text_data');

                return $shop;
            })->all();
    }

    /**
     * Pasa la パスコード de cada tienda: compara con la última fila (por cd), inserta si no hay ninguna y actualiza
     * si cambió; vaciarla solo actualiza si había una. Valor con form2Db + htmlspecialchars, como el legacy.
     */
    private function passcodes(array $shopCds, array $passcodes): void
    {
        foreach (array_values($shopCds) as $i => $shopCd) {
            $cd = htmlspecialchars(LegacyText::db($shopCd));
            $pass = htmlspecialchars(LegacyText::db($passcodes[$i] ?? ''));
            if (! preg_match('/^[0-9]+$/', $cd)) {
                continue;
            }
            $rows = fn () => DB::table('contents_master')->where('category', 'mobileshop')
                ->where('field', 'mobileshop_auth_passcode')->where('int_data', (int) $cd);
            $server = $rows()->orderByDesc('cd')->value('text_data');

            if ($server === null && $pass !== '') {
                ContentsMaster::create([
                    'category' => 'mobileshop', 'field' => 'mobileshop_auth_passcode', 'text_data' => $pass,
                    'disp_flg' => '1', 'hyouji_flg' => '1', 'int_data' => (int) $cd,
                ]);
            } elseif ($server !== null && $server !== $pass) {
                $rows()->where('disp_flg', '1')->where('hyouji_flg', '1')->update(['text_data' => $pass, 'update_date' => time()]);
            }
        }
    }

    /** 不達配信数のリセット: error_send_cnt = 0 y メルマガ区分 不達配信(9) → 配信あり(1), solo socios activos */
    private function resetErrorSendCount(): void
    {
        DB::transaction(function () {
            $members = fn () => DB::table('tb_customer')->where('touroku_kubun', '2')->where('disp_flg', '1');
            $members()->update(['error_send_cnt' => 0]);
            $members()->where('mailmaga_flg', '9')->update(['mailmaga_flg' => '1']);
        });
    }
}
