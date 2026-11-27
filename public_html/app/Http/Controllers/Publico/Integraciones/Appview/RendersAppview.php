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
        $html = view('publico.integraciones.appview.'.$view, $data + [
            'h' => self::h(...),
            'req' => $request->all(),
        ])->render();

        return Out::text($html);
    }

    /** h() de inc/h.php: htmlspecialchars sin doble codificación; un array imprime '' */
    public static function h(mixed $value): string
    {
        if (is_array($value)) {
            return '';
        }

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8', false);
    }

    /** ctype_digit() sobre un parámetro (false para arrays y vacíos) */
    protected static function digits(mixed $value): bool
    {
        return is_string($value) && ctype_digit($value);
    }
}
