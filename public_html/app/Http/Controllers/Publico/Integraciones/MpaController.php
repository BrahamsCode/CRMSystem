<?php

namespace App\Http\Controllers\Publico\Integraciones;

use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Services\Customers\VisitService;
use App\Services\Integraciones\LegacyJson;
use App\Services\Integraciones\Mpa\AppImages;
use App\Services\Integraciones\Mpa\PhotoAlbumUploader;
use App\Services\Mypage\MypageConfig;
use App\Support\LegacyText;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * API de la app マイページ (`/api/mpa_*.php`), sin autenticación como el legacy. Mismas respuestas
 * (fixtures mpa_* de tests/Fixtures/integraciones/legacy). Las imágenes viven en el disco public bajo
 * hp/uploads/c6/ y se anuncian con `http://<HTTP_HOST>/hp/uploads/...?r=<mtime>`.
 */
class MpaController extends Controller
{
    /** Content-Type que fijaban getTopBanner/getCoverFlow (en minúsculas) */
    private const JSON_TYPE = 'application/json; charset=utf-8';

    public function __construct(private readonly MypageConfig $config, private readonly AppImages $images)
    {
    }

    /** mpa_corresponding.php: si la app está activa, imagen y miniatura de la HOME y su comentario (por tienda del socio) */
    public function corresponding(Request $request): Response
    {
        $result = [
            'status' => (string) DB::table('admin_master')->where('cd', 1)->value('application_use_flg') == 1 ? 'ok' : 'ng',
            'version' => '0.0.2',
        ];

        $shopCd = $this->shopOf($request->input('login_id'));
        $config = $shopCd > 0 ? $this->config->mypageConfig() : [];
        $imageKey = fn (string $flag) => $shopCd > 0 && ! empty($config[$flag][$shopCd]) ? 0 : $shopCd;

        $key = $imageKey('home_image_flg');
        $dir = 'hp/uploads/c6/apli_home_image/';
        if ($this->images->exists($dir.$key.'.jpg') && $this->images->exists($dir.$key.'_s.jpg')) {
            $result['application_main_image'] = ['main' => $this->images->url($request, $dir.$key.'.jpg'), 'small' => $this->images->url($request, $dir.$key.'_s.jpg')];
        }
        if ($shopCd > 0) {
            // Con socio también vale el .gif (cada uno por separado)
            foreach (['main' => '.gif', 'small' => '_s.gif'] as $size => $suffix) {
                if ($this->images->exists($dir.$key.$suffix)) {
                    $result['application_main_image'][$size] = $this->images->url($request, $dir.$key.$suffix);
                }
            }
        }

        $key = $imageKey('home_thum_flg');
        $dir = 'hp/uploads/c6/apli_home_thumbnail/';
        foreach ($shopCd > 0 ? ['.jpg', '.gif'] : ['.jpg'] as $extension) {
            if ($this->images->exists($dir.$key.$extension)) {
                $result['apli_home_thumbnail'] = ['main' => $this->images->url($request, $dir.$key.$extension)];
            }
        }

        $comments = $this->homeComments();
        $commentKey = $shopCd > 0 && ! empty($config['comment_flg'][$shopCd]) ? 0 : $shopCd;
        $result['apli_home_comment'] = $comments[$commentKey] ?? '';

        return LegacyJson::response($result, jsonHeader: false);
    }

