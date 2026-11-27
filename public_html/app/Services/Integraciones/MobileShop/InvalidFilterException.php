<?php

namespace App\Services\Integraciones\MobileShop;

use RuntimeException;

/** Filtro que en el legacy daba error SQL en GET /api/customer (responde {"message":"Error."}) */
class InvalidFilterException extends RuntimeException
{
}
