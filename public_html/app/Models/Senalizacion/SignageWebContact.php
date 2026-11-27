<?php

namespace App\Models\Senalizacion;

/**
 * お問い合わせ (`contact`): lo escribe mail/ del legacy (pendiente de Antony). Solo `insert_date`.
 */
class SignageWebContact extends SignageWebModel
{
    protected $table = 'contact';

    public const UPDATED_AT = null;
}
