<?php

namespace App\Models\Senalizacion;

/**
 * サイクル (`cycle`): un pase de la pantalla. `img_path_ary[<marco>][<n>]` = cd de `contents` (o "blank")
 * y `action_ary[<marco>]['action']` = efecto (1 縦スライド, 2 横スライド, 3 フェード), ambos con serialize() de PHP.
 */
class SignageWebCycle extends SignageWebModel
{
    /** 表示方法 (schedule_repeat_type) */
    public const ALWAYS = 1;

    public const DAILY = 2;

    public const PERIOD = 3;

    protected $table = 'cycle';

    /** Lee un serialize() del legacy sin instanciar clases; vacío o inválido = [] */
    public static function unserializeArray(?string $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $data = @unserialize($value, ['allowed_classes' => false]);

        return is_array($data) ? $data : [];
    }

    public function imagePaths(): array
    {
        return self::unserializeArray($this->img_path_ary);
    }

    public function actions(): array
    {
        return self::decodeActions($this->action_ary);
    }

    /**
     * El formulario del legacy enviaba `action_ary[k]['action']` con las comillas dentro de la clave
     * (PHP guardaba la clave "'action'") y el reproductor leía ['action']: nunca se aplicaba el efecto.
     * Se aceptan las dos claves al leer; el formulario nuevo envía `action_ary[k][action]`.
     */
    public static function decodeActions(?string $value): array
    {
        $actions = self::unserializeArray($value);
        foreach ($actions as $k => $row) {
            if (is_array($row) && ! isset($row['action']) && isset($row["'action'"])) {
                $actions[$k]['action'] = $row["'action'"];
            }
        }

        return $actions;
    }
}
