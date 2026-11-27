<?php

namespace App\Services\Terminales;

use App\Models\Promotions\MycouponMaster;
use App\Models\Terminales\SignageDisplay;
use App\Models\Terminales\SignageMaterial;
use App\Models\Terminales\SignageProgram;
use App\Models\Terminales\SignageSystem;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 放映プログラム (tb_c7_movie_program): listado, formulario, detalle, alta/edición y borrado.
 * Reemplaza a movie_program_list/_frm/_detail/_register/_delete.php. Al guardar escribe el XML
 * del programa (xml_up.php) y floormap.xml (xml_info_up.php) con SignageFeedBuilder.
 */
class SignageProgramService
{
    public function __construct(
        private SignageShop $shop,
        private SignageRanker $ranker,
        private LegacyListPaginator $paginator,
        private SignageMaterialService $materials,
        private SignageFeedBuilder $feed,
    ) {}

    /** Datos de signage-programs.index */
    public function listing(Request $request, string $path): array
    {
        $query = $this->shop->scope(SignageProgram::query())
            ->where('disp_flg', '1')
            ->orderBy('rank')
            ->orderBy('cd');

        return [
            'programs' => $this->paginator->paginate($query, $request, $path),
            'kensu' => LegacyListPaginator::kensu($request),
        ];
    }

    public function find(int $cd): ?SignageProgram
    {
        return $this->shop->scope(SignageProgram::query())->find($cd);
    }

    /** Para editar: además debe ser de la tienda de la sesión (movie_program_update.php) */
    public function findOwned(int $cd): ?SignageProgram
    {
        $program = $this->find($cd);

        return $program && $this->shop->owns($program) ? $program : null;
    }

    /**
     * Datos de signage-programs.form.
     *
     * @param  list<int|string>|null  $oldSelected  tb_screensaver_cd[] enviados (si vuelve con error)
     */
    public function formData(?SignageProgram $program, Request $request, ?array $oldSelected): array
    {
        $selectedCds = $oldSelected !== null
            ? array_map('intval', array_filter($oldSelected, fn ($cd) => ctype_digit((string) $cd)))
            : ($program?->materialCds() ?? []);

        return [
            'program' => $program,
            'nonCouponFlg' => $program ? $this->system($program)?->non_coupon_flg : null,
            'coupons' => $this->coupons(),
            'systems' => $this->shop->scope(SignageSystem::query())->orderBy('rank')->get(['cd', 'name', 'non_coupon_flg']),
            'categories' => $this->materials->categories(),
            'selectedMaterials' => $this->selectedMaterials($selectedCds),
            'availableMaterials' => $this->availableMaterials(
                $selectedCds,
                (string) $request->input('search_p', ''),
                (string) $request->input('search_categorize_value_0', ''),
            ),
        ];
    }

    /** Datos de signage-programs.show */
    public function showData(SignageProgram $program): array
    {
        return [
            'program' => $program,
            // Orden de reproducción; sin archivos elegidos, ninguno (el legacy listaba todos)
            'programMaterials' => $program->materials(),
            'coupons' => $this->coupons(),
            'feliCoupon' => $this->coupon($program->feli_tb_mycoupon_master_cds),
            'mypageCoupon' => $this->coupon($program->mypage_tb_mycoupon_master_cds),
            'displaySystem' => $this->system($program),
            'previewDisplayCd' => SignageDisplay::query()
                ->where('tb_c7_movie_program_cds', (string) $program->cd)
                ->value('cd'),
            'shopCd' => $this->shop->current(),
        ];
    }

