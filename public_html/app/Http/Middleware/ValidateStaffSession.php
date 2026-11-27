<?php

namespace App\Http\Middleware;

use App\Models\Staff;
use App\Services\Nucleo\StaffLogin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Como system/session.php: en cada petición se relee el staff y, si cambió su clave
 * (o ya no existe / está borrado), se cierra la sesión y se vuelve al login.
 */
class ValidateStaffSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $staff = Auth::guard(StaffLogin::GUARD)->user();

        if ($staff instanceof Staff
            && ! hash_equals((string) $request->session()->get(StaffLogin::SESSION_FLAGS . '.pass_hash'), StaffLogin::passwordFingerprint($staff))) {
            Auth::guard(StaffLogin::GUARD)->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/system/login.php');
        }

        return $next($request);
    }
}
