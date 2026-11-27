<?php

/*
 * Señalización web (S4, signage/ del legacy).
 */
return [
    // Destinatario del aviso de las お問い合わせ de GoTel (signage/mail/index.php). Vacío = sin correo, como el legacy.
    'contact_notify' => env('SIGNAGE_CONTACT_NOTIFY', ''),
];
