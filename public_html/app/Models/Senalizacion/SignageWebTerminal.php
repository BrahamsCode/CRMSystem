<?php

namespace App\Models\Senalizacion;

/**
 * 端末 (`terminal`): pantalla que abre `/signage/view/?cd=<cd>` y muestra su paquete `packages_cd`.
 */
class SignageWebTerminal extends SignageWebModel
{
    protected $table = 'terminal';
}
