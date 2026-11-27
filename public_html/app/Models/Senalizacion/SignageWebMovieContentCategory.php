<?php

namespace App\Models\Senalizacion;

/**
 * 動画のサイズ (`movie_contents_category`): ancho × alto de los vídeos; carpeta `signage/upload/movie/<width>x<height>/`.
 */
class SignageWebMovieContentCategory extends SignageWebModel
{
    protected $table = 'movie_contents_category';
}
