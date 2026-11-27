<?php

namespace App\Http\Controllers\Admin\Senalizacion;

use App\Models\Senalizacion\SignageWebFile;
use App\Services\Senalizacion\SignageContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * signage/list/signage.php: サイネージ管理 (ファイル). Sin enlace en el menú del legacy; se conserva la
 * pantalla. Lista blanca por extensión sin `swf`; con extensión no permitida ya no se crea la fila vacía.
 */
class FileController extends SignageController
{
    public function index(Request $request): Response
    {
        return $this->legacyView->screen($request, 'files', [
            'data' => SignageWebFile::query()->toBase()->orderBy('cd')->get()->map(fn ($r) => (array) $r)->all(),
            '_form_action' => route('admin.senalizacion.files.store'),
        ]);
    }

    public function store(Request $request, SignageContentService $contents): RedirectResponse
    {
        $back = route('admin.senalizacion.files.index');
        $file = $request->file('upfile');
        if (! $file || ! $contents->storeFile($file)) {
            return $this->failed($back, SignageContentService::INVALID);
        }

        return $this->done($back, self::SAVED);
    }

    public function destroy(int $cd, SignageContentService $contents): RedirectResponse
    {
        $contents->deleteFile(SignageWebFile::findOrFail($cd));

        return $this->done(route('admin.senalizacion.files.index'), self::DELETED);
    }
}
