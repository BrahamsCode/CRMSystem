<?php

namespace App\Enums\Terminales;

/**
 * Tipo de archivo de un material de señalización (filetype).
 *
 * El legacy guarda el texto japonés y el reproductor lo recibe tal cual en el
 * atributo `type` del XML del programa: legacyValue() es parte del contrato.
 */
enum SignageFileType: int
{
    case Image = 1;  // 画像ファイル
    case Flv = 2;    // FLVファイル
    case Swf = 3;    // SWFファイル (solo datos importados: ya no se aceptan subidas)

    public function legacyValue(): string
    {
        return match ($this) {
            self::Image => '画像ファイル',
            self::Flv => 'FLVファイル',
            self::Swf => 'SWFファイル',
        };
    }

    public static function fromLegacy(?string $value): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->legacyValue() === $value) {
                return $case;
            }
        }

        return null;
    }
}