    /** mpa_getTopBanner.php: banners (15 huecos) visibles para la tienda del socio, o todos los archivos sin socio */
    public function topBanner(Request $request): Response
    {
        $result = ['status' => 'ok', 'errMsg' => ''];
        $links = explode('||', (string) $this->config->text('aplibanner'));
        $loginId = LegacyText::input($request->input('login_id'));
        $shopCd = $request->has('login_id') ? $this->shopOf($loginId) : 0;
        $dir = 'hp/uploads/c6/aplibanner/';

        if ($shopCd <= 0) {
            return $this->json($result + $this->allFiles($request, $dir, $links));
        }

        $config = $this->config->mypageConfig();
        foreach (range(0, 14) as $slot) {
            $custom = (string) ($config['cell_status'][$slot] ?? '') == 2; // バナー del sistema (hp/img/banner)
            $file = '';
            foreach (['.jpg', '.gif'] as $extension) {
                if ($this->images->exists($dir.$slot.$extension) || $custom) {
                    $file = $dir.$slot.$extension;
                }
            }
            if (! ($file !== '' && $this->images->exists($file)) && ! $custom) {
                continue;
            }
            if (empty($config['banner'][$slot][0]) && empty($config['banner'][$slot][$shopCd])) {
                continue;
            }

            $mtime = $this->images->mtime($file);
            if (! $custom) {
                $result['data'][] = ['imageUrl' => $this->images->host($request).'/'.$file.'?r='.$mtime, 'url' => $links[$slot] ?? null];

                continue;
            }

            $image = $config['cell_img'][$slot] ?? null;
            $imageUrl = $this->images->host($request).'/hp/img/banner/'.$image.'?r='.$mtime;
            $login = DB::selectOne('SELECT MD5(login_id) AS login_id_enc, MD5(login_pass) AS login_pass_enc, MD5(login_id||login_pass) AS log_chk FROM tb_customer WHERE login_id = ?', [$loginId]);
            if (in_array($image, ['mypage.png', 'mypage02.png'], true) && $login) {
                // Enlace a la マイページ con md5 de usuario y contraseña, como el legacy (anotado para Japón)
                $url = $this->images->host($request).'/hp/user/control.php?log_chk='.$login->log_chk.'&login_id='.$login->login_id_enc
                    .'&login_pass='.$login->login_pass_enc.'&app=top.php';
            } elseif (preg_match('/^mobile_banner_[1-6]\.jpg$/', (string) $image)) {
                $url = 'http://mobile-s.jp/';
            } else {
                $url = $links[$slot] ?? null;
            }
            $result['data'][] = ['imageUrl' => $imageUrl, 'url' => $url];
        }

        return $this->json($result);
    }

    /** mpa_getCoverFlow.php: imágenes de portada visibles para la tienda del socio (orden por número), o todas sin socio */
    public function coverFlow(Request $request): Response
    {
        $result = ['status' => 'ok', 'errMsg' => ''];
        $links = explode('||', (string) $this->config->text('aplicover'));
        $shopCd = $request->has('login_id') ? $this->shopOf(LegacyText::input($request->input('login_id'))) : 0;
        $dir = 'hp/uploads/c6/aplicover/';

        if ($shopCd <= 0) {
            return $this->json($result + $this->allFiles($request, $dir, $links));
        }

        $config = $this->config->mypageConfig();
        $byNumber = [];
        foreach ($this->images->entries($dir) as $entry) {
            if ($entry['file']) {
                $byNumber[preg_replace('/(.+)(\.[^.]+$)/', '$1', $entry['name'])] = $dir.$entry['name'];
            }
        }
        ksort($byNumber);

        $slot = 0;
        foreach ($byNumber as $file) {
            if (! empty($config['cover'][$slot][0]) || ! empty($config['cover'][$slot][$shopCd])) {
                $result['data'][] = ['imageUrl' => $this->images->host($request).'/'.$file.'?r='.$this->images->mtime($file), 'url' => $links[$slot] ?? null];
            }
            $slot++;
        }

        return $this->json($result);
    }

