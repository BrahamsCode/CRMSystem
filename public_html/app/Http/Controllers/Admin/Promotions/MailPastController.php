<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Models\AdminMaster;
use App\Models\Promotions\MailmagMaster;
use App\Services\Promotions\ClickStats;
use App\Services\Promotions\MailImageStore;
use App\Services\Promotions\StaffContext;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 過去配信 de メルマガ / デコメール: lista con búsqueda y borrado (inc/mailmag_list.php, inc/ajax_mail_delete.php),
 * detalle con la クリックカウンタ (inc/mailmag_shousai2.php + graph_sql.php, CSV csv_shousai_download.php)
 * y destinatarios con su CSV (inc/mailmag_sended_list.php).
 */
class MailPastController extends Controller
{
    public function __construct(private readonly StaffContext $staff) {}

    public function index(Request $request, string $mailType = 'mail'): View
    {
        $from = $this->date($request, 'from');
        $to = $this->date($request, 'to');
        $texto = (string) $request->input('texto', '');
        $perPage = max(1, (int) $request->input('kensu', 20));

        $query = MailmagMaster::query()
            ->whereRaw("COALESCE(disp_flg,'') <> '9'")
            ->where('配信状況', MailmagMaster::STATUS_SENT)
            ->when(! $this->staff->isMaster(), fn ($q) => $q->where('登録者', (string) $this->staff->shopCd()))
            ->whereRaw("COALESCE(type_flg,'') = ?", [match ($mailType) { 'mycoupon' => 'coupon', 'deco' => 'deco', default => '' }])
            ->when($from, fn ($q) => $q->where('配信日', '>=', $from))
            ->when($to, fn ($q) => $q->where('配信日', '<=', $to))
            ->when($texto !== '', fn ($q) => $q->where(fn ($w) => $w->where('タイトル', 'like', "%$texto%")->orWhere('pc内容', 'like', "%$texto%")))
            ->orderByDesc('配信日時');

        $mails = $query->paginate($perPage, ['*'], 'page', max(1, (int) $request->input('page', 1)));
        $cds = $mails->pluck('cd')->all();

        $minYear = (int) DB::table('mailmag_master')->whereRaw("coalesce(配信日, '') <> ''")->selectRaw("MIN(split_part(配信日,'/',1)) AS min")->value('min');
        $year = (int) now(\App\Services\Promotions\LegacyTime::ZONE)->year;

        return view('admin.promocion.inc.mailmag_list', [
            'mailType' => $mailType,
            'toolName' => $this->toolName(),
            'pageTitle' => $mailType === 'deco' ? '過去デコメール配信' : '過去'.$this->toolName().'配信',
            'mails' => $mails,
            'perPage' => $perPage,
            'filters' => ['from' => $from, 'to' => $to, 'texto' => $texto],
            'minYear' => ($minYear < 2009 || $minYear > $year) ? $year : $minYear,
            'maxYear' => $year,
            'isMaster' => $this->staff->isMaster(),
            'canDelete' => $this->can($mailType === 'deco' ? 1006 : 1005, 4),
            'shopNames' => $this->shopNames(),
            'clicks' => $cds === [] ? [] : DB::table('tb_mailmag_analyze')->whereIn('mailmag_master_cd', $cds)->groupBy('mailmag_master_cd')->selectRaw('mailmag_master_cd, count(*) AS n')->pluck('n', 'mailmag_master_cd')->all(),
            'appEnabled' => (int) AdminMaster::current()->application_use_flg === 1,
            'pushStats' => $cds === [] ? [] : DB::table('push_notifications')->where('type', 'mailmaga')->whereIn('type_master_cd', $cds)
                ->groupBy('type_master_cd')->selectRaw('type_master_cd, sum(open_cnt) AS nobe, count(open_cnt) AS real, count(*) AS total')->get()->keyBy('type_master_cd')->all(),
            'routes' => $this->routes($mailType),
        ]);
    }

    /** ajax_mail_delete.php: disp_flg = '9' de los cd dados (separados por coma) */
    public function destroy(Request $request): Response
    {
        $cds = array_values(array_filter(array_map('intval', explode(',', (string) $request->input('dataCd')))));
        if ($cds !== []) {
            MailmagMaster::query()->whereIn('cd', $cds)->update(['disp_flg' => '9']);
        }

        return response('');
    }

