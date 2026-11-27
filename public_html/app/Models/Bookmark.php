<?php

namespace App\Models;

/**
 * Accesos directos de la cabecera (`tb_bookmark`, ショートカット管理): `bookmark_flg = '1'` visibles,
 * orden `user_rank`. Si tiene `tb_kengen_contents_cd` + `tb_kengen_contents_node`, solo con ese permiso.
 */
class Bookmark extends LegacyModel
{
    protected $table = 'tb_bookmark';

    protected $casts = [
        'cd' => 'integer',
        'user_rank' => 'integer',
        'tb_kengen_contents_cd' => 'integer',
        'tb_kengen_contents_node' => 'integer',
    ];
}
