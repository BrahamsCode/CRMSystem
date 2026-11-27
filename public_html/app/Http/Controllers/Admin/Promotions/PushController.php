<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Models\Promotions\PushFollowMaster;
use App\Services\Promotions\MailAudience;
use App\Services\Promotions\PushForm;
use App\Services\Promotions\PushSchedule;
use App\Services\Promotions\StaffContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * プッシュ通知 (category3/push/): 発行条件選択 (motor común de S3 con mailType = push), 新規作成 / 修正
 * (push_new.php + push_new.js; guarda PushAjaxController), 完了 y las listas de 予定 / フォロー / 自動通知設定.
 * Bloque del menú lateral: .ac12.
 */
class PushController extends Controller
{
    public const ACTIVE_AC = '.ac12';

    public function __construct(private readonly StaffContext $staff) {}

    /** push_customer_select.php: inc/mail_customer_select_frm.php con $mail_type = push */
    public function select(Request $request): View
    {
        $view = app()->call([app(MailController::class), 'select'], ['mailType' => 'push']);

        return $view->with([
            'title' => 'プッシュ通知',
            'activeAc' => self::ACTIVE_AC,
            'selectAction' => route('admin.promocion.push.select'),
            'issueAction' => route('admin.promocion.push.create'),
        ]);
    }

    /**
     * push_new.php: con type_flg y cd edita una regla de push_follow_master (activa o parada); si no, hace falta
     * la búsqueda de 発行条件選択 (search_uniqid), como el legacy (si no, a la portada de category3).
     */
    public function create(Request $request, PushForm $form, MailAudience $audience): View|RedirectResponse
    {
        $in = $request->except(['_token']);
        $typeFlg = is_string($in['type_flg'] ?? null) ? $in['type_flg'] : '';
        $cd = is_string($in['cd'] ?? null) ? $in['cd'] : '';

        if ($typeFlg !== '' && $cd !== '') {
            $follow = preg_match('/^[1-9][0-9]*$/', $cd)
                ? PushFollowMaster::query()->whereKey((int) $cd)->whereIn('disp_flg', ['1', PushFollowMaster::PAUSED_FLAG])->first()
                : null;
            if (! $follow) {
                return redirect()->route('admin.promocion.push.follow-list', ['type_flg' => $typeFlg]);
            }
            $values = $form->fromFollow($follow);
        } elseif (! $audience->has(is_string($in['search_uniqid'] ?? null) ? $in['search_uniqid'] : null)) {
            return redirect()->to('/system/category3/index.php');
        } else {
            $values = $form->fromRequest($in);
        }

        return view('admin.promocion.push.push_new', $form->tags() + [
            'activeAc' => self::ACTIVE_AC,
            'formValues' => $values,
            'blocks' => $form->blocks($values['message'], $in),
            'typeFlg' => $typeFlg,
            'cd' => $cd,
            'searchUniqid' => is_string($in['search_uniqid'] ?? null) ? $in['search_uniqid'] : '',
            'recurring' => in_array($typeFlg, ['auto', 'follow'], true),
            'autoDescription' => $typeFlg === 'auto' ? $form->describeAuto($values['sendParameter']) : '',
            'toolName' => $form->toolName(),
            'canCancel' => $typeFlg !== '' && $cd !== '',
        ]);
    }

    /** push_complete.php: 完了しました y a los 2 s la lista de su tipo o la portada de category3 */
    public function complete(Request $request): View
    {
        $typeFlg = $request->query('type_flg');

        return view('admin.promocion.push.push_complete', [
            'activeAc' => self::ACTIVE_AC,
            'link' => is_string($typeFlg) && in_array($typeFlg, PushSchedule::TYPES, true)
                ? route('admin.promocion.push.follow-list', ['type_flg' => $typeFlg])
                : '/system/category3/index.php',
        ]);
    }

    /**
     * push_follow_list.php?type_flg=scheduled|follow|auto: reglas activas y paradas (las de su tienda si no es
     * master), por cd. 停止 / 起動 / 修正 solo para master: el legacy comprobaba `$kengen->myKengen[$kengenNo]`
     * con `$kengenNo` sin definir, así que ningún otro usuario los veía.
     */
    public function followList(Request $request, PushSchedule $schedule): View
    {
        $typeFlg = $request->input('type_flg');
        abort_unless(is_string($typeFlg) && in_array($typeFlg, PushSchedule::TYPES, true), 404);

        $query = PushFollowMaster::query()->whereIn('disp_flg', ['1', PushFollowMaster::PAUSED_FLAG])->where('type_flg', $typeFlg)
            ->when(! $this->staff->isMaster(), fn ($q) => $q->where('tb_shop_cd', (int) $this->staff->shopCd()))
            ->orderBy('cd');
        [$kensu, $page, $maxrows] = self::paging($request, (clone $query)->count());

        return view('admin.promocion.push.push_follow_list', [
            'activeAc' => self::ACTIVE_AC,
            'typeFlg' => $typeFlg,
            'rows' => $query->forPage($page, $kensu)->get(),
            'kensu' => $kensu,
            'page' => $page,
            'maxrows' => $maxrows,
            'schedule' => $schedule,
            'canEdit' => $this->staff->isMaster(),
        ]);
    }

    /**
     * URL del legacy (`/system/category3/push/*.php`: menú left_navi3, enlaces entre pantallas) → pantalla nueva
     * con la misma query. Un POST (follow_new_confirm / auto con kind = followPush|autoPush envían a push_new.php)
     * se reenvía con 307 para conservar el cuerpo.
     */
    public function legacy(Request $request, string $target): RedirectResponse
    {
        $query = $request->getQueryString();

        return redirect()->to(route($target).($query ? '?'.$query : ''), $request->isMethod('post') ? 307 : 302);
    }

    /**
     * page_control.php de category3: kensu (por defecto 20) y página acotada a la última.
     *
     * @return array{0: int, 1: int, 2: int} [kensu, page, maxrows]
     */
    public static function paging(Request $request, int $maxrows, int $default = 20): array
    {
        $kensu = (int) $request->input('kensu', $default) ?: $default;
        $kensu = max(1, min($kensu, 1000));
        $maxpage = $maxrows > 0 ? (int) ceil($maxrows / $kensu) : 1;
        $page = max(1, min((int) $request->input('page', 1), $maxpage));

        return [$kensu, $page, $maxrows];
    }
}
