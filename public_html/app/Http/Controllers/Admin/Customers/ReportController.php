<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Services\Customers\Reports\CustomerReportService;
use App\Services\Promotions\MailAudience;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * 集計ツール (`system/category1/shukei/`): shukei_list.php (lista con menú) y las ventanas que abre
 * (model1, model2, point1, stamp1, point2: sin cabecera ni menú, formulario GET a la misma URL), más
 * model1_select.php (guarda el filtro de メール y devuelve su uniqid). Lógica en CustomerReportService.
 */
class ReportController extends Controller
{
    public function __construct(private readonly CustomerReportService $reports) {}

    /** shukei_list.php */
    public function index(Request $request): View
    {
        return view('admin.clientes.reports.index', [
            'links' => $this->reports->links(),
            'isTablet' => self::isTablet($request),
            'host' => $request->getSchemeAndHttpHost(),
        ]);
    }

    /** model1.php: respuestas de un campo de opciones (?f=) */
    public function model1(Request $request): View|Response
    {
        $form = $this->reports->searchForm('model1', $request->query('f'));
        if ($form === null) {
            return response('カスタマイズデータがありません！！！');
        }
        $graphType = CustomerReportService::graphType($form);
        if ($graphType === null) {
            return response('カスタマイズデータから集計を出しません！！！');
        }

        $shops = $this->reports->shops();

        return view('admin.clientes.reports.model1', $this->formData($request, $shops, 'tb_raiten_rireki', 'raiten_time')
            + $this->reports->model1($form, $request->query(), $this->staff($request), $shops)
            + ['form' => $form, 'graphType' => $graphType]);
    }

    /** model1_select.php: filtro de la メール del bloque u opción → uniqid (texto) para 内容設定 de S3 */
    public function model1Select(Request $request, MailAudience $audience): Response
    {
        $form = $this->reports->searchForm('model1', $request->input('f'));
        abort_if($form === null, 400);

        $in = $request->except(['_token', 'otherCondition', 'conditionText']);
        $where = $this->reports->mailWhere($form, $in, (string) $request->input('otherCondition'), $this->staff($request));

        return response($audience->storeRaw($where, null, (string) $request->input('conditionText')));
    }

    /** model2.php: campo numérico (?f=) */
    public function model2(Request $request): View|Response
    {
        $form = $this->reports->searchForm('model2', $request->query('f'));
        if ($form === null) {
            return response('カスタマイズデータがありません！！！');
        }

        $shops = $this->reports->shops();

        return view('admin.clientes.reports.model2', $this->formData($request, $shops, 'tb_raiten_rireki', 'raiten_time')
            + $this->reports->model2($form, $request->query(), $this->staff($request), $shops)
            + ['form' => $form]);
    }

    /** point1.php: 【会計金額】集計 de visitas con puntos */
    public function point1(Request $request): View
    {
        return $this->kaikei($request, 'point');
    }

    /** stamp1.php: 【会計金額】集計 de visitas con sellos */
    public function stamp1(Request $request): View
    {
        return $this->kaikei($request, 'stamp');
    }

    /** point2.php: 【ポイント】集計 por tienda y periodo */
    public function point2(Request $request): View
    {
        $shops = $this->reports->shops();
        $minYear = $this->reports->minYear('tb_point_log', 'touroku_date');

        return view('admin.clientes.reports.point2', $this->formData($request, $shops, null, null) + [
            'minYear' => $minYear,
            'kikanSent' => $request->has('kikan_type'),
            'result' => $this->reports->point2($request->query(), $this->staff($request), $shops, $minYear),
        ]);
    }

    private function kaikei(Request $request, string $kind): View
    {
        $shops = $this->reports->shops();

        return view('admin.clientes.reports.kaikei', $this->formData($request, $shops, 'tb_raiten_rireki', 'raiten_time')
            + $this->reports->kaikei($kind, $request->query(), $this->staff($request), $shops)
            + ['kind' => $kind]);
    }

    /** Variables del formulario de búsqueda comunes a las ventanas */
    private function formData(Request $request, $shops, ?string $yearTable, ?string $yearColumn): array
    {
        $staff = $this->staff($request);

        return [
            'in' => $request->query(),
            'shops' => $shops,
            'isMaster' => $staff->isMaster(),
            'staffShopCd' => $staff->shop_cd,
            'staffShopName' => (string) $staff->shop?->kaishamei,
            'selfUrl' => $request->url(),
            'years' => $yearTable === null ? [] : range((int) now('Asia/Tokyo')->format('Y'), $this->reports->minYear($yearTable, $yearColumn)),
        ];
    }

    /** $isTablet de kihon.php: cUserAgent DEVICE_NAME en ('CRMRW107', 'CRMH580VT_A') */
    private static function isTablet(Request $request): bool
    {
        $agent = (string) $request->userAgent();

        return str_contains($agent, 'CRMRW107') || str_contains($agent, 'CRMH580VT_A');
    }

    private function staff(Request $request): Staff
    {
        return $request->user('admin');
    }
}
