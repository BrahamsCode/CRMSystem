<?php

namespace App\Services\Terminales;

use App\Models\Terminal;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * ICカード管理 / 端末管理 (system/category7/felica/terminal_list.php y el borrado de ajax_del.php).
 *
 * Trabaja sobre el modelo Terminal de S1 (`tb_terminal`) sin cambiar su clase.
 * - Listado: `flg <> '9'` (literal del legacy: también deja fuera flg NULL) y el filtro de tienda
 *   de SignageShop; orden por `rank`.
 * - ▲▼: intercambio de `rank` con la vecina, con SignageRanker sobre las filas del listado
 *   (el legacy buscaba la vecina en toda la tabla, incluidas las ocultas, y con rank repetidos no movía).
 * - Borrado físico, como el legacy.
 */
class FelicaTerminalService
{
    public function __construct(
        private SignageShop $shop,
        private SignageRanker $ranker,
        private LegacyListPaginator $paginator,
    ) {}

    public function listing(Request $request, string $path): array
    {
        $query = $this->visible()
            ->select('tb_terminal.*')
            ->selectSub(
                DB::table('tb_shop')->select('kaishamei')
                    ->whereColumn('tb_shop.cd', 'tb_terminal.tb_shop_cd')
                    ->where('tb_shop.disp_flg', '<>', '9')
                    ->limit(1),
                'kaishamei'
            )
            ->orderBy('rank')
            ->orderBy('cd');

        return [
            'terminals' => $this->paginator->paginate($query, $request, $path),
            'kensu' => LegacyListPaginator::kensu($request),
        ];
    }

    /** ▲ (`up`) / ▼ (`down`) */
    public function move(int $cd, string $kind): void
    {
        $this->ranker->move($this->group(), $cd, $kind);
    }

    /** Borra el terminal (físico, como ajax_del.php). false si no existe o no es visible */
    public function delete(int $cd): bool
    {
        $terminal = $this->visible()->find($cd);
        if (! $terminal) {
            return false;
        }

        return (bool) $terminal->delete();
    }

    /** Terminales del listado: flg <> '9' y filtro de tienda de la sesión */
    private function visible(): Builder
    {
        return $this->shop->scope(Terminal::query()->where('flg', '<>', '9'));
    }

    private function group(): Closure
    {
        return fn () => $this->visible();
    }
}
