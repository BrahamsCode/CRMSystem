<?php

namespace App\Http\Controllers\Admin\Nucleo;

use App\Http\Controllers\Controller;
use App\Mail\Base\ContactMail;
use App\Models\Staff;
use App\Services\Nucleo\LegacyUi;
use App\Support\LegacyText;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * 要望・お問い合わせ (system/contact/index.php, contact_confirm.php y contact_complete.php): la お問合せ de la cabecera,
 * en ventana emergente. Formulario → confirmación (index.php con act=up) → envío por correo al proveedor (ContactMail).
 * - master elige la tienda (todas las no borradas); los demás envían la suya.
 * - `verify` (time() en la confirmación) evita el doble envío por recarga o doble clic, como el legacy.
 * Diferencia: el correo lleva el texto tal cual (el legacy lo mandaba con entidades HTML de get_form2).
 */
class ContactController extends Controller
{
    public const TYPES = ['要望', 'お問い合わせ'];

    private const FIELDS = ['kaishamei', 'tb_shop_cd', 'tantou_name', 'tantou_name_kana', 'type', 'comment', 'domain'];

    private const SESSION_VERIFY = 'nucleo.contact_verify';

    /** index.php: formulario; con act=up valida el 本文 y muestra la confirmación */
    public function form(Request $request, LegacyUi $legacyUi): View
    {
        $staff = $request->user('admin');
        $data = $this->data($request, $legacyUi, $staff);
        $shops = $this->shops($staff);

        if ($request->input('act') === 'up') {
            if ($data['comment'] === '') {
                return $this->view('form', $legacyUi, $staff, $data, $shops, ['error' => '※本文を入力してください。']);
            }

            return $this->view('confirm', $legacyUi, $staff, $data, $shops, ['verify' => (string) time()]);
        }

        return $this->view('form', $legacyUi, $staff, $data, $shops);
    }

    /** contact_complete.php: envía el correo una vez por `verify` */
    public function send(Request $request, LegacyUi $legacyUi): View
    {
        $staff = $request->user('admin');
        $data = $this->data($request, $legacyUi, $staff);
        $shops = $this->shops($staff);
        $verify = (string) $request->input('verify', '');

        if ($request->session()->get(self::SESSION_VERIFY) !== $verify) {
            $request->session()->put(self::SESSION_VERIFY, $verify);
            $to = (string) config('crm.notify.contact');
            if ($to !== '') {
                Mail::to($to)->send(new ContactMail([
                    'domain' => $data['domain'],
                    'kaishamei' => $data['kaishamei'],
                    'shop_name' => $shops[(int) $data['tb_shop_cd']] ?? '',
                    'tantou_name' => $data['tantou_name'],
                    'tantou_name_kana' => $data['tantou_name_kana'],
                    'type' => $data['type'],
                    'comment' => $data['comment'],
                ], now()->format('Y/m/d H:i:s')));
            }
            $result = ['message' => $data['type'] . 'を送信しました。', 'failed' => false];
        } else {
            $result = ['message' => '重複エラーが発生しました。<br />お手数ですが、もう一度はじめから入力してください。', 'failed' => true];
        }

        return $this->view('complete', $legacyUi, $staff, $data, $shops, $result);
    }

    /** Campos enviados (recortados como get_form2); el 会社名 y la ドメイン los pone el servidor al abrir el formulario */
    private function data(Request $request, LegacyUi $legacyUi, ?Staff $staff): array
    {
        $data = [];
        foreach (self::FIELDS as $field) {
            $data[$field] = LegacyText::trim((string) (is_scalar($request->input($field)) ? $request->input($field) : ''));
        }
        $data['kaishamei'] = $data['kaishamei'] !== '' ? $data['kaishamei'] : $legacyUi->siteName();
        $data['domain'] = $data['domain'] !== '' ? $data['domain'] : $request->getHost();
        $data['type'] = in_array($data['type'], self::TYPES, true) ? $data['type'] : self::TYPES[0];
        if (! $staff?->isMaster()) {
            $data['tb_shop_cd'] = (string) $staff?->shop_cd;
        }

        return $data;
    }

    /** @return array<int, string> cd => nombre: todas las tiendas no borradas para master, la propia para los demás */
    private function shops(?Staff $staff): array
    {
        return DB::table('tb_shop')->where('disp_flg', '<>', '9')
            ->when(! $staff?->isMaster(), fn ($q) => $q->where('cd', (int) $staff?->shop_cd))
            ->orderBy('cd')->pluck('kaishamei', 'cd')->mapWithKeys(fn ($name, $cd) => [(int) $cd => (string) $name])->all();
    }

    private function view(string $name, LegacyUi $legacyUi, ?Staff $staff, array $data, array $shops, array $extra = []): View
    {
        return view('admin.nucleo.contact.' . $name, $extra + [
            'data' => $data,
            'shops' => $shops,
            'isMaster' => (bool) $staff?->isMaster(),
            'siteName' => $legacyUi->siteName(),
        ]);
    }
}
