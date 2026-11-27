<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Promotions\PushSaveRequest;
use App\Services\Promotions\PushImageStore;
use App\Services\Promotions\PushMemberSearch;
use App\Services\Promotions\PushRegistrar;
use App\Services\Promotions\PushStatusChanger;
use App\Services\Promotions\StaffContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * AJAX de push_new.php y push_follow_list.php, en sus URL del legacy (routes/legacy/promocion.php), y la subida
 * de imágenes de push_new.js (/system/inc/ajax_picupload.php). Respuestas con el mismo JSON que el legacy;
 * el `sessId` del legacy lo sustituye el token CSRF (cabecera X-CSRF-TOKEN del layout).
 */
class PushAjaxController extends Controller
{
    /** ajaxSavePush.php: {"status":"ok"} o {"status":"error","errorMsg":…,"isFatal":…} */
    public function save(PushSaveRequest $request, PushRegistrar $registrar, StaffContext $staff): JsonResponse
    {
        if ($error = $request->legacyError()) {
            return response()->json(['status' => 'error'] + $error);
        }

        try {
            $registrar->save($request->all(), $staff->isMaster() ? -1 : (int) $staff->shopCd());
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['status' => 'error', 'errorMsg' => PushRegistrar::FAILED, 'isFatal' => false]);
        }

        return response()->json(['status' => 'ok']);
    }

    /** ajaxChangeStatus.php: st = 停止 / 起動 / 解除 */
    public function status(Request $request, PushStatusChanger $changer): JsonResponse
    {
        $error = $changer->change($request->input('cd'), $request->input('st'));

        return response()->json($error === null ? ['status' => 'ok'] : ['status' => 'error', 'errorMsg' => $error]);
    }

    /** ajaxSearchMember.php: socio para la テスト配信 */
    public function searchMember(Request $request, PushMemberSearch $search): JsonResponse
    {
        $rows = $search->search(is_string($request->input('w')) ? $request->input('w') : '');

        return response()->json($rows !== []
            ? ['status' => 'ok', 'data' => $rows]
            : ['status' => 'error', 'errorMsg' => PushMemberSearch::NO_DATA]);
    }

    /**
     * /system/inc/ajax_picupload.php: con `up` guarda la imagen del bloque (responde el nombre o `error:<mensaje>`);
     * sin `up` responde el `<img>` de la miniatura de `f`.
     */
    public function picUpload(Request $request, PushImageStore $images): Response
    {
        if (! $request->has('up')) {
            $file = PushImageStore::thumbName(preg_replace('/[^A-Za-z0-9_.-]/', '', (string) $request->input('f')));
            $height = ctype_digit((string) $request->input('h')) ? ' height="'.$request->input('h').'"' : '';

            return response('<img'.$height.' src="/system/uploads/'.e($file).'?t='.time().'" />');
        }

        $limit = min(ini_get('post_max_size'), ini_get('upload_max_filesize'));
        $field = (string) $request->input('file');
        $file = preg_match('/^picfile_\d{1,3}$/', $field) ? $request->file($field) : null;
        if ((string) $request->input('tempfilename') === '') {
            return response('error:'.PushImageStore::uploadSizeError($limit));
        }
        if ($file === null || ! $file->isValid()) {
            return response('error:'.($file && in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
                ? PushImageStore::tooBigError($limit)
                : PushImageStore::UPLOAD_ERROR));
        }

        $name = $images->storeTemp($file, (string) $request->input('tempfilename'), (string) $request->input('ext'));

        return response($name ?? 'error:'.PushImageStore::UPLOAD_ERROR);
    }

    /** /system/uploads/<archivo>: subidas temporales (carpeta pública en el legacy) */
    public function tempImage(string $file, PushImageStore $images): StreamedResponse
    {
        $path = preg_match('/^[A-Za-z0-9_-]+\.(jpg|gif)$/', $file) ? 'system/uploads/'.$file : null;
        abort_if($path === null || ! Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    }
}