    /**
     * mpa_setraiten.php?regist_id=<id del socio>: visita por la app (VisitService de S2, sin terminal ni personal)
     * o, con membership=1, solo el registro del beacon. Con éxito responde ok con customer_info (el legacy daba
     * un fatal 500 tras registrar la visita: corregido y anotado).
     */
    public function setRaiten(Request $request, VisitService $visits): Response
    {
        $registId = $request->input('regist_id');
        $customer = null;
        $error = '';
        if (! is_string($registId) || ! preg_match('/^[0-9]+$/', $registId)) {
            $error = '入力される内容は数字のみでお願いいたします。';
        } elseif (! $customer = Customer::query()->where('id', $registId)->where('disp_flg', '1')->where('touroku_kubun', '2')->first()) {
            $error = '顧客が特定できませんでした。';
        } else {
            $shopCd = (int) $request->input('shop_cd') > 0 ? (int) $request->input('shop_cd') : (int) $customer->tb_shop_cd;
            if ((string) $request->input('membership') == 1 && $request->has('membership')) {
                DB::table('tb_beacon_history')->insert([
                    'cd' => 1, 'beacon_cd' => (int) $request->input('beacon_cd'), 'tb_customer_id' => $registId,
                    'touroku_date' => (string) time(), 'tb_shop_cd' => $shopCd,
                ]);
            } elseif (! $visits->register($customer, $shopCd, VisitService::ORIGIN_TERMINAL)) {
                $error = '来店処理できませんでした。インターバルなどをご確認ください。';
            }
        }

        $result = $error === ''
            ? ['status' => 'ok', 'customer_info' => ['cd' => (string) $customer->cd, 'id' => (string) $customer->id, 'name' => (string) $customer->name]]
            : ['status' => 'error', 'errorMsg' => $error, 'errMsg' => $error];

        return LegacyJson::response($result, jsonHeader: false);
    }

    /** mpa_albumUpdate.php: foto de la app al álbum del socio (tb_photo_album) y vuelta a la app con el estado */
    public function albumUpdate(Request $request, PhotoAlbumUploader $uploader): Response
    {
        $domain = (string) (is_scalar($request->input('domain')) ? $request->input('domain') : '');
        $cd = (string) (is_scalar($request->input('cd')) ? $request->input('cd') : '');
        $comment = (string) (is_scalar($request->post('comment')) ? $request->post('comment') : '');
        $back = PhotoAlbumUploader::RETURN_URL.'?domain='.$domain.'&cd='.$cd;

        if (empty($domain) || empty($cd)) {
            return $this->redirect($back.'&status=err1&comment='.urlencode($comment));
        }
        $file = $request->file('pic_name');
        if (! $file || ! $uploader->accepts($file)) {
            return $this->redirect($back.'&status=err2&comment='.urlencode($comment));
        }

        $uploader->store($cd, $comment, $file);

        return $this->redirect($back.'&status=ok');
    }

    // ── Ayudas ──────────────────────────────────────────────────────────────

    /** tb_shop_cd (> 0) del socio con ese login_id, o 0 */
    private function shopOf(mixed $loginId): int
    {
        if (! is_scalar($loginId)) {
            return 0;
        }
        $shopCd = (int) DB::table('tb_customer')->where('login_id', (string) $loginId)->value('tb_shop_cd');

        return max($shopCd, 0);
    }

    /** Comentarios de la HOME como los leía mpa_corresponding (último activo y visible, de cualquier categoría) */
    private function homeComments(): array
    {
        $raw = (string) DB::table('contents_master')->where('field', 'apli_home_comment')->where('disp_flg', '1')->where('hyouji_flg', '1')
            ->orderByDesc('cd')->value('text_data');
        $value = $raw === '' ? false : @unserialize(str_replace("'", '"', $raw), ['allowed_classes' => false]);

        return is_array($value) ? $value : [];
    }

    /** Sin socio: cada entrada de la carpeta (glob), con su enlace por posición; las carpetas cuentan pero no salen */
    private function allFiles(Request $request, string $dir, array $links): array
    {
        $data = [];
        foreach ($this->images->entries($dir) as $i => $entry) {
            if ($entry['file']) {
                $data[$i] = ['imageUrl' => $this->images->host($request).'/'.$dir.$entry['name'].'?r='.$this->images->mtime($dir.$entry['name']), 'url' => $links[$i] ?? null];
            }
        }

        return $data ? ['data' => $data] : [];
    }

    private function json(array $result): Response
    {
        return new Response(LegacyJson::encode($result), 200, ['Content-Type' => self::JSON_TYPE]);
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}
