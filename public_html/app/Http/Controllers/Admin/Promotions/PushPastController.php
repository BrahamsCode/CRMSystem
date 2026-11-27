<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Services\Promotions\LegacyTime;
use App\Services\Promotions\PushImageStore;
use App\Services\Promotions\PushReport;
use App\Services\Promotions\PushSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * 過去配信内容一覧 (push_past.php → inc/push_list.php) y 過去プッシュ配信詳細 (push_past_shousai.php →
 * inc/push_shousai.php; con data_type = mailmaga, el push espejo de una お知らせ).
 */
class PushPastController extends Controller
{
    public function __construct(private readonly PushReport $report) {}

    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $sort = array_key_exists((string) $request->input('sort_value'), PushReport::SORTS) ? (string) $request->input('sort_value') : 'send_date';
        // El legacy comparaba una variable sin definir: el orden siempre es descendente
        $query = $this->report->past($filters, $sort, 'DESC');
        [$kensu, $page, $maxrows] = PushController::paging($request, (clone $query)->count());
        $rows = $query->forPage($page, $kensu)->get();

        $minYear = (int) DB::table('mailmag_master')->whereRaw("coalesce(配信日, '') <> ''")->selectRaw("MIN(split_part(配信日, '/', 1)) AS y")->value('y');
        $year = (int) Carbon::now(LegacyTime::ZONE)->year;

