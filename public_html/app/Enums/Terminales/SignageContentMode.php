<?php

namespace App\Enums\Terminales;

/** システム選択 de programas y displays (disp_conf) */
enum SignageContentMode: int
{
    case Files = 1;   // 放映ファイル選択: reproduce los materiales del programa
    case System = 2;  // システム内蔵: muestra un sistema integrado (cupones, juegos…)
}
