<?php

namespace App\Models\Senalizacion;

/**
 * 店舗 (`shop`): login `?s=md5(email01_pass)` del legacy, reemplazado por la sesión del admin.
 */
class SignageWebShop extends SignageWebModel
{
    protected $table = 'shop';
}
