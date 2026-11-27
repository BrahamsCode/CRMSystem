<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Promotions\AnketoCateRequest;
use App\Services\Promotions\AnketoFamily;
use App\Services\Promotions\AnketoSurveyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Cabecera de la encuesta (アンケート管理 `anketo/` y お客様アンケート管理 `anketo2/`, parámetro de ruta `family`):
 * anketo_cate_list.php (一覧 y 過去アンケート配信一覧), anketo_cate_new.php / _update.php (+ _frm, _errchk, _register),
 * anketo_cate_action.php, anketo_cate_detail.php y ajax_set_anketo.php (お客様アンケート: «選択»).
 * Como el legacy, las páginas solo exigen sesión; el menú es el que filtra por permiso (1011).
 */
class AnketoCateController extends Controller
{
    public function __construct(private readonly AnketoSurveyService $surveys) {}

    /** anketo_cate_list.php (which=sended: 過去アンケート配信一覧, solo アンケート管理) */
    public function index(Request $request, string $family): View
    {
        $family = AnketoFamily::from($family);
        // お客様アンケート: el legacy fuerza «enviado» en los enlaces y muestra siempre la misma lista
        $sended = ! $family->isCustomer() && $request->input('which') === 'sended';
        $kensu = max(1, (int) ($request->input('kensu') ?: 20));

        $query = $family->cates()
            ->when($family->isCustomer(), fn ($q) => $q->where('disp_flg', '1'))
            ->when($sended,
                fn ($q) => $q->where('hyouji_flg', '1')->whereIn('cd', fn ($s) => $s->from('mailmag_master')->select('type_data_cd')->where('type_flg', 'anketo')),
                fn ($q) => $q->whereNull('hyouji_flg'));
        $total = (clone $query)->count();
        $page = max(1, (int) ($request->input('page') ?: 1));
        $cates = $query->orderByDesc('cd')->forPage($page, $kensu)->get();

        $extra = [];
        if ($sended) {
            foreach ($cates as $cate) {
                $mail = $this->surveys->mail($cate->cd);
                $extra[$cate->cd] = [
                    'respondents' => $this->surveys->respondents($family, $cate->cd),
                    'total' => (int) ($mail?->{'配信総数'} ?? 0),
                    'display' => (string) ($mail?->search_display ?? ''),
                ];
            }
        }

        return view('admin.promocion.anketo.anketo_cate_list', [
            'family' => $family,
            'pageTitle' => $sended ? '過去アンケート配信' : $family->title(),
            'sended' => $sended,
            'which' => $family->isCustomer() ? 'sended' : ($sended ? 'sended' : ''),
            'cates' => $cates,
            'extra' => $extra,
            'total' => $total,
            'page' => $page,
            'kensu' => $kensu,
            'maxPage' => (int) ceil($total / $kensu),
            'from' => max(0, ($page - 1) * $kensu),
            'activeCd' => $family->isCustomer() ? $this->surveys->activeCd() : null,
            'toolNames' => $this->surveys->toolNames(),
        ]);
    }

    /** anketo_cate_new.php: formulario y, con act=up, comprobación y registro */
    public function create(AnketoCateRequest $request, string $family): View|RedirectResponse
    {
        return $this->form($request, AnketoFamily::from($family), null);
    }

    /** anketo_cate_update.php */
    public function edit(AnketoCateRequest $request, string $family): View|RedirectResponse
    {
        return $this->form($request, AnketoFamily::from($family), (int) $request->input('cd'));
    }

    private function form(AnketoCateRequest $request, AnketoFamily $family, ?int $cd): View|RedirectResponse
    {
        $errors = [];
        if ($request->input('act') === 'up') {
            $data = $request->legacyData();
            $errors = $request->legacyErrors(! $family->isCustomer());
            if ($errors === []) {
                $cate = $this->surveys->save($family, $data, $cd);

                return $this->registered($family, $cate->cd, $cd === null);
            }
        } elseif ($cd !== null) {
            $cate = $family->cates()->find($cd);
            if (! $cate) {
                return redirect('/system/category3/index.php');
            }
            $data = $cate->getAttributes();
            $data['start_date'] = AnketoSurveyService::date($cate->start_date);
            $data['end_date'] = AnketoSurveyService::date($cate->end_date);
        } else {
            $data = [];
        }

        return view('admin.promocion.anketo.anketo_cate_frm', [
            'family' => $family,
            'mode' => $cd === null ? '新規登録' : '修正',
            'action' => $family->route($cd === null ? 'create' : 'edit'),
            'back' => $cd === null
                ? $family->route('index')
                : $family->route('forms.index', ['pcd' => $cd, 'page' => (string) $request->input('page'), 'kensu' => (string) $request->input('kensu')]),
            'data' => $data,
            'errors' => $errors,
            'cd' => $cd,
        ]);
    }

    /** Fin de anketo_cate_register.php: アンケート管理 muestra «登録中です»; お客様アンケート va a cargar los ítems */
    private function registered(AnketoFamily $family, int $cd, bool $new): View|RedirectResponse
    {
        if ($family->isCustomer()) {
            return redirect()->to($family->route('forms.index', $new ? ['register_type' => 'new', 'pcd' => $cd] : ['pcd' => $cd]));
        }

        return view('admin.promocion.anketo.anketo_done', [
            'family' => $family,
            'heading' => $family->title().' '.($new ? '新規登録' : '修正'),
            'message' => '登録中です。',
            'seconds' => 1,
            'next' => $family->route('forms.index', ['pcd' => $cd]),
        ]);
    }

    /** anketo_cate_action.php: 発行 / 削除 / 複製 / 登録 y «処理中です» de vuelta a la lista */
    public function action(Request $request, string $family): View
    {
        $family = AnketoFamily::from($family);
        $act = (string) $request->input('act');
        $this->surveys->action($family, $act, (int) $request->input('anketo_cate_cd'));

        return view('admin.promocion.anketo.anketo_done', [
            'family' => $family,
            'heading' => $family->title().' '.$act,
            'message' => '処理中です。',
            'seconds' => 2,
            'next' => $family->route('index', ['page' => (string) $request->input('page'), 'kensu' => (string) $request->input('kensu')]),
        ]);
    }

    /** anketo_cate_detail.php */
    public function show(Request $request, string $family): View|RedirectResponse
    {
        $family = AnketoFamily::from($family);
        $cate = $family->cates()->withDeleted()->find((int) $request->input('cd'));
        if (! $cate) {
            return redirect('/system/category3/index.php');
        }

        return view('admin.promocion.anketo.anketo_cate_detail', [
            'family' => $family,
            'cate' => $cate,
            'kensu' => (string) $request->input('kensu'),
            'page' => (string) $request->input('page'),
        ]);
    }

    /** GET de una .php del legacy (menú left_navi3) → la pantalla nueva con la misma query (which=sended…) */
    public function legacy(Request $request, string $target): RedirectResponse
    {
        return redirect()->to(route($target, $request->query()));
    }

    /** ajax_set_anketo.php (お客様アンケート «選択»): responde «success» como el legacy */
    public function setActive(Request $request): Response
    {
        $this->surveys->setActive($request->input('anketo_cd'));

        return response('success');
    }
}
