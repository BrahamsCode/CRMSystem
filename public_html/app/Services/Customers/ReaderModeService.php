<?php

namespace App\Services\Customers;

use App\Models\Operaciones\FelicaMode;

/**
 * Fila `cd = 2` de `tb_felica_mode` (modelo de S4), la que edita 顧客管理 (category1):
 * - `mode` (カードリーダーモード設定 y カード管理): '0' 運用, '1' 入室強制, '2' 退室強制, '9' 登録.
 * - `interval_time` (カード重複読込時間間隔, en segundos).
 * - `nyushitsu_komento` / `taishitsu_komento` (メールコメント, guardados con get_form2()).
 *
 * El legacy hacía `UPDATE … WHERE cd = 2` y, si la fila no existía, no guardaba ni avisaba:
 * aquí la fila se crea al guardar (decisión del diseño, fila de felica_mode.php).
 */
class ReaderModeService
{
    /** Minutos del selector de felica_mode.php (el valor guardado es minutos × 60) */
    public const INTERVAL_MINUTES = [10, 20, 30, 60, 120];

    /** Valor de la opción テスト */
    public const INTERVAL_TEST = '1';

    /** Fila cd = 2, o null si no existe (la pantalla se pinta como el legacy sin fila) */
    public function row(): ?FelicaMode
    {
        return FelicaMode::query()->find(FelicaMode::MAIL_ROW);
    }

    /** Modo actual ('' si no hay fila) */
    public function mode(): string
    {
        return (string) $this->row()?->mode;
    }

    /** felica_mode_up: cualquier valor que no sea 9, 1 o 2 guarda 0 (運用), como el legacy; también `update` = time() */
    public function setMode(mixed $mode): void
    {
        $mode = in_array((string) $mode, [FelicaMode::MODE_REGISTER, FelicaMode::MODE_FORCE_IN, FelicaMode::MODE_FORCE_OUT], true)
            ? (string) $mode
            : FelicaMode::MODE_OPERATION;

        $row = $this->rowOrNew();
        $row->mode = $mode;
        $row->save();
    }

    /**
     * interval_time_action_act: solo los valores del selector (el legacy guardaba cualquier texto).
     * Como el UPDATE del legacy, no toca `update`.
     */
    public function setInterval(mixed $seconds): bool
    {
        $seconds = (string) $seconds;
        if (! in_array($seconds, $this->intervalValues(), true)) {
            return false;
        }

        $row = $this->rowOrNew();
        $row->interval_time = $seconds;
        $row->timestamps = $row->exists ? false : true;
        $row->save();

        return true;
    }

    /** mail_comment_register.php: ambos comentarios con get_form2() (ya llegan limpios) */
    public function setComments(string $entry, string $exit): void
    {
        $row = $this->rowOrNew();
        $row->nyushitsu_komento = $entry;
        $row->taishitsu_komento = $exit;
        $row->timestamps = $row->exists ? false : true;
        $row->save();
    }

    /** Valores válidos de interval_time: 600, 1200, 1800, 3600, 7200 y 1 (テスト) */
    public function intervalValues(): array
    {
        return [...array_map(fn (int $m) => (string) ($m * 60), self::INTERVAL_MINUTES), self::INTERVAL_TEST];
    }

    private function rowOrNew(): FelicaMode
    {
        return $this->row() ?? new FelicaMode(['cd' => FelicaMode::MAIL_ROW]);
    }
}
