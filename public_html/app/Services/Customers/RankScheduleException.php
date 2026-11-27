<?php

namespace App\Services\Customers;

/** Configuración de reparto de rangos que el legacy rechazaba con die("不適切な設定") / die("関連データ…") */
class RankScheduleException extends \RuntimeException {}
