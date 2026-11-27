<?php

namespace App\Services\Senalizacion;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * Pinta las pantallas de `signage/` con el HTML del legacy.
 *
 * Los cuerpos de las pantallas (`resources/views/admin/senalizacion/screens/*.php`), los 17 diseños de
 * marco (`…/designs/{inc,inc_flame_cd,inc_js}/*.php`) y las pantallas del reproductor
 * (`resources/views/publico/senalizacion/signage-view/*.php`) son vistas PHP planas convertidas del
 * legacy: mismo HTML y JS, mismas variables (`$_detail_data`, `$_packages_data`, `$val`…), con `$_REQUEST`
 * → `$_request`, rutas Laravel en los enlaces y los archivos en el disco public (`$_signage_root`).
 *
 * El legacy se apoyaba en que PHP no mostraba avisos (claves que faltan en `img_path_ary`, contenidos
 * borrados…). Laravel los convierte en excepción, así que al pintar estos fragmentos se ignoran
 * E_WARNING / E_NOTICE / E_DEPRECATED como en producción; los errores de verdad siguen saltando.
 */
class SignageLegacyView
{
    /** `$_base_url` del legacy: las URL públicas (/signage/upload, /signage/img, /signage/view) se conservan */
    public const BASE_URL = '/signage';

    /** Carpeta del disco public que hace de `signage/` del legacy (contiene `upload/`) */
    public const ROOT = 'signage';

    /** display_type válido: h|v _ filas _ columnas (h_1_1 … v_2_2) */
    private const DESIGN_PATTERN = '/^[hv]_[1-9]_[1-9]$/';

    /** Variables comunes de todas las vistas del legacy */
    public function common(Request $request): array
    {
        return [
            '_base_url' => self::BASE_URL,
            '_request' => array_merge($request->query(), $request->post()),
            '_signage_root' => Storage::disk('public')->path(self::ROOT),
            '_design' => fn (string $dir, mixed $type) => $this->designPath($dir, $type),
            '_host' => $request->getHttpHost(),
        ];
    }

    /** Ruta del fragmento de diseño (`inc`, `inc_flame_cd`, `inc_js`) o null si no existe ("準備中") */
    public function designPath(string $dir, mixed $type): ?string
    {
        if (! in_array($dir, ['inc', 'inc_flame_cd', 'inc_js'], true) || ! is_string($type) || ! preg_match(self::DESIGN_PATTERN, $type)) {
            return null;
        }
        $path = resource_path("views/admin/senalizacion/designs/{$dir}/{$type}.php");

        return is_file($path) ? $path : null;
    }

    /** Vista del reproductor (view/<display_type>.php) o null */
    public function playerView(mixed $type): ?string
    {
        if (! is_string($type) || ! preg_match(self::DESIGN_PATTERN, $type)) {
            return null;
        }
        $view = 'publico.senalizacion.signage-view.'.$type;

        return view()->exists($view) ? $view : null;
    }

    /** Pinta una vista del legacy ignorando avisos (ver la cabecera) */
    public function render(string $view, array $data): string
    {
        set_error_handler(fn () => true, E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE | E_DEPRECATED | E_USER_DEPRECATED);
        try {
            return view($view, $data)->render();
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Pantalla de gestión: cuerpo `screens/<name>.php` (+ `<name>-head.php` y `<name>-scripts.php` si existen)
     * dentro del layout con cabecera, menú lateral y pie del legacy.
     *
     * @param  string  $frame  'screen' (con menú lateral) o 'popup' (contents_detail.php, sin menú)
     */
    public function screen(Request $request, string $name, array $data, string $frame = 'screen'): Response
    {
        $data = array_merge($this->common($request), [
            'success_msg_ary' => (array) session('signage_success', []),
            'error_msg_ary' => (array) session('signage_error', []),
        ], $data);

        $parts = [];
        foreach (['head' => "{$name}-head", 'main' => $name, 'scripts' => "{$name}-scripts"] as $part => $file) {
            $view = "admin.senalizacion.screens.{$file}";
            $parts[$part] = view()->exists($view) ? $this->render($view, $data) : '';
        }

        return response()->view("admin.senalizacion.{$frame}", ['parts' => $parts]);
    }
}
