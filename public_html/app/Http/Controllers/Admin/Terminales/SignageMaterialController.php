<?php

namespace App\Http\Controllers\Admin\Terminales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Terminales\SignageMaterialRequest;
use App\Models\Terminales\SignageMaterial;
use App\Services\Terminales\SignageMaterialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

/**
 * 放映素材 (category7/digital_signage/screen_saver_*.php). El formulario, el detalle y el
 * 削除しました son pantallas sin menú (layouts.legacy-popup), como el legacy.
 */
class SignageMaterialController extends Controller
{
    public function __construct(private SignageMaterialService $materials) {}

    /** screen_saver_list.php (GET y POST: el buscador y el selector de 件数 envían por POST) */
    public function index(Request $request): View
    {
        return view('admin.terminales.signage-materials.index',
            $this->materials->listing($request, route('admin.terminales.signage-materials.index')));
    }

    /** ▲▼ del listado (thisCd, thisRow > 0, kind) y vuelta al listado */
    public function sort(Request $request): RedirectResponse
    {
        $displayCd = (int) $request->query('display_cd') ?: null;
        if ((int) $request->query('thisRow') > 0 && ctype_digit((string) $request->query('thisCd'))) {
            $this->materials->move((int) $request->query('thisCd'), (string) $request->query('kind'), $displayCd);
        }

        return redirect()->route('admin.terminales.signage-materials.index', [
            'page' => $request->query('page'),
            'kensu' => $request->query('kensu'),
            'display_cd' => $displayCd,
        ]);
    }

    /** screen_saver_new.php */
    public function create(): View
    {
        return view('admin.terminales.signage-materials.form', [
            'material' => null,
            'categories' => $this->materials->categories(),
        ]);
    }

    public function store(SignageMaterialRequest $request): View
    {
        [$material, $errors] = $this->materials->save(null, $request->validated(), $this->photos($request));

        return $this->processing('new', $material, $errors, $request);
    }

    /** screen_saver_detail.php */
    public function show(int $cd): View
    {
        return view('admin.terminales.signage-materials.show', [
            'material' => $this->materials->find($cd) ?? abort(404),
        ]);
    }

    /** screen_saver_update.php (GET y POST: el botón 編集する del detalle envía por POST) */
    public function edit(int $cd): View
    {
        return view('admin.terminales.signage-materials.form', [
            'material' => $this->materials->findOwned($cd) ?? abort(404),
            'categories' => $this->materials->categories(),
        ]);
    }

    public function update(SignageMaterialRequest $request, int $cd): View
    {
        $material = $this->materials->findOwned($cd) ?? abort(404);
        [$material, $errors] = $this->materials->save($material, $request->validated(), $this->photos($request));

        return $this->processing('update', $material, $errors, $request);
    }

    /** screen_saver_delete.php: «削除中です。» y luego 削除しました */
    public function destroy(Request $request, int $cd): View
    {
        $material = $this->materials->find($cd) ?? abort(404);
        if ($request->input('delete_flg') === 'del') {
            $this->materials->delete($material);
        }

        return view('admin.terminales.partials.processing', [
            'family' => 'materials',
            'action' => 'delete',
            'refreshUrl' => route('admin.terminales.signage-materials.deleted'),
        ]);
    }

    /** screen_saver_delete_complete.php */
    public function deleted(): View
    {
        return view('admin.terminales.signage-materials.deleted');
    }

    /** «登録中です。» con Refresh al detalle (screen_saver_register.php) */
    private function processing(string $action, SignageMaterial $material, array $errors, Request $request): View
    {
        return view('admin.terminales.partials.processing', [
            'family' => 'materials',
            'action' => $action,
            'uploadErrors' => $errors,
            'refreshUrl' => route('admin.terminales.signage-materials.show', [
                $material->cd,
                'register_type' => $action,
                'display_cd' => $request->input('tb_display_cd'),
                'page' => $request->input('page'),
                'kensu' => $request->input('kensu'),
            ]),
        ]);
    }

    /** @return array<string, UploadedFile> photo<x> */
    private function photos(Request $request): array
    {
        return array_filter(
            $request->allFiles(),
            fn ($file, $key) => $file instanceof UploadedFile && preg_match('/^photo\d+$/', (string) $key),
            ARRAY_FILTER_USE_BOTH,
        );
    }
}
