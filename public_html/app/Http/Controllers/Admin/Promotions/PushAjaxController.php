<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Promotions\PushSaveRequest;
use App\Services\Promotions\PushMemberSearch;
use App\Services\Promotions\PushRegistrar;
use App\Services\Promotions\PushStatusChanger;
use App\Services\Promotions\StaffContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * AJAX de push_new.php y push_follow_list.php, en sus URL del legacy (routes/legacy/promocion.php); la subida
 * de imágenes de push_new.js (/system/inc/ajax_picupload.php) es del núcleo (NUCLEO.md §12). Respuestas con el mismo JSON que el legacy;
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
}
