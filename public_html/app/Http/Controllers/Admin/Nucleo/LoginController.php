<?php

namespace App\Http\Controllers\Admin\Nucleo;

use App\Http\Controllers\Controller;
use App\Services\Nucleo\StaffLogin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

/**
 * system/login.php y system/logout.php del legacy (mismas URL).
 */
class LoginController extends Controller
{
    public const REMEMBER_COOKIE = 'admin_login_id';

    public function show(Request $request): View|RedirectResponse
    {
        if (Auth::guard(StaffLogin::GUARD)->check()) {
            return redirect('/system/select.php');
        }

        return view('admin.nucleo.login', [
            'rememberedId' => (string) $request->cookie(self::REMEMBER_COOKIE, ''),
        ]);
    }

    public function login(Request $request, StaffLogin $staffLogin): RedirectResponse
    {
        $loginId = trim((string) $request->input('admin_id', ''));
        $errors = $staffLogin->attempt($request, $loginId, (string) $request->input('admin_pass', ''));

        if ($errors !== null) {
            return redirect('/system/login.php')->withErrors(['login' => $errors])->withInput($request->only('admin_id'));
        }

        // "ログイン情報を保存": solo el usuario, nunca la clave
        $cookie = $request->filled('logincheck')
            ? Cookie::make(self::REMEMBER_COOKIE, $loginId, 60 * 24 * (int) config('crm.remember_login_id_days'))
            : Cookie::forget(self::REMEMBER_COOKIE);

        // El legacy manda a config.php si tb_office.kaishamei está vacío (primer acceso): pendiente con 基本情報管理
        return redirect('/system/select.php')->withCookie($cookie);
    }

    public function logout(Request $request, StaffLogin $staffLogin): RedirectResponse
    {
        $staffLogin->logout($request);

        return redirect('/system/login.php');
    }
}
