<?php

namespace App\Models\Terminales;

use App\Models\Concerns\HasHatuban;
use App\Models\Concerns\LegacySoftDeletes;
use App\Models\LegacyModel;

/**
 * ペーパーサイネージ: tarjeta/papel con QR a /ptag/<id> (`tb_url_list`, category7/card_signage/cUrl.php).
 *
 * - `cd`: hatuban 'tb_url_list'.
 * - `id`: código del QR, único entre las filas con disp_flg = '1'; vacío al crear → 'CRM' + cd a 9 cifras.
 * - `url`: destino de la redirección de /ptag/<id> (S5), guardado con htmlspecialchars(ENT_QUOTES) como control.php.
 * - `hyouji_flg`: 稼働区分 ('1' 運用, '9' 停止). `disp_flg = '9'`: borrado lógico.
 * - Sin columnas de fecha.
 */
class PaperCard extends LegacyModel
{
    use HasHatuban, LegacySoftDeletes;

    public const RUNNING = '1';

    public const STOPPED = '9';

    protected $table = 'tb_url_list';

    protected $casts = [
        'cd' => 'integer',
    ];

    public function isRunning(): bool
    {
        return (string) $this->hyouji_flg === self::RUNNING;
    }

    /** Código automático de cUrl::update(): 'CRM' + cd con 9 cifras */
    public static function autoId(int $cd): string
    {
        return 'CRM'.sprintf('%09d', $cd);
    }
}
