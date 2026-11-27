<?php

namespace App\Services\Promotions;

/**
 * Aviso al servidor de push de la app (g-api) de que hay una お知らせ o un push para enviar
 * (legacy system/class/Push.php::exec). El servidor lee mailmag_master / push_master del CRM.
 */
interface PushTrigger
{
    /** $type: 'mailmaga' (espejo de una メルマガ) o 'push' */
    public function trigger(string $type, int $masterCd): void;
}
