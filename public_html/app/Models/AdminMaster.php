<?php

namespace App\Models;

/**
 * Configuración global del legacy (`admin_master`): una sola fila, `cd = 1`
 * (`base_system_version_flg`, `application_use_flg`, `system_mail`, `mail_footer`, `ssl_flg`, `domain`,
 * `seller_id`, `smatopi_flg`…). Uso: `AdminMaster::current()->system_mail`.
 */
class AdminMaster extends LegacyModel
{
    public const ROW_CD = 1;

    protected $table = 'admin_master';

    protected $hidden = ['pass', 'smtp_login_pass'];

    protected $casts = [
        'cd' => 'integer',
        'application_use_flg' => 'integer',
        'shop_category_flg' => 'integer',
        'beacon_use_flg' => 'integer',
        'ssl_flg' => 'integer',
        'smatopi_flg' => 'integer',
        'seller_id' => 'integer',
        'pos_koubai_tb_search_form_cd' => 'integer',
    ];

    /** La fila de configuración (`cd = 1`); un modelo vacío si la BD aún no la tiene */
    public static function current(): static
    {
        return static::query()->find(self::ROW_CD) ?? new static(['cd' => self::ROW_CD]);
    }
}