        return view('admin.promocion.push.push_past', [
            'activeAc' => PushController::ACTIVE_AC,
            'filters' => $filters,
            'sort' => $sort,
            'rows' => $rows,
            'counts' => $this->report->counts($rows->pluck('cd')->map(fn ($cd) => (int) $cd)->all()),
            'kensu' => $kensu,
            'page' => $page,
            'maxrows' => $maxrows,
            'minYear' => $minYear < 2009 || $minYear > $year ? $year : $minYear,
            'maxYear' => $year,
        ]);
    }

    /**
     * Filtros de push_list.php: fecha desde / hasta (from=Y/m/d o from_year/month/day; las fechas imposibles se
     * corrigen como strtotime), título y tipo (ポイント付与 / その他の配信).
     */
    private function filters(Request $request): array
    {
        $date = function (string $prefix, string $time) use ($request): array {
            $parts = $request->filled($prefix) && is_string($request->input($prefix))
                ? array_pad(explode('/', $request->input($prefix)), 3, '')
                : [$request->input($prefix.'_year'), $request->input($prefix.'_month'), $request->input($prefix.'_day')];
            [$y, $m, $d] = array_map(fn ($v) => is_scalar($v) ? (string) $v : '', $parts);
            if ($y === '' || $m === '' || $d === '' || ! ctype_digit($y.$m.$d)) {
                return [null, ['', '', '']];
            }
            [$h, $i, $sec] = array_map('intval', explode(':', $time));
            $at = Carbon::create((int) $y, (int) $m, 1, $h, $i, $sec, LegacyTime::ZONE)->addDays((int) $d - 1);

            return [$at->getTimestamp(), [$at->format('Y'), $at->format('m'), $at->format('d')]];
        };
        [$from, $fromParts] = $date('from', '0:0:0');
        [$to, $toParts] = $date('to', '23:59:59');
        $texto = $request->input('texto');

        return [
            'from' => $from,
            'to' => $to,
            'fromParts' => $fromParts,
            'toParts' => $toParts,
            'texto' => is_string($texto) ? $texto : '',
            'point_add_flg' => $request->input('point_add_flg') === '1',
            'etc_flg' => $request->input('etc_flg') === '1',
        ];
    }

    /** push_past_shousai.php?pushcd=…&data_type=push|mailmaga */
    public function show(Request $request): View
    {
        $cd = (int) $request->query('pushcd');
        $type = $request->query('data_type') === 'mailmaga' ? 'mailmaga' : 'push';
        $push = $type === 'push' ? $this->push($cd) : $this->mail($cd);
        abort_if($push === null, 404);

        $detail = $this->report->detail($cd, $type);

        return view('admin.promocion.push.push_past_shousai', $detail + [
            'activeAc' => PushController::ACTIVE_AC,
            'push' => $push,
            'dataType' => $type,
            'pushCd' => $cd,
            'shopName' => (int) $push['shop_cd'] === -1
                ? (string) DB::table('tb_office')->where('cd', 1)->value('kaishamei')
                : (string) DB::table('tb_shop')->where('cd', (int) $push['shop_cd'])->value('kaishamei'),
            'query' => $request->query(),
        ]);
    }

    /** push_master con los bloques (version 2) ya convertidos a HTML: texto escapado e imágenes con su enlace */
    private function push(int $cd): ?array
    {
        $row = DB::table('push_master')->where('cd', $cd)->first();
        if (! $row) {
            return null;
        }
        $data = (array) $row;
        $data['shop_cd'] = $row->tb_shop_cd;
        $data['body'] = (int) $row->version === 2 ? $this->blocksHtml($row) : nl2br(e((string) $row->message));

        $image = 'hp/uploads/c3/push/'.$cd.'/0.jpg';
        $data['image'] = (string) $row->img_flg !== '' && (int) $row->img_flg !== 0 && Storage::disk('public')->exists($image)
            ? ['url' => '/'.$image, 'size' => @getimagesize(Storage::disk('public')->path($image)) ?: [0, 0]]
            : null;

        return $data;
    }

    private function blocksHtml(object $row): string
    {
        $blocks = @unserialize((string) $row->message, ['allowed_classes' => false]);
        $links = @unserialize((string) $row->pic_link, ['allowed_classes' => false]);
        $links = is_array($links) ? $links : [];
        $html = [];
        foreach ((array) $blocks as $key => $value) {
            $value = (string) $value;
            $folder = null;
            if (in_array($row->type_flg, ['follow', 'auto'], true) && $row->push_follow_master_cd
                && app(PushImageStore::class)->exists('push_follow', (int) $row->push_follow_master_cd, $value)) {
                [$folder, $owner] = ['push_follow', (int) $row->push_follow_master_cd];
            } elseif (app(PushImageStore::class)->exists('push', (int) $row->cd, $value)) {
                [$folder, $owner] = ['push', (int) $row->cd];
            }
            if ($folder === null) {
                $html[] = e($value);

                continue;
            }
            $img = '<img width="100%" style="max-width:320px" src="'.e(PushImageStore::url($folder, $owner, $value)).'" >';
            $link = (string) ($links[$key] ?? '');
            $html[] = $link !== '' ? '<a href="'.e($link).'" target="_blank">'.$img.'</a>' : $img;
        }

        return nl2br(implode("\n\n", $html), false);
    }

    /** mailmag_master como push (push_shousai.php con data_type = mailmaga) */
    private function mail(int $cd): ?array
    {
        $row = DB::table('mailmag_master')->where('cd', $cd)->first();
        if (! $row) {
            return null;
        }
        $data = (array) $row;
        $data['title'] = $data['タイトル'];
        $data['shop_cd'] = $data['店id'];
        $data['post_type'] = '1';
        $data['body'] = nl2br(e((string) $data['pc内容']));
        $posted = (string) ($data['投稿日時'] ?? '');
        $data['touroku_date'] = $posted !== '' ? (int) PushSchedule::parseDate($posted) : 0;

        $image = 'system/category3/mail_mag/uploads/'.$data['店id'].'/'.$data['画像名大'];
        $data['image'] = (string) $data['画像名大'] !== '' && Storage::disk('public')->exists($image)
            ? ['url' => Storage::disk('public')->url($image), 'size' => @getimagesize(Storage::disk('public')->path($image)) ?: [0, 0]]
            : null;

        return $data;
    }
}
