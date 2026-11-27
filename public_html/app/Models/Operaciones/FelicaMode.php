<?php

namespace App\Models\Operaciones;

use App\Models\Concerns\LegacyTimestamps;
use App\Models\LegacyModel;

/**
 * `tb_felica_mode`, dos filas fijas:
 * - `cd = 1` (S5): modo del lector — '0' 運用, '1' 出勤強制, '2' 退勤強制, '9' 登録. ⏸ 勤怠
 * - `cd = 2` (la edita S2 desde category1): `interval_time`, `nyushitsu_komento`, `taishitsu_komento`.
 */
class FelicaMode extends LegacyModel
{
    use LegacyTimestamps;

    public const READER_ROW = 1;

    public const MAIL_ROW = 2;

    public const MODE_OPERATION = '0';

    public const MODE_FORCE_IN = '1';

    public const MODE_FORCE_OUT = '2';

    public const MODE_REGISTER = '9';

    public const MODES = [
        // Textos de felica_mode.php (el modo 登録 se activa desde カード管理)
        self::MODE_OPERATION => '運用モード',
        self::MODE_FORCE_IN => '出勤強制モード',
        self::MODE_FORCE_OUT => '退勤強制モード',
        self::MODE_REGISTER => '登録モード',
    ];

    protected $table = 'tb_felica_mode';

    protected ?string $legacyCreatedColumn = null;

    protected ?string $legacyUpdatedColumn = 'update';

    protected $casts = ['cd' => 'integer'];

    /** Fila del lector; la crea en modo 運用 si falta */
    public static function reader(): self
    {
        return static::query()->firstOrCreate(['cd' => self::READER_ROW], ['mode' => self::MODE_OPERATION]);
    }

    public function isRegisterMode(): bool
    {
        return (string) $this->mode === self::MODE_REGISTER;
    }
}
