<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Promotions\VisitCsvRequest;
use App\Services\Promotions\StaffContext;
use App\Services\Promotions\VisitCsvService;
use App\Services\Promotions\VisitStatsService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** CSV de 来店履歴管理 (csv_download.php, storecsv_download.php, daycsv_download.php), en SJIS-win */
class VisitCsvController extends Controller
{
    public function __construct(
        private readonly StaffContext $staff,
        private readonly VisitCsvService $csv,
        private readonly VisitStatsService $stats,
    ) {}

    /** csv_download.php: el botón solo se muestra a master; aquí también se exige (el legacy solo lo ocultaba) */
    public function visits(VisitCsvRequest $request): StreamedResponse
    {
        abort_unless($this->staff->isMaster(), 403);
        $selectedDay = $request->validated('selected_day');

        return $this->csv->visits(
            (int) $request->validated('shop_cd'),
            (int) $request->validated('start_time'),
            (int) $request->validated('end_time'),
            $selectedDay !== null ? (string) $selectedDay : null,
            (string) ($request->validated('kikan') ?? '0'),
        );
    }

    /** storecsv_download.php (kikan, date) */
    public function shops(Request $request): StreamedResponse
    {
        $kikan = in_array((string) $request->input('kikan'), ['0', '1', '2'], true) ? (int) $request->input('kikan') : 0;

        return $this->csv->shops($kikan, is_numeric($request->input('date')) ? (int) $request->input('date') : null);
    }

    /** daycsv_download.php (shop_cd, kikan, date): la tabla 来客数一覧 de day_visit_list.php */
    public function days(Request $request): StreamedResponse
    {
        $data = $this->stats->day($request->only(['shop_cd', 'kikan', 'date']), $this->staff->isMaster(), $this->staff->shopCd());
        abort_if($data['table'] === null, 404);

        return $this->csv->days($data);
    }
}
