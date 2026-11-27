<?php

namespace App\Http\Controllers\Publico\Integraciones\Appview;

use App\Http\Controllers\Controller;
use App\Services\Customers\CustomerRegistrationService;
use App\Services\Integraciones\Appview\AppCustomer;
use App\Services\Integraciones\Appview\AppviewDomain;
use App\Services\Integraciones\Appview\AppviewPush;
use App\Services\Promotions\PointService;
use App\Services\Integraciones\ToolApi\ToolApiOutput as Out;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * appview: alta rápida de socio desde la app (member_kiyaku → member_comfirm → member_complete).
 */
class MemberRegistrationController extends Controller
{
    use RendersAppview;

    /** Clave de sesión del token de member_comfirm.php ($_SESSION["customer_regist"]) */
    public const SESSION_TOKEN = 'appview.customer_regist';

    /**
     * member_kiyaku.php: valida nombre y teléfono (solo dígitos) y muestra el 利用規約 con el nombre de la primera
     * tienda activa. La メールアドレス nunca se validaba (variable sin asignar en el legacy): se mantiene.
     */
    public function terms(Request $request): Response
    {
        $name = $request->input('name');
        $tel = $request->input('tel');

        $errors = '';
        if ($name === null || $name === '') {
            $errors .= '名前を必ず入力ください。<br>';
        }
        if ($tel === null || $tel === '') {
            $errors .= '電話番号を必ず入力ください。<br>';
        } elseif (! is_string($tel) || ! preg_match('/^[0-9]*$/', $tel)) {
            $errors .= '電話番号を数字のみで入力ください。<br>';
        }

        // SELECT * FROM tb_shop WHERE disp_flg = '1' LIMIT 1: shop_cd solo podía elegir esa misma fila
        $shop = DB::table('tb_shop')->where('disp_flg', '1')->limit(1)->first(['kaishamei']);

        return $this->page('member-kiyaku', $request, [
            '_error_string' => $errors,
            '_shop_data' => ['kaishamei' => $shop?->kaishamei],
        ]);
    }

    /**
     * member_regist.php?domain=: 新規会員登録フォーム (campos de tb_koumoku y personalizados, AppCustomer) y, por POST,
     * el alta con las bonificaciones de la tienda (tb_shop.introduce_settings / customer_registration_settings,
     * serializados): puntos de alta con 紹介者 (mycolle:signUp), puntos de registro (mycolle:newRegist) con
     * PointService de S3, y push al 紹介者 y al socio nuevo (AppviewPush → CustomerPushService de S3).
     */
    public function form(Request $request, AppCustomer $customers, PointService $points, AppviewPush $push): Response
    {
        $domain = $request->has('domain') ? $request->input('domain') : '';
        if (empty($domain)) {
            $domain = (string) $request->getHost();
        }
        if (! is_string($domain) || AppviewDomain::package($domain) === null) {
            return Out::text('解析エラーです。');
        }
        if (AppviewDomain::package($domain) !== 'crm' || ! AppviewDomain::usesCrmDatabase($domain)) {
            return Out::text('接続できませんでした');
        }

        $introducerCd = is_string($request->input('introducer_cd')) && preg_match('/^[0-9]+$/', $request->input('introducer_cd'))
            ? $request->input('introducer_cd') : null;
        $shopList = [];
        $shopMasterName = '';
        foreach (DB::table('tb_shop')->where('disp_flg', '1')->orderBy('cd')
            ->get(['cd', 'kaishamei as shop_name', 'introduce_settings', 'customer_registration_settings']) as $shop) {
            $shopList[$shop->cd] = (array) $shop;
            if (empty($shopMasterName)) {
                $shopMasterName = (string) $shop->shop_name;
            }
        }
        $introducer = $introducerCd === null ? null : DB::table('tb_customer')->where('disp_flg', '1')->where('touroku_kubun', '2')
            ->where('cd', (int) $introducerCd)->first(['cd', 'name', 'login_id']);
        if (! $shopList) {
            return Out::text('接続できませんでした');
        }

        $isPost = $request->isMethod('POST');
        $post = $isPost ? $customers->fromPost($request->post()) : [];
        $form = $customers->form($post);
        $res = ['status' => null];
        $alertMessage = '';
        if ($isPost) {
            $res = $customers->save($domain, $post);
            if ($res['status'] === 'ok' && is_array($res['data'] ?? null)) {
                $alertMessage = $this->bonuses($request, $shopList, $introducer, $introducerCd, $res['data'], $points, $push);
            }
        }

        return $this->page('member-regist', $request, [
            'res' => $res, 'alertMessage' => $alertMessage, 'shopMasterName' => $shopMasterName, 'shopList' => $shopList,
            'form' => $form, 'domain' => $domain, 'introducerData' => $introducer ? (array) $introducer : null,
        ]);
    }

