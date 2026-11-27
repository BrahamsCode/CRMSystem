<?php

namespace App\Http\Controllers\Admin\Mypage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mypage\ApliFileUploadRequest;
use App\Services\Mypage\AppImageUploader;
use Illuminate\Http\JsonResponse;

/**
 * URL literal del legacy /system/category6/user_hp/ajax_apliFileUpload.php (routes/legacy/mypage.php):
 * la llama el JS de マイコレ para subir, borrar y guardar imágenes. Siempre responde JSON.
 */
class ApliFileUploadController extends Controller
{
    public function __invoke(ApliFileUploadRequest $request, AppImageUploader $uploader): JsonResponse
    {
        $result = $uploader->handle(
            (string) $request->input('type'),
            $request->file('file'),
            $request->except('file'),
        );

        return response()->json($result);
    }
}
