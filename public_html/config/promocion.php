<?php

/*
 * 販売促進管理 (S3).
 */
return [

    /*
     * Aviso al servidor de push de la app (Push.php::exec → b.g-api.net / b-develop.g-api.net según
     * admin_master.push_api_host). `gapi` llama de verdad; `log` solo lo anota en el log de Laravel.
     * Por defecto `log` en APP_ENV=local, para que probar envíos en una copia local con la BD de producción
     * (push_api_host = 1) no avise al servidor real; CRM_PUSH_TRIGGER=gapi lo fuerza.
     */
    'push_trigger' => env('CRM_PUSH_TRIGGER', env('APP_ENV') === 'local' ? 'log' : 'gapi'),

];
