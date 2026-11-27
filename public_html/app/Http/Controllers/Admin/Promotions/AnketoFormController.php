<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Promotions\AnketoFormRequest;
use App\Models\Promotions\MycouponMaster;
use App\Services\Promotions\AnketoFamily;
use App\Services\Promotions\AnketoItemService;
use App\Services\Promotions\AnketoSurveyService;
use App\Services\Promotions\ClickStats;
use App\Services\Promotions\StaffContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Ítems de la encuesta: anketo_form_list.php (詳細 de la encuesta + lista de ítems con 必須 / 回答一覧に表示, ▲▼,
 * 発行条件を選択する, 削除 y 複製; en 過去アンケート配信 la クリックカウンタ del envío), anketo_form_new.php /
 * _update.php (+ _frm, _errchk, _register), anketo_form_delete.php y anketo_form_detail.php.
 * お客様アンケート: los ítems solo se editan durante el alta (`register_type=new`), como el legacy.
 */
class AnketoFormController extends Controller
{
    public function __construct(
        private readonly AnketoItemService $items,
        private readonly AnketoSurveyService $surveys,
        private readonly StaffContext $staff,
    ) {}

    /** anketo_form_list.php (act=update: 必須 / 回答一覧に表示) */
    public function index(Request $request, ClickStats $clickStats, string $family): View|RedirectResponse
    {
        $family = AnketoFamily::from($family);
        $pcd = (string) $request->input('pcd');
        $back = $this->listQuery($request, $family);
        if (! preg_match('/^[0-9]+$/', $pcd)) {
            return redirect()->to($family->route('index', $back));
        }
        $new = $family->isCustomer() && $request->input('register_type') === 'new';
        // お客様アンケート: antes del «登録» la cabecera tiene disp_flg vacío
        $cate = $family->cates()->whereKey((int) $pcd)->when(! $new, fn ($q) => $q->where('disp_flg', '1'))->first();
        if (! $cate) {
            return redirect()->to($family->route('index', $back));
        }

        if ($request->input('act') === 'update') {
            $this->items->updateFlags($family, $cate->cd, $request->all());
        }

        $sended = ! $family->isCustomer() && $request->input('which') === 'sended';
        $mail = $sended ? $this->surveys->mail($cate->cd) : null;

        $subKensu = max(1, (int) ($request->input('sub_kensu') ?: 20));
        $query = $family->forms()->where('tb_c3_anketo_cate_cd', $cate->cd)->where('disp_flg', '1');
        $total = (clone $query)->count();
        $maxPage = (int) ceil($total / $subKensu);
        $subPage = min(max(1, (int) ($request->input('sub_page') ?: 1)), max(1, $maxPage));

        return view('admin.promocion.anketo.anketo_form_list', [
            'family' => $family,
            'cate' => $cate,
            // お客様アンケート: siempre como «enviada» (no se modifica la cabecera); los ítems, solo durante el alta
            'editable' => ! $family->isCustomer() && (string) $cate->hyouji_flg === '',
            'registerNew' => $new,
            'sended' => $sended,
            'mail' => $mail,
            'stats' => $mail ? $clickStats->forMail((int) $mail->cd) : null,
            'respondents' => $sended ? $this->surveys->respondents($family, $cate->cd) : 0,
            'coupon' => $sended && $cate->attached_cd ? MycouponMaster::query()->withDeleted()->find($cate->attached_cd) : null,
            'canIssue' => (int) $cate->end_date > Carbon::now()->getTimestamp(),
            'forms' => $query->orderBy('rank')->forPage($subPage, $subKensu)->get(),
            'total' => $total,
            'subPage' => $subPage,
            'subKensu' => $subKensu,
            'maxPage' => $maxPage,
            'from' => ($subPage - 1) * $subKensu,
            'listQuery' => $back,
            'isMaster' => $this->staff->isMaster(),
        ]);
    }

    /** ▲▼ (el legacy lo hacía con GET thisCd/thisRow/kind en anketo_form_list.php): aquí POST y vuelta a la lista */
    public function move(Request $request, string $family): RedirectResponse
    {
        $family = AnketoFamily::from($family);
        $pcd = (int) $request->input('pcd');
        if (preg_match('/^[0-9]+$/', (string) $request->input('thisCd'))) {
            $this->items->move($family, $pcd, (int) $request->input('thisCd'), (int) $request->input('thisRow'), (string) $request->input('kind'));
        }

        return redirect()->to($family->route('forms.index', array_filter([
            'register_type' => $request->input('register_type'),
            'pcd' => $pcd,
            'sub_kensu' => $request->input('sub_kensu'),
            'sub_page' => $request->input('sub_page'),
        ] + $this->listQuery($request, $family), fn ($v) => (string) $v !== '')));
    }

    /** anketo_form_new.php */
    public function create(AnketoFormRequest $request, string $family): View|RedirectResponse
    {
        return $this->form($request, AnketoFamily::from($family), null);
    }

