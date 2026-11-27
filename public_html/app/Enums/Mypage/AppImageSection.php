<?php

namespace App\Enums\Mypage;

/** Sección de la マイコレ a la que pertenece una imagen de la app */
enum AppImageSection: int
{
    case Cover = 1;      // カバーフロー (aplicover)
    case Banner = 2;     // バナー (aplibanner)
    case Main = 3;       // HOMEメインイメージ (mainimage)
    case Thumbnail = 4;  // サムネイル (apli_home_thumbnail)

    /** Carpeta del legacy en hp/uploads/c6/ (la usan el importador y la redirección de URL) */
    public function legacyFolder(): string
    {
        return match ($this) {
            self::Cover => 'aplicover',
            self::Banner => 'aplibanner',
            self::Main => 'mainimage',
            self::Thumbnail => 'apli_home_thumbnail',
        };
    }
}
