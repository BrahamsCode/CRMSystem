<?php

namespace App\Models\Senalizacion;

/**
 * 画像サイズ (`contents_category`): ancho × alto de las imágenes; carpeta `signage/upload/<width>x<height>/`.
 */
class SignageWebContentCategory extends SignageWebModel
{
    protected $table = 'contents_category';
}