    /** anketo_form_update.php */
    public function edit(AnketoFormRequest $request, string $family): View|RedirectResponse
    {
        return $this->form($request, AnketoFamily::from($family), (int) $request->input('cd'));
    }

    private function form(AnketoFormRequest $request, AnketoFamily $family, ?int $cd): View|RedirectResponse
    {
        $pcd = (string) $request->input('pcd');
        if (! preg_match('/^[0-9]+$/', $pcd)) {
            return redirect()->to($family->route('index'));
        }
        $cate = $family->cates()->find((int) $pcd);

        $errors = [];
        if ($request->input('act') === 'up') {
            $data = $request->legacyData();
            $errors = $request->legacyErrors($family->isCustomer());
            if ($errors === []) {
                $form = $this->items->save($family, (int) $pcd, $data, $cd);

                return $this->registered($request, $family, (int) $pcd, $form->cd, $cd === null);
            }
        } elseif ($cd !== null) {
            $form = $family->forms()->find($cd);
            if (! $form) {
                return redirect('/system/category3/index.php');
            }
            $data = $this->items->formData($form);
        } else {
            $data = [];
        }

        return view('admin.promocion.anketo.anketo_form_frm', [
            'family' => $family,
            'title' => '『'.($cate?->name ?? '').'』 '.($family->isCustomer() ? ($cd === null ? 'アンケート項目' : '項目') : '入力フォーム部品設定'),
            'mode' => $cd === null ? '新規登録' : '修正',
            'action' => $family->route($cd === null ? 'forms.create' : 'forms.edit'),
            'data' => $data,
            'errors' => $errors,
            'cd' => $cd,
            'pcd' => $pcd,
            'page' => (string) $request->input('page'),
            'back' => $family->route('forms.index', array_filter([
                'register_type' => $family->isCustomer() ? 'new' : null,
                'page' => $request->input('page'),
                'pcd' => $pcd,
            ], fn ($v) => (string) $v !== '')),
        ]);
    }

    /** Fin de anketo_form_register.php: アンケート管理 «登録中です» → 詳細; お客様アンケート vuelve a la lista del alta */
    private function registered(Request $request, AnketoFamily $family, int $pcd, int $cd, bool $new): View|RedirectResponse
    {
        if ($family->isCustomer()) {
            return redirect()->to($family->route('forms.index', array_filter([
                'register_type' => 'new', 'page' => $request->input('page'), 'pcd' => $pcd,
            ], fn ($v) => (string) $v !== '')));
        }

        return view('admin.promocion.anketo.anketo_done', [
            'family' => $family,
            'heading' => '',
            'message' => '登録中です。',
            'seconds' => 2,
            'next' => $family->route('forms.show', ['cd' => $cd, 'register_type' => $new ? 'new' : 'update']),
        ]);
    }

    /** anketo_form_delete.php (el legacy: enlace GET con confirmación; aquí POST): DELETE físico */
    public function destroy(Request $request, string $family): View|RedirectResponse
    {
        $family = AnketoFamily::from($family);
        $pcd = (int) $request->input('pcd');
        $this->items->delete($family, $pcd, (int) $request->input('cd'));
        $next = $family->route('forms.index', ($family->isCustomer() ? ['register_type' => 'new'] : []) + ['pcd' => $pcd] + $this->listQuery($request, $family));

        if ($family->isCustomer()) {
            return redirect()->to($next);
        }

        return view('admin.promocion.anketo.anketo_done', [
            'family' => $family,
            'heading' => '',
            'message' => '削除中です。',
            'seconds' => 2,
            'next' => $next,
        ]);
    }

    /** anketo_form_detail.php */
    public function show(Request $request, string $family): View|RedirectResponse
    {
        $family = AnketoFamily::from($family);
        $form = $family->forms()->withDeleted()->find((int) $request->input('cd'));
        if (! $form) {
            return redirect('/system/category3/index.php');
        }
        $cate = $family->cates()->find($form->tb_c3_anketo_cate_cd);

        return view('admin.promocion.anketo.anketo_form_detail', [
            'family' => $family,
            'title' => '『'.($cate?->name ?? '').'』'.($family->isCustomer() ? '項目' : ' 入力フォーム部品設定'),
            'form' => $form,
            'data' => $this->items->formData($form),
            'registerType' => (string) $request->input('register_type'),
            'kensu' => (string) $request->input('kensu'),
            'page' => (string) $request->input('page'),
        ]);
    }

    /** $add_getstr del legacy: page, kensu y which de la lista de encuestas */
    private function listQuery(Request $request, AnketoFamily $family): array
    {
        return array_filter([
            'page' => (string) $request->input('page'),
            'kensu' => (string) $request->input('kensu'),
            'which' => $family->isCustomer() ? '' : (string) $request->input('which'),
        ], fn ($v) => $v !== '');
    }
}