    /**
     * Alta o edición (movie_program_register.php) y escritura de los XML.
     *
     * @param  array<string, mixed>  $input  datos enviados (validados)
     */
    public function save(?SignageProgram $program, array $input): SignageProgram
    {
        $isNew = $program === null;
        $program ??= new SignageProgram(['tb_shop_cd' => $this->shop->current(), 'disp_flg' => '1']);

        // Casillas de cupón por posición: tb_mycoupon_master_cds0..max-1 (vacías incluidas)
        $max = (int) ($input['tb_mycoupon_master_max'] ?? 0);
        $coupons = [];
        $systemCoupons = [];
        for ($i = 0; $i < $max; $i++) {
            $coupons[] = (string) ($input['tb_mycoupon_master_cds'.$i] ?? '');
            $systemCoupons[] = (string) ($input['system_tb_mycoupon_master_cds'.$i] ?? '');
        }

        $text = fn (string $key) => (string) ($input[$key] ?? '');
        $program->fill([
            // El formulario no envía terminal_id ni terminal_type: el legacy los deja vacíos al guardar
            'terminal_id' => '',
            'terminal_type' => '',
            'telop_comment' => $text('telop_comment'),
            'act_flg' => $text('act_flg'),
            'feli_act_flg' => $text('feli_act_flg'),
            'mypage_act_flg' => $text('mypage_act_flg'),
            'feli_tb_mycoupon_master_cds' => $text('feli_tb_mycoupon_master_cds'),
            'mypage_tb_mycoupon_master_cds' => $text('mypage_tb_mycoupon_master_cds'),
            'comment' => $text('comment'),
            'name' => $text('name'),
            'disp_conf' => $text('disp_conf'),
            'tb_dispaly_systems_cds' => $text('tb_dispaly_systems_cds'),
            'vertical_setting' => $text('vertical_setting'),
        ]);
        $program->setLegacyList('tb_mycoupon_master_cds', $coupons)
            ->setLegacyList('system_tb_mycoupon_master_cds', $systemCoupons)
            ->setMaterialCds($input['tb_screensaver_cd'] ?? []);

        DB::transaction(function () use ($program, $isNew, $input) {
            if ($isNew) {
                // 最後に追加: el legacy usaba la columna inexistente tb_c7_movie_program_cd y el UPDATE
                // fallaba entero; aquí es max(rank) + 1 de los programas de la tienda
                $program->rank = ($input['hb_which'] ?? null) === '1'
                    ? 0
                    : (int) $this->shop->scope(SignageProgram::query())->max('rank') + 1;
            }
            $program->save();
        });

        if ($isNew) {
            $this->ranker->renumber($this->group());
        }

        $this->feed->writeProgram($program->cd);
        $this->feed->writeFloormap();

        return $program;
    }

    /**
     * Borrado lógico (movie_program_delete.php). El legacy intentaba borrar
     * hp/uploads/c7/movie_program/<cd>.xml, que nunca existe: no se borra nada más.
     */
    public function delete(SignageProgram $program): void
    {
        $program->delete();
    }

    /** Cupones de señalización (coupon_kubun = 8) de la tienda, más recientes primero */
    private function coupons(): Collection
    {
        return $this->shop->scope(MycouponMaster::query())
            ->where('coupon_kubun', MycouponMaster::KUBUN_SIGNAGE)
            ->orderByDesc('update_date')
            ->get(['cd', 'title', 'comment']);
    }

    private function coupon(mixed $cd): ?MycouponMaster
    {
        return ctype_digit((string) $cd) ? MycouponMaster::query()->find((int) $cd) : null;
    }

    private function system(SignageProgram $program): ?SignageSystem
    {
        $cds = $program->systemCds();

        return $cds === [] ? null : $this->shop->scope(SignageSystem::query())->whereIn('cd', $cds)->first();
    }

    /** 放映するファイル en el orden guardado (solo disp_flg = '1', como el legacy) */
    private function selectedMaterials(array $cds): Collection
    {
        if ($cds === []) {
            return collect();
        }
        $found = SignageMaterial::query()->where('disp_flg', '1')->whereIn('cd', $cds)->get()->keyBy('cd');

        return collect($cds)->map(fn (int $cd) => $found->get($cd))->filter()->values();
    }

    /**
     * 選択可能ファイル: el resto de materiales visibles, filtrados por el buscador, cd DESC.
     * El legacy solo aplicaba el buscador si el programa ya tenía archivos elegidos: aquí siempre.
     */
    private function availableMaterials(array $selectedCds, string $keyword, string $category): Collection
    {
        return SignageMaterial::query()
            ->where('disp_flg', '1')
            ->when($selectedCds !== [], fn ($q) => $q->whereNotIn('cd', $selectedCds))
            ->when($keyword !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$keyword}%")
                ->orWhere('comment', 'like', "%{$keyword}%")))
            ->when($category !== '', fn ($q) => $q->where('categorize_value_0', 'like', "%{$category}%"))
            ->orderByDesc('cd')
            ->get();
    }

    private function group(): Closure
    {
        return fn () => $this->shop->scope(SignageProgram::query());
    }
}
