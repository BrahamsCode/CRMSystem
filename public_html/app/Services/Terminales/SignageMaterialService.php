<?php

namespace App\Services\Terminales;

use App\Support\LegacyText;
use App\Models\Terminales\SignageDisplay;
use App\Models\Terminales\SignageMaterial;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * 放映素材 (tb_screensaver): listado, ▲▼, alta/edición con subida y borrado lógico.
 * Reemplaza a screen_saver_list.php, screen_saver_register.php y screen_saver_delete.php.
 */
class SignageMaterialService
{
    public function __construct(
        private SignageShop $shop,
        private SignageRanker $ranker,
        private SignageMaterialUploader $uploader,
        private LegacyListPaginator $paginator,
    ) {}

    /** Datos de signage-materials.index (screen_saver_list.php; el listado no filtra por tienda) */
    public function listing(Request $request, string $path): array
    {
        $displays = SignageDisplay::query()->where('disp_flg', '1')->orderBy('rank')->get(['cd', 'name']);

        // Sin display_cd en la petición se toma el primer display, como el legacy
        $displayCd = $request->has('display_cd')
            ? ((int) $request->input('display_cd') ?: null)
            : $displays->first()?->cd;

        $query = SignageMaterial::query()
            ->where('disp_flg', '1')
            ->when($displayCd, fn ($q) => $q->where('tb_display_cd', $displayCd))
            ->orderBy('rank')
            ->orderBy('cd');

        return [
            'materials' => $this->paginator->paginate($query, $request, $path),
            'displays' => $displays,
            'displayCd' => $displayCd,
            'kensu' => LegacyListPaginator::kensu($request),
        ];
    }

    /** ▲▼ dentro de los materiales visibles del mismo display */
    public function move(int $cd, string $kind, ?int $displayCd): void
    {
        $this->ranker->move($this->displayGroup($displayCd), $cd, $kind);
    }

    /** カテゴリ del formulario: categorize_value_0 distintos, no vacíos */
    public function categories(): Collection
    {
        return $this->shop->scope(SignageMaterial::query())
            ->whereRaw("coalesce(categorize_value_0, '') <> ''")
            ->distinct()
            ->orderBy('categorize_value_0')
            ->pluck('categorize_value_0');
    }

    public function find(int $cd): ?SignageMaterial
    {
        return $this->shop->scope(SignageMaterial::query())->find($cd);
    }

    /** Para editar: además debe ser de la tienda de la sesión (screen_saver_update.php) */
    public function findOwned(int $cd): ?SignageMaterial
    {
        $material = $this->find($cd);

        return $material && $this->shop->owns($material) ? $material : null;
    }

    /**
     * Alta o edición (screen_saver_register.php + pic_up.php).
     *
     * @param  array<string, mixed>  $input  datos validados
     * @param  array<string, UploadedFile>  $files  photo<x>
     * @return array{0: SignageMaterial, 1: list<string>} material y errores de subida
     */
    public function save(?SignageMaterial $material, array $input, array $files): array
    {
        $isNew = $material === null;
        $material ??= new SignageMaterial(['tb_shop_cd' => $this->shop->current()]);

        // 新規でカテゴリ入れてきたら登録
        $category = filled($input['categorize_value_0_new'] ?? null)
            ? $input['categorize_value_0_new']
            : ($input['categorize_value_0'] ?? '');

        // Como screen_saver_register.php: todo el texto pasa por get_form()
        $material->fill([
            'title' => LegacyText::form($input['title']),
            'categorize_value_0' => LegacyText::form($category),
            'comment' => LegacyText::form($input['comment']),
            'play_frame_sec' => LegacyText::form($input['play_frame_sec'] ?? ''),
            'filetype' => LegacyText::form($input['filetype']),
            'disp_flg' => '1',
        ]);
        if (! empty($input['tb_display_cd'])) {
            $material->tb_display_cd = (int) $input['tb_display_cd'];
        }

        DB::transaction(function () use ($material, $isNew, $input) {
            if ($isNew) {
                // 先頭に追加 = 0; 最後に追加 = max + 1 del display (el legacy lo calculaba sobre tb_display_cd = 0)
                $material->rank = SignageMaterial::rankForNew($material->tb_display_cd, ($input['hb_which'] ?? null) === '1');
            }
            $material->save();
        });

        if ($isNew) {
            $this->ranker->renumber($this->displayGroup($material->tb_display_cd));
        }

        $errors = $this->uploader->apply($material, $input['pic_flg'] ?? [], $input['pic_extension'] ?? [], $files);

        return [$material, $errors];
    }

    /** Borrado lógico y carpeta de archivos (screen_saver_delete.php) */
    public function delete(SignageMaterial $material): void
    {
        $material->delete();
        Storage::disk('public')->deleteDirectory(SignageMaterialUploader::FOLDER.'/'.$material->cd);
    }

    private function displayGroup(?int $displayCd): Closure
    {
        return fn () => SignageMaterial::query()
            ->where('disp_flg', '1')
            ->when($displayCd, fn ($q) => $q->where('tb_display_cd', $displayCd), fn ($q) => $q->whereNull('tb_display_cd'));
    }
}
