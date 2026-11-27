<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Services\Promotions\AnketoAnswerService;
use App\Services\Promotions\AnketoFamily;
use App\Services\Promotions\AnketoSurveyService;
use App\Services\Promotions\LegacyTime;
use App\Services\Promotions\StaffContext;
use App\Support\LegacyCsv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Respuestas: anketo_answer_analyze.php (回答解析), anketo_answer_shousai.php (respuestas de texto, popup),
 * csv_answer_download.php (CSV SJIS-win) y, solo お客様アンケート, anketo_answer_list.php (回答一覧 con 返信)
 * y anketo_answer_detail.php (回答内容詳細, popup).
 */
class AnketoAnswerController extends Controller
{
    public function __construct(
        private readonly AnketoAnswerService $answers,
        private readonly AnketoSurveyService $surveys,
    ) {}

    /** anketo_answer_analyze.php */
    public function analyze(Request $request, StaffContext $staff, string $family): View|RedirectResponse
    {
        $family = AnketoFamily::from($family);
        $cate = $this->cate($family, $request);
        if (! $cate) {
            return redirect()->to($family->route('index'));
        }
        $forms = $family->forms()->where('tb_c3_anketo_cate_cd', $cate->cd)->where('disp_flg', '1')->orderBy('rank')->get();

        // アンケート管理: 回答状況 solo desde 過去アンケート配信 (which=sended); お客様アンケート: siempre (el legacy lo dejaba vacío)
        $withStatus = $family->isCustomer() || $request->input('which') === 'sended';

        return view('admin.promocion.anketo.anketo_answer_analyze', [
            'family' => $family,
            'cate' => $cate,
            'items' => $forms->map(fn ($form) => ['form' => $form, 'stats' => $this->answers->itemStats($family, $form)]),
            'respondents' => $withStatus ? $this->surveys->respondents($family, $cate->cd) : null,
            'delivered' => $withStatus && ! $family->isCustomer() ? (int) ($this->surveys->mail($cate->cd)?->{'配信総数'} ?? 0) : null,
            'isMaster' => $staff->isMaster(),
            'listQuery' => array_filter([
                'page' => (string) $request->input('page'),
                'kensu' => (string) $request->input('kensu'),
                'which' => $family->isCustomer() ? '' : (string) $request->input('which'),
            ], fn ($v) => $v !== ''),
        ]);
    }

    /** anketo_answer_shousai.php: popup con las respuestas de texto de un ítem (page_control.php) */
    public function texts(Request $request, string $family): View
    {
        $family = AnketoFamily::from($family);
        $formCd = (int) $request->input('frmcd');
        $kensu = max(1, (int) ($request->input('kensu') ?: 20));
        $query = $this->answers->texts($family, $formCd);
        $total = (clone $query)->count();
        $maxPage = $total > 0 ? (int) ceil($total / $kensu) : 1;
        $page = min(max(1, (int) ($request->input('page') ?: 1)), $maxPage);

        return view('admin.promocion.anketo.anketo_answer_shousai', [
            'family' => $family,
            'formCd' => $formCd,
            'pcd' => (string) $request->input('pcd'),
            'rows' => $query->forPage($page, $kensu)->get(),
            'total' => $total,
            'page' => $page,
            'kensu' => $kensu,
            'maxPage' => $maxPage,
            'from' => ($page - 1) * $kensu,
        ]);
    }

    /** csv_answer_download.php: anketo_answer.csv en SJIS-win (sin encuesta: respuesta vacía, como el exit del legacy) */
    public function csv(Request $request, string $family): StreamedResponse|Response
    {
        $family = AnketoFamily::from($family);
        $cate = $this->cate($family, $request);
        if (! $cate) {
            return response('');
        }
        $forms = $family->forms()->where('tb_c3_anketo_cate_cd', $cate->cd)->where('disp_flg', '1')->orderBy('rank')->get();
        $lines = $this->answers->csvLines($family, $cate, $forms);

        return response()->streamDownload(function () use ($lines) {
            foreach ($lines as $line) {
                echo LegacyCsv::encode($line)."\r\n";
            }
        }, 'anketo_answer.csv', ['Content-Type' => 'text/x-csv']);
    }

