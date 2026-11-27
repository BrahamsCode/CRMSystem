<?php

namespace App\Services\Mypage;

use App\Models\ContentsMaster;

/**
 * Configuración de マイコレ en `contents_master` (category = 'tb_c6_user_hp'),
 * leída y escrita como el legacy (user_hp/ajax_apliFileUpload.php, api/mpa_*):
 *
 * - `mypage_config`: `serialize()` de [sección][hueco][shop_cd] = 1 y otras claves
 *   (`cell_status`, `cell_img`, `comment_flg`, `home_image_flg`, `home_thum_flg`).
 *   El legacy lo guarda con las comillas dobles cambiadas por simples y las
 *   devuelve al leer; se hace igual porque lo leen las APIs de la app.
 * - `aplicover`, `aplibanner`: enlaces por hueco en `text_data`, separados por `||`.
 * - `apli_home_comment`, `apli_customer_group_disp`: texto.
 *
 * Como el legacy, toma la última fila (`ORDER BY cd DESC`).
 */
class MypageConfig
{
    public const CATEGORY = 'tb_c6_user_hp';

    /** Carpeta de hp/uploads/c6/ y campo de contents_master de cada sección */
    public const SECTIONS = [
        'cover' => 'aplicover',
        'banner' => 'aplibanner',
        'main' => 'mainimage',
        'thumbnail' => 'apli_home_thumbnail',
    ];

    public function row(string $field): ?ContentsMaster
    {
        return ContentsMaster::query()
            ->where('category', self::CATEGORY)
            ->where('field', $field)
            ->orderByDesc('cd')
            ->first();
    }

    public function text(string $field): ?string
    {
        return $this->row($field)?->text_data;
    }

    public function put(string $field, ?string $value): ContentsMaster
    {
        $row = $this->row($field) ?? new ContentsMaster([
            'category' => self::CATEGORY, 'field' => $field, 'disp_flg' => '1', 'hyouji_flg' => '1',
        ]);
        $row->text_data = $value;
        $row->save();

        return $row;
    }

    /** `mypage_config` deserializado (sin objetos); [] si falta o no se entiende */
    public function mypageConfig(): array
    {
        $raw = $this->text('mypage_config');
        if ($raw === null || $raw === '') {
            return [];
        }
        $value = @unserialize(str_replace("'", '"', $raw), ['allowed_classes' => false]);

        return is_array($value) ? $value : [];
    }

    public function saveMypageConfig(array $config): void
    {
        $this->put('mypage_config', str_replace('"', "'", serialize($config)));
    }

    /** ¿Se muestra el hueco de la sección para la tienda? (全店舗 = clave 0) */
    public function slotVisibleFor(string $section, int $slot, ?int $shopCd): bool
    {
        $flags = $this->mypageConfig()[$section][$slot] ?? [];

        return ! empty($flags[0]) || ($shopCd && ! empty($flags[$shopCd]));
    }

    /** Enlaces por hueco (`text_data` con `||`) de aplicover o aplibanner */
    public function links(string $field): array
    {
        $text = (string) $this->text($field);

        return $text === '' ? [] : explode('||', $text);
    }

    public function saveLinks(string $field, array $links): void
    {
        $this->put($field, implode('||', $links));
    }
}