    public function show(Request $request, ClickStats $stats, MailImageStore $images, string $mailType = 'mail'): View
    {
        $mail = MailmagMaster::query()->findOrFail((int) $request->input('mailmagcd'));
        $folder = MailImageStore::folder($mailType);

        return view('admin.promocion.inc.mailmag_shousai2', [
            'mailType' => $mailType,
            'toolName' => $this->toolName(),
            'pageTitle' => ($mailType === 'deco' ? '過去デコメール配信' : '過去'.$this->toolName().'配信').'詳細',
            'mail' => $mail,
            'stats' => $stats->forMail($mail->cd),
            'imageUrl' => (string) $mail->getAttribute('画像有無') === '1' ? $images->url($folder, (string) $mail->getAttribute('店id'), $mail->getAttribute('画像名小')) : null,
            'imagePath' => (string) $mail->getAttribute('画像有無') === '1' ? storage_path('app/public/system/category3/'.$folder.'/uploads/'.$mail->getAttribute('店id').'/'.$mail->getAttribute('画像名小')) : null,
            'couponTitle' => $mail->type_data_cd ? DB::table('tb_mycoupon_master')->where('cd', $mail->type_data_cd)->value('title') : null,
            'shopNames' => $this->shopNames(),
            'isMaster' => $this->staff->isMaster(),
            'canCreate' => $this->can(1005, 2),
            'backQuery' => $request->except(['mailmagcd']),
            'routes' => $this->routes($mailType),
        ]);
    }

    /** csv_shousai_download.php: clics de la お知らせ (SJIS-win, celdas con tabulador inicial) */
    public function clicksCsv(Request $request): StreamedResponse
    {
        $mail = MailmagMaster::query()->findOrFail((int) $request->input('mailmagcd'));

        return response()->streamDownload(function () use ($mail) {
            $sjis = fn (string $s) => mb_convert_encoding($s, 'SJIS-win', 'UTF-8');
            [$title, $sub] = match ((string) $mail->type_flg) {
                'coupon' => ['クーポン付きメルマガ', '添付クーポンID: '.$mail->type_data_cd],
                'anketo' => ['アンケート付きメルマガ', '添付アンケートID: '.$mail->type_data_cd],
                '' => ['普通メールマガ', ''],
                default => ['', ''],
            };
            $shop = (string) DB::table('tb_shop')->where('cd', (int) $mail->getAttribute('登録者'))->value('kaishamei');
            $lines = [$title, 'メールCD: '.$mail->cd, 'メールタイトル: '.$mail->getAttribute('タイトル'), 'メール内容: '.$mail->getAttribute('pc内容')];
            if ($sub !== '') {
                $lines[] = $sub;
            }
            array_push($lines, '登録者: '.$shop, '備考: '.$mail->getAttribute('備考'), '配信日時: '.$mail->getAttribute('配信日時'));
            foreach ($lines as $i => $line) {
                echo '"'.$sjis($line).'"'."\r\n".($i === count($lines) - 1 ? "\r\n" : '');
            }
            echo implode(',', array_map(fn ($h) => "\"\t".$sjis($h).'"', ['ユーザID', 'アクセス日時', '年齢', '性別', '職業']))."\r\n";

            $rows = DB::table('tb_mailmag_analyze')->where('mailmag_master_cd', $mail->cd)->orderByDesc('touroku_date')
                ->get(['tb_customer_cd', 'touroku_date', 'customer_age', 'customer_sex', 'customer_job']);
            foreach ($rows as $row) {
                $cells = (array) $row;
                $cells['touroku_date'] = date('Y/m/d H:i', (int) $cells['touroku_date']);
                echo implode(',', array_map(fn ($v) => "\"\t".$sjis((string) $v).'"', $cells))."\r\n";
            }
        }, 'user.csv', ['Content-Type' => 'text/x-csv']);
    }