    /** anketo_answer_list.php (お客様アンケート): búsqueda por fecha de registro, 内訳 de 回答解析 y casillas de 返信 */
    public function index(Request $request, string $family): View|RedirectResponse
    {
        $family = AnketoFamily::from($family);
        abort_unless($family->isCustomer(), 404);
        $pcd = (string) $request->input('pcd');
        if (! preg_match('/^[0-9]+$/', $pcd)) {
            return redirect()->to($family->route('index'));
        }

        $in = $request->except(['_token']);
        [$from, $to] = $this->period($in);
        $fcd = (string) ($in['fcd'] ?? '') !== '' ? (int) $in['fcd'] : null;
        $result = $this->answers->answers($family, (int) $pcd, ['from' => $from, 'to' => $to, 'fcd' => $fcd, 'answer' => rawurldecode((string) ($in['answer'] ?? ''))]);

        $kensu = max(1, (int) ($in['kensu'] ?? 0) ?: 20);
        $total = count($result['answers']);
        $page = max(1, (int) ($in['page'] ?? 0) ?: 1);
        // El legacy cortaba con array_slice($a, $from, $from + $kensu): mostraba de más a partir de la página 2
        $answers = array_slice($result['answers'], ($page - 1) * $kensu, $kensu);

        $minEpoch = DB::table($family->subTable())->where('tb_c3_anketo_cate_cd', (int) $pcd)->where('disp_flg', '1')
            ->selectRaw('MIN(CAST(touroku_date AS bigint)) AS min')->value('min');

        // $add_getstr / $add_poststr: el resto de parámetros (filtros, which, page2, kensu2, fcd, answer)
        $carry = collect($in)->except(['pcd', 'page', 'kensu', 'act', 'reply_mail', 'check_all'])
            ->reject(fn ($v) => is_array($v))->map(fn ($v) => (string) $v)->all();

        return view('admin.promocion.anketo2.anketo_answer_list', [
            'family' => $family,
            'pcd' => $pcd,
            'in' => $in,
            'items' => $family->forms()->where('tb_c3_anketo_cate_cd', (int) $pcd)->where('disp_flg', '1')->where('hyouji_flg', '-1')->orderBy('rank')->get(),
            'answers' => $answers,
            'customers' => $result['customers'],
            'total' => $total,
            'page' => $page,
            'kensu' => $kensu,
            'maxPage' => (int) ceil($total / $kensu),
            'from' => ($page - 1) * $kensu,
            'carry' => $carry,
            'checked' => array_map('strval', array_keys((array) ($in['reply_mail'] ?? []))),
            'minYear' => $minEpoch ? (int) Carbon::createFromTimestamp((int) $minEpoch, LegacyTime::ZONE)->format('Y') : 1970,
            'thisYear' => (int) Carbon::now(LegacyTime::ZONE)->format('Y'),
        ]);
    }

    /** anketo_answer_detail.php (お客様アンケート): respuesta de un cliente, popup */
    public function show(Request $request, string $family): View|RedirectResponse
    {
        $family = AnketoFamily::from($family);
        abort_unless($family->isCustomer(), 404);
        $cate = $this->cate($family, $request);
        if (! $cate) {
            return redirect()->to($family->route('index'));
        }

        return view('admin.promocion.anketo2.anketo_answer_detail', [
            'family' => $family,
            'items' => $family->forms()->where('tb_c3_anketo_cate_cd', $cate->cd)->where('disp_flg', '1')->orderBy('rank')->get(),
            'answer' => $this->answers->answer($family, $cate->cd, (int) $request->input('ccd'), (string) $request->input('group')),
        ]);
    }

    /** Encuesta de `pcd` (solo vivas, disp_flg = 1) */
    private function cate(AnketoFamily $family, Request $request): ?object
    {
        $pcd = (string) $request->input('pcd');

        return preg_match('/^[0-9]+$/', $pcd) ? $family->cates()->whereKey((int) $pcd)->where('disp_flg', '1')->first() : null;
    }

    /**
     * Periodo de anketo_answer_list.php: si se rellena algún campo, los vacíos se completan (inicio: enero, día 1, 00:00;
     * fin: diciembre, día 31, 23:59) y el fin solo cuenta si es posterior al inicio. Fechas con Carbon (no mktime).
     *
     * @return array{0: ?int, 1: ?int}
     */
    private function period(array &$in): array
    {
        $year = (int) Carbon::now(LegacyTime::ZONE)->format('Y');
        $build = function (string $prefix, array $defaults, int $second) use (&$in, $year): ?int {
            $keys = [$prefix.'_year', $prefix.'_month', $prefix.'_day', $prefix.'_h', $prefix.'_i'];
            if (collect($keys)->every(fn ($k) => (string) ($in[$k] ?? '') === '')) {
                return null;
            }
            foreach (array_combine($keys, [$year, ...$defaults]) as $k => $default) {
                if ((string) ($in[$k] ?? '') === '') {
                    $in[$k] = (string) $default;
                }
            }
            $at = Carbon::create((int) $in[$keys[0]], 1, 1, 0, 0, 0, LegacyTime::ZONE)
                ->addMonths((int) $in[$keys[1]] - 1)->addDays((int) $in[$keys[2]] - 1)
                ->addHours((int) $in[$keys[3]])->addMinutes((int) $in[$keys[4]])->addSeconds($second);

            return $at->getTimestamp();
        };

        $from = $build('from', ['01', '01', '00', '00'], 0);
        $to = $build('to', ['12', '31', '23', '59'], 59);
        if ($to !== null && $from !== null && $from >= $to) {
            $to = null;
        }

        return [$from, $to];
    }
}
