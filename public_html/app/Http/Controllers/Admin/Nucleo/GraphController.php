<?php

namespace App\Http\Controllers\Admin\Nucleo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Gráficos que las pantallas del legacy cargan en un <iframe> (system/inc/graph_pie.php y graph_bar.php,
 * con html5jp circle.js / vbar.js). Mismos parámetros GET y mismo HTML. A diferencia del legacy, los valores
 * se escapan antes de ir al JS/CSS (colores solo hexadecimales, números como número, textos como cadena JS).
 *
 * pie: v=nombre,valor|nombre,valor  w, h, b (fondo), lc (color de leyenda), c=true (rótulos)
 * bar: v=serie,v1,v2…|…  x=,etiqueta1,etiqueta2…  y=título  w, h, b, gb (fondo del área), bc=color,color
 *      gtype=square|cylinder|flat|line, l=false (sin leyenda)
 */
class GraphController extends Controller
{
    public function pie(Request $request): View
    {
        $items = [];
        foreach ($this->rows($request->query('v')) as $row) {
            $items[] = [(string) ($row[0] ?? ''), self::number($row[1] ?? 0)];
        }

        return view('admin.nucleo.graph-pie', [
            'background' => self::color($request->query('b'), 'transparent'),
            'legendColor' => self::color($request->query('lc'), '#000000'),
            'width' => self::size($request->query('w'), 400),
            'height' => self::size($request->query('h'), 250),
            'caption' => $request->query('c') === 'true',
            'items' => $items,
            'total' => array_sum(array_column($items, 1)),
        ]);
    }

    public function bar(Request $request): View
    {
        $items = [];
        foreach ($this->rows($request->query('v')) as $row) {
            $items[] = array_merge([(string) array_shift($row)], array_map([self::class, 'number'], $row));
        }
        $gtype = (string) $request->query('gtype');

        return view('admin.nucleo.graph-bar', [
            'background' => self::color($request->query('b'), 'transparent'),
            'graphBackground' => $request->query('gb') ? self::color($request->query('gb'), null) : null,
            'legend' => $request->query('l') !== 'false',
            'barShape' => in_array($gtype, ['square', 'cylinder', 'flat', 'line'], true) ? $gtype : 'square',
            'barColors' => array_values(array_filter(array_map(fn ($c) => self::color($c, null), explode(',', (string) $request->query('bc', ''))))),
            'x' => (string) $request->query('x', '') !== '' ? explode(',', (string) $request->query('x')) : [],
            'y' => (string) $request->query('y', '') !== '' ? [(string) $request->query('y')] : [],
            'width' => self::size($request->query('w'), 400),
            'height' => self::size($request->query('h'), 250),
            'items' => $items,
        ]);
    }

    /** v=a,1|b,2 → [['a','1'], ['b','2']] */
    private function rows(mixed $value): array
    {
        $value = trim((string) $value);

        return $value === '' ? [] : array_map(fn ($row) => explode(',', $row), explode('|', $value));
    }

    private static function color(mixed $hex, ?string $default): ?string
    {
        $hex = trim((string) $hex);

        return preg_match('/^[0-9A-Fa-f]{3,8}$/', $hex) ? '#' . $hex : $default;
    }

    private static function size(mixed $value, int $default): int
    {
        return (int) $value > 0 ? (int) $value : $default;
    }

    private static function number(mixed $value): float|int
    {
        $value = trim((string) $value);

        return is_numeric($value) ? $value + 0 : 0;
    }
}
