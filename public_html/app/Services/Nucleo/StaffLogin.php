<?php

namespace App\Services\Nucleo;

use App\Models\LoginHistory;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

/**
 * Login y logout del personal, con las mismas reglas y mensajes que system/login.php y logout.php:
 * - Normal: `login_id` + `login_pass` (texto) en `tb_staff` con `disp_flg = '1'`.
 * - Soporte `extsupport` (clave de 6 caracteres) y `Relation` (clave de la cuenta o de un solo uso de 8):
 *   se verifican contra el servidor de soporte y entran como `master`.
 * - Historial en `tb_login_rireki` (ログイン / ログアウト).
 * Devuelve null si entró, o el mensaje de error del legacy.
 */
class StaffLogin
{
    public const GUARD = 'admin';

    /** Claves de sesión con las banderas del legacy ($_SESSION['support_login'], etc.) */
    public const SESSION_FLAGS = 'nucleo';

    public function attempt(Request $request, string $loginId, string $password): ?array
    {
        $relationPass = (string) config('crm.support.relation_login_pass');
        $masterPass = (string) config('crm.support.support_master_pass');

        // Las claves internas de soporte no se pueden escribir en el formulario
        if ($loginId !== '' && $password !== '' && in_array($password, array_filter([$relationPass, $masterPass]), true)) {
            return ['※ご入力の情報は無効です。'];
        }

        if ($loginId === '') {
            return array_merge(['※ユーザーIDを入力してください。'], $password === '' ? ['※パスワードを入力してください。'] : []);
        }

        $komentoExtra = '';
        $isRelation = false;
        if ($loginId === 'extsupport' || $loginId === 'Relation') {
            [$loginId, $password, $komentoExtra, $isRelation] = $this->supportCredentials($request, $loginId, $password);
        }

        $staff = $loginId === '' ? null : Staff::query()->active()->where('login_id', $loginId)->first();
        if (! $staff) {
            return ['※ご入力のユーザーIDは無効です。'];
        }
        if ($password === '') {
            return ['※パスワードを入力してください。'];
        }

        $isSupport = $password !== '' && in_array($password, array_filter([$relationPass, $masterPass]), true);
        if (! $isSupport && ! hash_equals((string) $staff->login_pass, $password)) {
            return ['※パスワードが正しくありません。'];
        }

        LoginHistory::record($staff, 'ログイン' . $komentoExtra);

        Auth::guard(self::GUARD)->login($staff);
        $request->session()->regenerate();
        $request->session()->put(self::SESSION_FLAGS, [
            'support_login' => $isSupport,
            'support_master' => $masterPass !== '' && $password === $masterPass,
            'relation_login' => $isRelation,
            'pass_hash' => self::passwordFingerprint($staff),
        ]);

        return null;
    }

    public function logout(Request $request): void
    {
        $staff = Auth::guard(self::GUARD)->user();
        if ($staff instanceof Staff) {
            LoginHistory::record($staff, 'ログアウト');
        }

        Auth::guard(self::GUARD)->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    /** Huella de la clave para expulsar la sesión si cambia (session.php relee login_pass en cada petición) */
    public static function passwordFingerprint(Staff $staff): string
    {
        return hash_hmac('sha256', (string) $staff->login_pass, (string) config('app.key'));
    }

    /**
     * Verificación contra el servidor de soporte (b.g-api.net), como login.php.
     *
     * @return array{0: string, 1: string, 2: string, 3: bool} [login_id, clave interna, sufijo del historial, relation]
     */
    private function supportCredentials(Request $request, string $loginId, string $password): array
    {
        $host = config('crm.support.api_host');
        $timeout = (int) config('crm.support.timeout');
        $domain = $request->getHost();
        $none = ['', '', '', false];

        try {
            if ($loginId === 'extsupport') {
                if (mb_strlen($password) !== 6) {
                    return $none;
                }
                $response = Http::timeout($timeout)->get("http://{$host}/api/support/verifyOnetimePassword.php", [
                    'act' => 'verify', 'pass' => $password, 'domain' => $domain,
                ])->json();
                if (($response['status'] ?? null) !== 'ok') {
                    return $none;
                }
                $pass = ($response['data']['access_level'] ?? null) === 'master'
                    ? config('crm.support.support_master_pass')
                    : config('crm.support.relation_login_pass');

                return [Staff::MASTER_LOGIN_ID, (string) $pass, ' ワンタイムパスワード: ' . $password, false];
            }

            // Relation: clave de la cuenta de soporte o, si falla y mide 8, clave de un solo uso
            $response = Http::asForm()->timeout($timeout)->post("http://{$host}/api/support/verifyAccountLogin.php", [
                'act' => 'verify', 'password' => $password, 'domain' => $domain,
            ])->json();
            if (($response['status'] ?? null) !== 'ok' && mb_strlen($password) === 8) {
                $response = Http::asForm()->timeout($timeout)->post("http://{$host}/api/support/verifyOnetimePassword.php", [
                    'act' => 'verify', 'pass' => $password, 'domain' => $domain,
                ])->json();
            }
            if (($response['status'] ?? null) !== 'ok') {
                return $none;
            }

            return [Staff::MASTER_LOGIN_ID, (string) config('crm.support.relation_login_pass'), ' Relation: ' . ($response['data']['name'] ?? ''), true];
        } catch (\Throwable) {
            return $none;
        }
    }
}
