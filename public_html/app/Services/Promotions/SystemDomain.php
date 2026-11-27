<?php

namespace App\Services\Promotions;

use App\Models\AdminMaster;

/**
 * Dominio del sistema como en el legacy (inc/db.php + CrmSystem\Util\Domain::fetchSystemDomain):
 * `admin_master.domain` y, si está vacío, el host de `app.url`.
 * De él salen el remitente `mail_mag@<dominio>`, el return-path `mail_err@<dominio>` y los enlaces `/hp/`.
 */
class SystemDomain
{
    public function domain(): string
    {
        $domain = trim((string) AdminMaster::current()->domain);

        return $domain !== '' ? $domain : (string) parse_url((string) config('app.url'), PHP_URL_HOST);
    }

    /** $from_mail del legacy */
    public function fromAddress(): string
    {
        return 'mail_mag@'.$this->domain();
    }

    /** Destino de rebotes (`-fmail_err@` del legacy) */
    public function errorAddress(): string
    {
        return 'mail_err@'.$this->domain();
    }

    /** $hp_address del legacy */
    public function hpAddress(): string
    {
        return 'http://'.$this->domain().'/hp/';
    }
}
