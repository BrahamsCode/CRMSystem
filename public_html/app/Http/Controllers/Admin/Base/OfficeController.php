<?php

namespace App\Http\Controllers\Admin\Base;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Services\Base\OfficeService;
use App\Services\Nucleo\LegacyUi;
use App\Services\Nucleo\StaffLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * 基本情報確認・変更 (system/category0/config/config.php → config_confirm.php → config_register.php)
 * y, con $setup, el 基本情報登録 de primer acceso (system/config.php → …), al que manda el login
 * cuando tb_office.kaishamei está vacío. Mismo formulario: el de primer acceso no tiene ホームページ情報.
 */
class OfficeController extends Controller
{
    public function __construct(private OfficeService $office, private LegacyUi $legacyUi) {}

    /** config.php; al volver con 書き直す llegan los datos por POST (el legacy los perdía y releía la BD) */
    public function form(Request $request, bool $setup = false): View
    {
        $data = $this->office->current();
        if ($request->isMethod('post') && $request->has('kaishamei')) {
            $data = array_merge($data, $request->only(OfficeService::FIELDS));
        }

        return view('admin.base.config.form', ['data' => $data, 'setup' => $setup, 'check' => null]);
    }

    /** config_confirm.php */
    public function confirm(Request $request, bool $setup = false): View
    {
        $data = $this->office->normalize($request->all());
        $check = $this->office->check($data);

        if ($check->fails()) {
            return view('admin.base.config.form', ['data' => $data, 'setup' => $setup, 'check' => $check]);
        }

        return view('admin.base.config.confirm', ['data' => $data, 'setup' => $setup]);
    }

    /** config_register.php: guarda y muestra 登録中です */
    public function register(Request $request, bool $setup = false): View
    {
        abort_unless($request->input('act') === 'up', 400);
        $this->office->save($request->all(), $setup);

        if ($setup) {
            // El legacy volvía a entrar con la clave nueva (por URL); aquí se renueva la sesión del master
            $staff = Staff::find(1);
            if ($staff) {
                Auth::guard(StaffLogin::GUARD)->setUser($staff);
                $request->session()->put(StaffLogin::SESSION_FLAGS . '.pass_hash', StaffLogin::passwordFingerprint($staff));
            }
        }

        return view('admin.base.shops.wait', [
            'title' => '基本情報登録 - ' . $this->legacyUi->siteName(),
            'heading' => '■基本情報登録',
            'refresh' => $setup ? '/system/select.php' : '/system/category0/config/config.php',
            'message' => '登録中です。',
            'class' => 'textCenter',
            'seconds' => 1,
            'menu' => $setup ? '' : 'base',
            'ac' => 'ac1',
        ]);
    }
}
