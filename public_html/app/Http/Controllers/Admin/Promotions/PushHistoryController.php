<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Services\Promotions\PushImageStore;
use App\Services\Promotions\PushReport;
use App\Support\LegacyCsv;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * プッシュ集計 (push_history.php) y su detalle por push (push_history_detail.php, con CSV csv_print = 1).
 */
class PushHistoryController extends Controller
{
    public function __construct(private readonly PushReport $report) {}

    public function index(Request $request): View
    {
        $query = $this->report->history();
        [$kensu, $page, $maxrows] = PushController::paging($request, (clone $query)->count());
        $rows = $query->forPage($page, $kensu)->get()->map(function ($row) {
            $row->body = $this->body($row);
            $row->audience = $this->report->audienceCount($row->wherestr);

            return $row;
        });

        return view('admin.promocion.push.push_history', [
            'activeAc' => PushController::ACTIVE_AC,
            'rows' => $rows,
            'kensu' => $kensu,
            'page' => $page,
            'maxrows' => $maxrows,
        ]);
    }

    /** Bloques del push: texto escapado e imágenes de hp/uploads/c3/push/<cd>/ */
    private function body(object $row): string
    {
        $blocks = @unserialize((string) $row->message, ['allowed_classes' => false]);
        if (! is_array($blocks)) {
            return '';
        }
        $html = [];
        foreach ($blocks as $value) {
            $value = (string) $value;
            $html[] = app(PushImageStore::class)->exists('push', (int) $row->cd, $value)
                ? '<img width="100%" style="max-width:320px" src="'.e(PushImageStore::url('push', (int) $row->cd, $value)).'" >'
                : e($value);
        }

        return implode("\n\n", $html);
    }

    /** push_history_detail.php?master_cd=…&type=haishin|eturan (eturan: solo los que lo abrieron) */
    public function show(Request $request): View|StreamedResponse
    {
        $cd = (int) $request->query('master_cd');
        $query = $this->report->historyRecipients($cd, $request->query('type') === 'eturan');

        if ((string) $request->query('csv_print') === '1') {
            $cell = fn ($v) => str_contains((string) $v, '{') ? '"'.$v.'"' : (string) $v;

            return LegacyCsv::download(PushReport::CSV_NAME.Carbon::now()->format('YmdHis').'.csv',
                fn () => $query->cursor()->map(fn ($r) => array_map($cell, [$r->name, $r->tel, $r->shop_name, $r->age, $r->kojin_sex, $r->job, $r->touroku_ymd])),
                PushReport::CSV_HEADER,
                ['quote' => 'none', 'eol' => "\n", 'content_type' => 'application/octet-stream']);
        }

        [$kensu, $page, $maxrows] = PushController::paging($request, (clone $query)->count());

        return view('admin.promocion.push.push_history_detail', [
            'activeAc' => PushController::ACTIVE_AC,
            'rows' => $query->forPage($page, $kensu)->get(),
            'kensu' => $kensu,
            'page' => $page,
            'maxrows' => $maxrows,
            'query' => array_filter($request->only(['list_page', 'list_kensu', 'master_cd', 'type']), 'is_string'),
        ]);
    }
}
