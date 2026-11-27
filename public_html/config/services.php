<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    // API de la tienda móvil (S5, contrato del legacy con firebase/php-jwt). Valores solo en .env
    'mobileshop' => [
        'api_user' => env('MOBILESHOP_API_USER'),
        'api_pass' => env('MOBILESHOP_API_PASS'),
        'extlink_url' => env('MOBILE_SHOP_EXTLINK_API_URL'),
        'extlink_pass' => env('MOBILESHOP_EXTLINK_PASS'),
        'domain_list' => env('MOBILESHOP_LIST'),
        'private_key_path' => env('MOBILESHOP_PRIVATE_KEY', base_path('../keys/private.pem')),
        'public_key_path' => env('MOBILESHOP_PUBLIC_KEY', base_path('../keys/public.pem')),
    ],

    // Clave de la API del CRM (tool/api del legacy)
    'crm' => [
        'api_key' => env('CRM_API_KEY'),
    ],

];
