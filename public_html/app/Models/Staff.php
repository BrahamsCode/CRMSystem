<?php

namespace App\Models;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\Concerns\LegacyTimestamps;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\Access\Authorizable;

/**
 * Personal (`tb_staff`): cuentas del sistema y empleados. `login_id`/`login_pass` (texto, como el legacy),
 * `disp_flg` 1 = activo, 9 = borrado (`category5/staff/staff_delete_register.php`).
 * El usuario `login_id = 'master'` es el superusuario (`rank = 'master'` en la sesión del legacy).
 * Es el usuario del guard `admin` (provider `legacy_staff`, App\Auth\LegacyStaffUserProvider).
 */
class Staff extends LegacyModel implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, HasHatuban, LegacySoftDeletes, LegacyTimestamps;

    public const MASTER_LOGIN_ID = 'master';

    protected $table = 'tb_staff';

    /** tb_staff no tiene columna de "recordarme" */
    public function getRememberTokenName(): string
    {
        return '';
    }

    protected $hidden = ['login_pass'];

    protected $casts = [
        'cd' => 'integer',
        'office_cd' => 'integer',
        'shop_cd' => 'integer',
        'tb_staff_group_cd' => 'integer',
        'rank' => 'integer',
        'pos_rank' => 'integer',
        'kingaku_kihon' => 'integer',
        'kingaku_buai' => 'float',
        'age' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_cd', 'cd');
    }

    public function isMaster(): bool
    {
        return $this->login_id === self::MASTER_LOGIN_ID;
    }

    public function getAuthPasswordName(): string
    {
        return 'login_pass';
    }

    /** `$_SESSION['rank']` del legacy: 'master' o la cadena `rank_pass` */
    public function legacyRank(): string
    {
        return $this->isMaster() ? 'master' : (string) $this->rank_pass;
    }

    /** `$_SESSION['login_user_name']`: 管理者 para master; si no, el nombre de su tienda */
    public function loginUserName(): string
    {
        return $this->isMaster() ? '管理者' : (string) $this->shop?->kaishamei;
    }

    /** Activos para el login (el legacy exige `disp_flg = '1'`, no solo "no borrado") */
    public function scopeActive(Builder $query): void
    {
        $query->where($this->qualifyColumn('disp_flg'), self::ACTIVE_FLAG);
    }
}