    /** Puntos y push tras el alta (member_regist.php, paquete crm). Devuelve el aviso de puntos para la app */
    private function bonuses(Request $request, array $shopList, ?object $introducer, ?string $introducerCd, array $customer, PointService $points, AppviewPush $push): string
    {
        $shopCd = (int) (is_scalar($request->input('shop_cd')) ? $request->input('shop_cd') : 0);
        $shop = $shopList[$request->post('shop_cd')] ?? [];
        $introduce = self::settings($shop['introduce_settings'] ?? null);
        $registration = self::settings($shop['customer_registration_settings'] ?? null);
        $registrationPoints = (int) ($introduce['registrationPoints'] ?? 0);
        $introducePoints = (int) ($introduce['introducePoints'] ?? 0);
        $introduceMax = (int) ($introduce['introduceMaxMonthPoints'] ?? 0);
        $newRegistPoints = (int) ($registration['registPoints'] ?? 0);
        $newRegistBody = $registration['body'] ?? null;
        $expiration = now()->startOfDay()->addDays(31)->getTimestamp() - 1;
        $alert = '';

        // お友達紹介ポイント: push al 紹介者 mientras no pase del máximo del mes
        if ($introducePoints > 0 && $introducer && $introduceMax > 0 && $introducerCd) {
            $thisMonth = (int) DB::table('tb_point_log')->where('tb_customer_cd', $introducer->cd)->where('action_flg', 'mycolle:introduce')
                ->whereBetween('touroku_date', [now()->startOfMonth()->getTimestamp(), now()->endOfMonth()->startOfSecond()->getTimestamp()])
                ->sum('sounyu_point');
            if ($thisMonth < $introduceMax) {
                $name = fn (string $key) => is_scalar($request->input($key)) ? (string) $request->input($key) : '';
                $push->send((string) $introducer->login_id, $shopCd, 'お友達紹介ポイント',
                    $name('name_1').' '.$name('name_2').'様 の会員登録が完了しました為'.number_format($introducePoints).'ポイントを差し上げます。',
                    ['system' => 'mpa', 'file' => 'signUp', 'action' => 'introduce'], $introducePoints, $expiration);
            }
        }

        $customerCd = (int) $customer['cd'];
        if ($registrationPoints > 0 && $introducerCd) {
            $points->setPoint($registrationPoints, PointService::ADD, $shopCd, 1, $customerCd, 'mycolle:signUp', -100);
            $alert = 'ありがとうございました、'.number_format($registrationPoints).'ポイントをゲットしました。';
        }
        if ($newRegistPoints > 0) {
            $points->setPoint($newRegistPoints, PointService::ADD, $shopCd, 1, $customerCd, 'mycolle:newRegist', -100);
        }
        if (! empty($newRegistBody)) {
            // El legacy filtraba el push con "id = (SELECT login_id FROM tb_customer WHERE cd = <login_id>)" y no
            // llegaba a nadie; aquí va al socio nuevo (anotado para Japón)
            $push->send((string) $customer['login_id'], $shopCd, 'ご登録ありがとうございます', (string) $newRegistBody,
                ['system' => 'mpa', 'file' => 'signUp', 'action' => 'newRegist'], $newRegistPoints, $expiration,
                ['cd' => $customerCd, 'registration_id' => $customer['registration_id'] ?? null]);
        }

        return $alert;
    }

    /** unserialize() de la configuración de la tienda (sin clases); lo que no sea un array cuenta como vacío */
    private static function settings(?string $serialized): array
    {
        $value = $serialized === null ? false : @unserialize($serialized, ['allowed_classes' => false]);

        return is_array($value) ? $value : [];
    }

