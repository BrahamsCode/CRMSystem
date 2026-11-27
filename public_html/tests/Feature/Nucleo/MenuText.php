<?php

namespace Tests\Feature\Nucleo;

/** Textos de prueba para label_from / title_from del menú */
class MenuText
{
    public static function title(): string
    {
        return 'おすすめ';
    }

    public static function empty(): string
    {
        return '';
    }
}
