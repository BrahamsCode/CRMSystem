<?php

namespace App\Models;

use App\Models\Concerns\HasHatuban;

/**
 * Historial de entradas y salidas del personal (`tb_login_rireki`): `komento` = ログイン / ログアウト,
 * `update` = epoch en texto. `cd` con hatuban `tb_login_rireki`, como login.php y logout.php.
 */
class LoginHistory extends LegacyModel
{
    use HasHatuban;

    protected $table = 'tb_login_rireki';

    public static function record(Staff $staff, string $komento): static
    {
        return static::create([
            'staff_cd' => $staff->cd,
            'staff_name' => $staff->name,
            'update' => (string) now()->timestamp,
            'komento' => $komento,
        ]);
    }
}
