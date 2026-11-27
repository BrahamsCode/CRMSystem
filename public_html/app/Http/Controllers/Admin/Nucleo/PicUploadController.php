<?php

namespace App\Http\Controllers\Admin\Nucleo;

use App\Http\Controllers\Controller;
use App\Services\Nucleo\TempUploads;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * system/inc/ajax_picupload.php (AjaxUpload de los pic_upload.js) y la carpeta pública system/uploads/.
 * Solo personal con sesión, como el legacy migrado (antes cualquiera podía subir archivos).
 */
class PicUploadController extends Controller
{
    /**
     * Con `up`: guarda la imagen del campo `file` (`picfile_<n>`) como `<tempfilename>.jpg|gif` y responde el nombre o
     * `error:<mensaje>`. Sin `up`: responde el `<img>` de la miniatura de `f` (alto opcional `h`).
     */
    public function upload(Request $request, TempUploads $uploads): Response
    {
        if (! $request->has('up')) {
            $file = TempUploads::thumbName((string) preg_replace('/[^A-Za-z0-9_.-]/', '', (string) $request->input('f')));
            $height = ctype_digit((string) $request->input('h')) ? ' height="' . $request->input('h') . '"' : '';

            return response('<img' . $height . ' src="/' . TempUploads::DIR . '/' . e($file) . '?t=' . time() . '" />');
        }

        $limit = min(ini_get('post_max_size'), ini_get('upload_max_filesize'));
        if ((string) $request->input('tempfilename') === '') {
            return response('error:' . TempUploads::uploadSizeError($limit));
        }
        $field = (string) $request->input('file');
        $file = preg_match('/^picfile_\d{1,3}$/', $field) ? $request->file($field) : null;
        if ($file === null || ! $file->isValid()) {
            return response('error:' . ($file && in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
                ? TempUploads::tooBigError($limit)
                : TempUploads::UPLOAD_ERROR));
        }

        $name = $uploads->store($file, (string) $request->input('tempfilename'), (string) $request->input('ext'));

        return response($name ?? 'error:' . TempUploads::UPLOAD_ERROR);
    }

    /** /system/uploads/<archivo> */
    public function show(string $file, TempUploads $uploads): StreamedResponse
    {
        $path = $uploads->path($file);
        abort_if($path === null, 404);

        return Storage::disk('public')->response($path);
    }
}
