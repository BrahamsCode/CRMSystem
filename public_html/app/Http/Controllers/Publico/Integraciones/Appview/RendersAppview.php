<?php

namespace App\Http\Controllers\Publico\Integraciones\Appview;

use App\Services\Integraciones\ToolApi\ToolApiOutput as Out;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Pantallas de appview/* (WebView de la app): vistas PHP planas en resources/views/publico/integraciones/appview
 * con la plantilla del legacy tal cual. Todas salen como text/html; charset=UTF-8 (sin header() en el legacy).
 */
trait RendersAppview
{
    /** Renderiza la vista con `$h` (h() del legacy) y `$req` ($_REQUEST: GET + POST, POST gana) */
    protected function page(string $view, Request $request, array $data = []): Response
    {
        $path = view()->getFinder()->find('publico.integraciones.appview.'.$view);

        return Out::text(self::renderFile($path, $data + ['h' => self::h(...), 'req' => $request->all()]));
    }

    /** include de la plantilla sin el ltrim() del PhpEngine de Laravel (hay páginas que empiezan con saltos de línea) */
    private static function renderFile(string $__path, array $__data): string
    {
        $level = ob_get_level();
        ob_start();
        try {
            (static function () use ($__path, $__data) {
                extract($__data, EXTR_SKIP);
                include $__path;
            })();
        } catch (\Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            throw $e;
        }

        return (string) ob_get_clean();
    }

    /** h() de inc/h.php: htmlspecialchars sin doble codificación; un array imprime '' */
    public static function h(mixed $value): string
    {
        if (is_array($value)) {
            return '';
        }

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8', false);
    }

    /**
     * Fila como la daba pg_fetch_assoc(): valores como texto ('t'/'f' para booleanos) o null. Sin fila, las
     * columnas pedidas en null (lo que imprimía el legacy al leer un resultado vacío).
     *
     * @param  list<string>  $columns
     * @return array<string, ?string>
     */
    protected static function row(?object $row, array $columns = []): array
    {
        if ($row === null) {
            return array_fill_keys($columns, null);
        }

        return array_map(fn ($v) => match (true) {
            $v === null => null,
            is_bool($v) => $v ? 't' : 'f',
            default => (string) $v,
        }, (array) $row) + array_fill_keys($columns, null);
    }

    /** ctype_digit() sobre un parámetro (false para arrays y vacíos) */
    protected static function digits(mixed $value): bool
    {
        return is_string($value) && ctype_digit($value);
    }
}
