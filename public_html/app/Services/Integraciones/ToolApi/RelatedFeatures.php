<?php

namespace App\Services\Integraciones\ToolApi;

use Illuminate\Support\Facades\DB;

/**
 * 関連機能 de tool/api: `admin_master.related_features` = '<?>' . '<>' . '<1 ポイント | 2 スタンプ>' . '<>' . '<tb_search_cate.cd>'.
 * La categoría da los campos personalizados (tb_search_form) que acompañan a cada visita.
 */
class RelatedFeatures
{
    /** @return list<string> */
    public function config(): array
    {
        return explode('<>', (string) DB::table('admin_master')->where('cd', 1)->value('related_features'));
    }

    /** '1' ポイント, '2' スタンプ u otro */
    public function logType(): string
    {
        return $this->config()[1] ?? '';
    }

    /** Fila (texto) de tb_search_cate de 関連機能, o null si no hay categoría configurada o no existe */
    public function category(): ?array
    {
        $cd = $this->config()[2] ?? '';
        if ($cd === '' || ! preg_match('/^-?[0-9]+$/', $cd)) {
            return null;
        }

        return ToolApiOutput::rows('SELECT '.ToolApiOutput::textColumns('tb_search_cate', 'tb_search_cate').' FROM tb_search_cate WHERE cd = ?', [$cd])[0] ?? null;
    }

    /** Campos visibles (hyouji_flg 1 o 2, no borrados) de la categoría, por rank, como texto */
    public function forms(int $categoryCd): array
    {
        return ToolApiOutput::rows('SELECT '.ToolApiOutput::textColumns('tb_search_form', 'tb_search_form').' FROM tb_search_form'
            ." WHERE tb_search_cate_cd = ? AND disp_flg <> '9' AND hyouji_flg IN ('1', '2') ORDER BY rank", [$categoryCd]) ?? [];
    }
}