    /** member_comfirm.php: confirmación con un token nuevo en la sesión (lo comprueba member_complete) */
    public function confirm(Request $request): Response
    {
        $token = bin2hex(random_bytes(16));
        $request->session()->put(self::SESSION_TOKEN, $token);

        return $this->page('member-comfirm', $request, ['token' => $token]);
    }

    /**
     * member_complete.php: con el token de member_comfirm y regist=1 da de alta al socio (tienda 1, 会員番号 =
     * login_id con createID, contraseña de 4 cifras) con las mismas columnas que el INSERT del legacy y su fila de
     * アンケート<>お住まいの形態 (cate 7, form 10). Como el legacy, sin token y sin sesión la comparación (null == null)
     * deja pasar (anotado para Japón).
     */
    public function store(Request $request, CustomerRegistrationService $registration): Response
    {
        $token = $request->input('_token');
        if ($token != $request->session()->get(self::SESSION_TOKEN)) {
            return Out::text('二重投稿のため処理を中断しました。');
        }

        $newNo = null;
        $regist = $request->input('regist');
        if ((is_string($regist) && ! is_numeric($regist) ? (float) $regist : $regist) == 1) {
            $request->session()->put(self::SESSION_TOKEN, bin2hex(random_bytes(16)));
            $newNo = DB::transaction(fn () => $this->insertCustomer($request, $registration));
        }

        return $this->page('member-complete', $request, ['new_no' => $newNo]);
    }

    /**
     * INSERT de tb_customer y tb_customer_sub de member_complete.php (columnas no nulas del legacy). El legacy nombra
     * además 8 columnas que no están en el esquema del backup (mobile_type, ec_member_cd, family_membership_id…): con
     * esa BD su INSERT falla en silencio y la página igual dice 登録完了. Aquí se guarda (anotado para Japón).
     */
    private function insertCustomer(Request $request, CustomerRegistrationService $registration): int
    {
        $cd = $registration->uniqueCd(1);
        $subCd = $registration->uniqueCd(1);
        $id = $registration->createId(1);
        $pass = $registration->randomPassword();
        $time = (string) time();
        $text = fn (string $key) => is_array($request->input($key)) ? '' : (string) $request->input($key, '');

        DB::table('tb_customer')->insert([
            'cd' => $cd, 'id' => $id, 'tb_shop_cd' => 1, 'type' => '個人', 'name' => $text('name'), 'name_kana' => '',
            'pref' => '', 'zip' => '', 'add' => '', 'add_kana' => '', 'add_build' => '', 'add_number' => '', 'add_build_kana' => '',
            'email1' => $text('mail'), 'email2' => '', 'fax' => '', 'tel' => $text('tel'), 'kojin_birth' => '//',
            'kojin_bloodtype' => '', 'kojin_sex' => '', 'kojin_mail' => '', 'kojin_mobile' => '', 'kinmusaki_name' => '',
            'kinmusaki_gyoushu' => '', 'kinmusaki_name_kana' => '', 'kinmusaki_fax' => '', 'kinmusaki_tel' => '',
            'mailmaga_email_type' => '', 'mailmaga_flg' => '1', 'touroku_kubun' => '2', 'bikou' => '',
            'touroku_date' => $time, 'update_date' => $time, 'login_id' => $id, 'login_pass' => $pass, 'error_send_cnt' => 0,
            'disp_flg' => '1', 'job' => '', 'kanri_id' => '', 'synchronize_flg' => '', 'stop_yoyaku_reminder_mail_flg' => '',
            'local_dl_sync_flg' => '',
        ]);
        DB::table('tb_customer_sub')->insert([
            'cd' => $subCd, 'tb_customer_cd' => $cd, 'tb_search_cate_cd' => 7, 'tb_search_form_cd' => 10, 'text_data' => '',
            'form_type' => '1', 'disp_flg' => '1', 'form_title' => 'アンケート<>お住まいの形態', 'touroku_date' => $time,
            'update_date' => $time, 'synchronize_flg' => '', 'local_sync_flg' => '',
        ]);

        return $cd;
    }
}
