<?php

namespace App\Services\Terminales;

use App\Support\LegacyText;
use App\Models\Terminales\SignageDisplay;
use App\Models\Terminales\SignageProgram;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ディスプレイ (tb_display): listado con ▲▼, alta/edición y borrado.
 * Reemplaza a display_list/_frm/_detail/_register/_delete.php.
 */
class SignageDisplayService
{
    public function __construct(
        private SignageShop $shop,
        private SignageRanker $ranker,
        private LegacyListPaginator $paginator,
        private SignageFeedBuilder $feed,
    ) {}

    /** Datos de signage-displays.index; cada fila lleva programName (nombre del programa elegido) */
    public function listing(Request $request, string $path): array
    {
        $query = $this->shop->scope(SignageDisplay::query())
            ->where('disp_flg', '1')
            ->orderBy('rank')
            ->orderBy('cd');
        $displays = $this->paginator->paginate($query, $request, $path);

        $programCds = $displays->getCollection()->map(fn (SignageDisplay $d) => $d->programCds()[0] ?? null)->filter()->unique();
        $names = SignageProgram::query()->whereIn('cd', $programCds->all())->pluck('name', 'cd');
        foreach ($displays as $display) {
            // Atributo solo para la vista (no se guarda: el listado no llama a save())
            $display->setAttribute('programName', $names->get($display->programCds()[0] ?? 0));
        }

        return [
            'displays' => $displays,
            'kensu' => LegacyListPaginator::kensu($request),
        ];
    }

    public function move(int $cd, string $kind): void
    {
        $this->ranker->move($this->group(), $cd, $kind);
    }

    /** Programas del <select> 「放映プログラム」選択 */
    public function programs(): Collection
    {
        return $this->shop->scope(SignageProgram::query())->orderBy('rank')->orderBy('cd')->get(['cd', 'name']);
    }

    public function find(int $cd): ?SignageDisplay
    {
        return $this->shop->scope(SignageDisplay::query())->find($cd);
    }

    /** Para editar: además debe ser de la tienda de la sesión (display_update.php) */
    public function findOwned(int $cd): ?SignageDisplay
    {
        $display = $this->find($cd);

        return $display && $this->shop->owns($display) ? $display : null;
    }

    public function showData(SignageDisplay $display): array
    {
        return [
            'display' => $display,
            'program' => $display->program(),
            'shopCd' => $this->shop->current(),
        ];
    }

    /**
     * Alta o edición (display_register.php). Si el display pasa a otro programa se reescribe el
     * XML de ese programa (pedido de S0; el legacy no lo hacía).
     *
     * @param  array<string, mixed>  $input  datos validados
     */
    public function save(?SignageDisplay $display, array $input): SignageDisplay
    {
        $isNew = $display === null;
        $display ??= new SignageDisplay(['tb_shop_cd' => $this->shop->current(), 'disp_flg' => '1']);
        $previousProgram = (string) $display->tb_c7_movie_program_cds;

        $display->fill([
            // El formulario no envía terminal_id ni terminal_type: el legacy los deja vacíos al guardar
            'terminal_id' => '',
            'terminal_type' => '',
            // Como display_register.php: todo el texto pasa por get_form()
            'tb_c7_movie_program_cds' => LegacyText::form($input['tb_c7_movie_program_cds'] ?? ''),
            'comment' => LegacyText::form($input['comment'] ?? ''),
            'name' => LegacyText::form($input['name']),
        ]);

        DB::transaction(function () use ($display, $isNew, $input) {
            if ($isNew) {
                // 最後に追加: el legacy usaba tb_display.tb_display_cd (no existe); aquí max(rank) + 1
                $display->rank = ($input['hb_which'] ?? null) === '1'
                    ? 0
                    : (int) $this->shop->scope(SignageDisplay::query())->max('rank') + 1;
            }
            $display->save();
        });

        if ($isNew) {
            $this->ranker->renumber($this->group());
        }

        $programCd = $display->programCds()[0] ?? null;
        if ($programCd && ($isNew || $previousProgram !== (string) $display->tb_c7_movie_program_cds)) {
            $this->feed->writeProgram($programCd);
        }

        return $display;
    }

    /**
     * Borrado lógico (display_delete.php). El legacy además borraba hp/uploads/c7/display/<cd del
     * display>.xml, que es el XML del PROGRAMA con ese número, y los archivos de los materiales con
     * tb_display_cd = cd (dejando sus filas vivas y rotas): no se hace ninguna de las dos cosas.
     */
    public function delete(SignageDisplay $display): void
    {
        $display->delete();
    }

    private function group(): Closure
    {
        return fn () => $this->shop->scope(SignageDisplay::query());
    }
}