    /** mail_sended_list.php: destinatarios registrados en mailmag_sended_log con su clic; csvFlg = 1 descarga */
    public function recipients(Request $request, string $mailType = 'mail'): View|StreamedResponse|Response
    {
        $mail = MailmagMaster::query()->find((int) $request->input('mailmagcd'));
        if (! $mail) {
            return response('不正アクセス！！！', 200, ['Refresh' => '2; url='.$this->routes($mailType)['past']]);
        }

        $query = DB::table('mailmag_sended_log as tb1')
            ->join('tb_customer as tb2', 'tb1.tb_customer_cd', '=', 'tb2.cd')
            ->leftJoin('tb_mailmag_analyze as tb3', function ($join) {
                $join->on('tb1.mailmag_master_cd', '=', 'tb3.mailmag_master_cd')->on('tb1.tb_customer_cd', '=', 'tb3.tb_customer_cd');
            })
            ->where('tb1.mailmag_master_cd', $mail->cd)
            ->select(['tb1.*', 'tb2.id', 'tb2.kanri_id', 'tb2.name', 'tb2.kojin_birth', 'tb3.touroku_date as click_time'])
            ->orderByRaw('click_time DESC NULLS LAST')->orderByDesc('tb2.cd');

        if ($request->input('csvFlg') == '1') {
            $title = $mail->getAttribute('配信日時').'配信'.$this->toolName().'　(ID: '.$mail->cd.')';

            return response()->streamDownload(function () use ($query, $title) {
                $csv = $title."\r\n";
                $rows = $query->get();
                if ($rows->isNotEmpty()) {
                    $csv .= "顧客ID,管理番号,顧客名,配信先メールアドレス,生年月日,開封\r\n";
                    foreach ($rows as $r) {
                        $csv .= $r->id.",\t".$r->kanri_id.','.$r->name.','.$r->email.",\t".$r->kojin_birth.','.($r->click_time ? '○' : '')."\r\n";
                    }
                }
                echo mb_convert_encoding($csv, 'SJIS-win', 'UTF-8');
            }, 'detail_'.now(\App\Services\Promotions\LegacyTime::ZONE)->format('YmdHis').'.csv', ['Content-Type' => 'text/x-csv']);
        }

        return view('admin.promocion.inc.mailmag_sended_list', [
            'mailType' => $mailType,
            'toolName' => $this->toolName(),
            'pageTitle' => '過去'.$this->toolName().'配信',
            'mail' => $mail,
            'rows' => $query->paginate(50, ['*'], 'page', max(1, (int) $request->input('page', 1))),
            'backQuery' => $request->except(['mailmagcd', 'page', 'csvFlg']),
            'routes' => $this->routes($mailType),
        ]);
    }

    // ------------------------------------------------------------------

    /** from=Y/m/d o from_year/from_month/from_day → Y/m/d válido (corrige 02/30 como strtotime) o null */
    private function date(Request $request, string $prefix): ?string
    {
        $value = $request->input($prefix);
        if ($value === null) {
            [$y, $m, $d] = [$request->input($prefix.'_year'), $request->input($prefix.'_month'), $request->input($prefix.'_day')];
            if ((string) $y === '' || (string) $m === '' || (string) $d === '') {
                return null;
            }
            $value = "$y/$m/$d";
        }
        $time = strtotime((string) $value);

        return $time === false ? null : date('Y/m/d', $time);
    }

    private function routes(string $mailType): array
    {
        $prefix = 'admin.promocion.'.($mailType === 'deco' ? 'deco' : 'mail').'.';

        return [
            'past' => route($prefix.'past'),
            'show' => route($prefix.'show'),
            'recipients' => route($prefix.'recipients'),
            'clicksCsv' => route('admin.promocion.mail.clicks-csv'),
            'select' => route($prefix.'select'),
            'delete' => route('legacy.promocion.inc.ajax_mail_delete'),
        ];
    }

    private function toolName(): string
    {
        return (int) AdminMaster::current()->application_use_flg === 1 ? 'お知らせ' : 'メール';
    }

    private function can(int $contentsCd, int $function): bool
    {
        return $this->staff->isMaster() || Gate::allows('kengen.'.$contentsCd.'.'.$function);
    }

    private function shopNames(): array
    {
        return DB::table('tb_shop')->pluck('kaishamei', 'cd')->all() + ['-1' => (string) DB::table('tb_office')->where('cd', 1)->value('kaishamei')];
    }
}
