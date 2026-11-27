<?php

namespace App\Http\Controllers\Publico\Integraciones\Appview;

use App\Http\Controllers\Controller;
use App\Services\Integraciones\Appview\AppCustomer;
use App\Services\Integraciones\Appview\AppviewDomain;
use App\Services\Integraciones\Appview\GApi;
use App\Services\Integraciones\Appview\LegacyPassword;
use App\Services\Integraciones\Mpa\AppImages;
use App\Services\Integraciones\ToolApi\ToolApiOutput as Out;
use App\Support\LegacyText;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * appview: páginas del socio en el WebView (会員ID, QR, 会員ページ, ログイン, フォトギャラリー, 来店履歴).
 * Sin autenticación, por `customer_cd` (contrato del legacy; anotado para Japón).
 */
class MemberController extends Controller
{
    use RendersAppview;

    /** memberId.php: ID de socio (login_id) en grande; `error` si customer_cd no son dígitos */
    public function memberId(Request $request): Response
    {
        $cd = $request->input('customer_cd');
        if (! self::digits($cd)) {
            return Out::text('error');
        }

        return $this->page('member-id', $request, ['customer_array' => ['login_id' => $this->loginId((int) $cd)]]);
    }

    /**
     * qr_code.php: QR con "<HTTP_HOST>@<customer_cd>" ofuscado (LegacyPassword). Con customer_cd=[customer_cd] y
     * demo=1 (vista previa del panel) se usa el primer socio de la tabla.
     */
    public function qrCode(Request $request): Response
    {
        $cd = $request->input('customer_cd');
        if ($cd === '[customer_cd]' && GApi::looseEqualsOne($request->input('demo'))) {
            $cd = (string) DB::table('tb_customer')->limit(1)->value('cd');
        }
        if (! self::digits($cd)) {
            return Out::text('error');
        }

        return $this->page('qr-code', $request, [
            'enc' => LegacyPassword::encode($request->server('HTTP_HOST').'@'.$cd),
            'custmer_array' => ['login_id' => $this->loginId((int) $cd)],
        ]);
    }

    /** member_init.php: enlaces a 会員登録 y ログイン */
    public function init(Request $request): Response
    {
        return $this->page('member-init', $request);
    }

    /** member_comp.php: vincula un socio provisional (touroku_kubun 1 → 2) */
    public function link(Request $request): Response
    {
        $value = $request->input('kaiincd');
        if (empty($value)) {
            return Out::text('エラー');
        }
        $cd = is_array($value) ? 1 : (int) $value;

        $linked = DB::table('tb_customer')->where('touroku_kubun', '1')->where('cd', $cd)->update(['touroku_kubun' => '2']);
        if ($linked === 0) {
            return Out::text('既に登録済みか、顧客の情報が正しくありません。');
        }

        return $this->page('member-comp', $request);
    }

    /**
     * member_login.php: con POST busca el socio activo por `id` + login_pass (get_form, en claro como el legacy) y
     * devuelve a la app su cd por postMessage; si no coincide, el formulario con el error.
     */
    public function login(Request $request): Response
    {
        $isPost = $request->isMethod('POST');
        $customer = null;
        if ($isPost) {
            $customer = DB::table('tb_customer')
                ->where('id', LegacyText::form($request->post('login_id')))
                ->where('login_pass', LegacyText::form($request->post('login_pass')))
                ->where('disp_flg', '1')->where('touroku_kubun', '2')
                ->orderByDesc('cd')->first(['cd', 'touroku_kubun']);
        }

        return $this->page('member-login', $request, [
            'isPost' => $isPost,
            '_is_login' => $customer !== null,
            '_customer_data' => $customer ? ['cd' => (string) $customer->cd, 'touroku_kubun' => (string) $customer->touroku_kubun] : [],
        ]);
    }

    /** photo_gallery.php: fotos de g-api.net (getPhotoGallery) cuyo archivo existe en este servidor */
    public function photoGallery(Request $request, AppImages $images): Response
    {
        $host = (string) $request->getHost();
        $data = GApi::get('/api/mpa/getPhotoGallery', 'domain='.$host);
        if (GApi::hasData($data)) {
            foreach ($data->data as $key => $photo) {
                $name = explode('://'.$host, (string) ($photo->largeImageUrl ?? ''), 2)[1] ?? null;
                if ($name === null) {
                    continue; // el legacy comprobaba la carpeta raíz, que existe
                }
                $name = explode('?r=', $name, 2)[0];
                $exists = str_starts_with($name, '/hp/uploads/')
                    ? $images->exists(ltrim($name, '/'))
                    : file_exists(public_path(ltrim($name, '/')));
                if (! $exists) {
                    unset($data->data[$key]);
                }
            }
        }

        return $this->page('photo-gallery', $request, ['data' => $data]);
    }

    /** visit_history.php: 来店履歴 de g-api.net (getComingHistory) por `cutomer_cd` (sic) */
    public function visitHistory(Request $request): Response
    {
        $loginId = $request->query('cutomer_cd');
        $data = GApi::get('/api/mpa/getComingHistory', 'domain='.$request->getHost().'&login_id='.urlencode(is_string($loginId) ? $loginId : ''));

        return $this->page('visit-history', $request, ['data' => $data]);
    }

    /**
     * customerUpdate.php?domain=&customer_cd= (o id=): 個人情報の登録変更フォーム. Por GET rellena con el socio
     * (customer_cd → su login_id); por POST guarda con AppCustomer (mismos campos que el alta).
     */
    public function updateProfile(Request $request, AppCustomer $customers): Response
    {
        $domain = $request->has('domain') ? $request->input('domain') : '';
        if (empty($domain)) {
            $domain = (string) $request->getHost();
        }
        $id = $request->has('id') ? $request->input('id') : '';
        $id = $request->has('customer_cd') ? $request->input('customer_cd') : $id;
        if (empty($domain) || empty($id) || ! is_string($domain) || ! is_scalar($id)) {
            return Out::text('不正なリクエストです');
        }
        $package = AppviewDomain::package($domain);
        if ($package === null) {
            return Out::text('解析エラーです。');
        }
        if ($package !== 'crm' || ! AppviewDomain::usesCrmDatabase($domain)) {
            return Out::text('接続できませんでした');
        }

        $loginId = DB::table('tb_customer')->where('cd', (int) $id)->value('login_id');
        if ($loginId !== null) {
            $id = (string) $loginId;
        }

        $isPost = $request->isMethod('POST');
        $post = $isPost ? $customers->fromPost($request->post()) : $customers->fromId((string) $id);
        $shopList = DB::table('tb_shop')->where('disp_flg', '1')->orderBy('cd')->pluck('kaishamei', 'cd')->all();
        $form = $customers->form($post === false ? [] : $post);
        $res = ['status' => null];
        if ($isPost) {
            $res = $customers->save($domain, $post, update: true);
        }

        return $this->page('customer-update', $request, [
            'res' => $res, 'id' => (string) $id, 'domain' => $domain, 'shopList' => $shopList, 'form' => $form, 'post' => $post,
        ]);
    }

    private function loginId(int $cd): ?string
    {
        $value = DB::table('tb_customer')->where('cd', $cd)->value('login_id');

        return $value === null ? null : (string) $value;
    }
}
