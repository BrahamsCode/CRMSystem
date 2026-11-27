<?php

namespace App\Auth;

use App\Models\Staff;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Login del personal contra `tb_staff`, como system/login.php del legacy:
 * busca por `login_id` con `disp_flg = '1'` y compara `login_pass` en texto plano.
 * La clave no se re-cifra: la app y las integraciones del legacy dependen de ella (CONVENCIONES §0).
 */
class LegacyStaffUserProvider extends EloquentUserProvider
{
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $loginId = (string) ($credentials['login_id'] ?? '');
        if ($loginId === '') {
            return null;
        }

        return Staff::query()->active()->where('login_id', $loginId)->first();
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        $password = (string) ($credentials['password'] ?? '');

        return $password !== '' && hash_equals((string) $user->getAuthPassword(), $password);
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        // Sin re-cifrado: login_pass sigue en texto plano como en el legacy
    }
}
