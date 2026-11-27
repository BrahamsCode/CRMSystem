<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Models\AdminMaster;
use App\Services\Promotions\ClickStats;
use App\Services\Promotions\StaffContext;
use App\Support\LegacyCsv;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * クリックカウンタ (inc/mailmag_graph2.php + graph_sql.php): accesos a Mi página desde los enlaces de los envíos,
 * por sexo, edad y ocupación, de toda una familia (`mailmag_type`):
 * mail_mag/mailmag_analyze.php (''), decomail/mailmag_analyze.php (deco), mycoupon/mycoupon_analyze.php (coupon)
 * y anketo/anketo_analyze.php (anketo). CSV de los clics (inc/csv_download.php) solo para master.
 */
class ClickCounterController extends Controller
{
    /** mailmag_type => [bloque del menú, título del CSV] */
    public const TYPES = [
        '' => ['.ac1', 'メルマガからアクセス数'],
        'deco' => ['.ac9', 'デコメールからアクセス数'],
        'coupon' => ['.ac7', 'クーポンメルマガからアクセス数'],
        'anketo' => ['.ac8', 'アンケートメルマガからアクセス数'],
    ];

    public function __construct(private readonly StaffContext $staff) {}

    public function show(ClickStats $stats, string $requestType = ''): View
    {
        $tool = (int) AdminMaster::current()->application_use_flg === 1 ? 'お知らせ' : 'メール';

        return view('admin.promocion.inc.mailmag_graph2', [
            'pageTitle' => match ($requestType) {
                'deco' => '配信メールからのマイページアクセス集計',
                'coupon' => 'クーポン付配信メールからのマイページアクセス集計',
                'anketo' => 'アンケートメルマガアクセス解析',
                default => '配信'.$tool.'からのマイページアクセス集計',
            },
            'activeAc' => self::TYPES[$requestType][0],
            'requestType' => $requestType,
            'stats' => $stats->forType($requestType),
            'isMaster' => $this->staff->isMaster(),
        ]);
    }

    /**
     * csv_download.php: «"<título>"», cabecera y una fila por clic (más reciente primero), celdas «"\t…"» en SJIS-win.
     * El legacy no tenía el caso deco (la consulta fallaba y salía solo la cabecera); aquí deco exporta sus clics.
     */
    public function csv(Request $request): StreamedResponse
    {
        abort_unless($this->staff->isMaster(), 403);
        $type = (string) $request->input('request_type', '');
        abort_unless(array_key_exists($type, self::TYPES), 404);

        $cell = fn ($v) => "\"\t".str_replace('"', '', (string) $v).'"';
        $rows = function () use ($type, $cell) {
            yield ['"'.self::TYPES[$type][1].'"'];
            yield array_map($cell, ['メルマガCD', 'ユーザID', 'アクセス日時', '年齢', '性別', '職業']);
            $query = ClickStats::typeQuery($type)->from('tb_mailmag_analyze as tb1')
                ->leftJoin('tb_customer as tb2', 'tb1.tb_customer_cd', '=', 'tb2.cd')
                ->orderByDesc('tb1.touroku_date')->orderByDesc('tb1.cd')
                ->select(['tb1.mailmag_master_cd', 'tb2.id', 'tb1.touroku_date', 'tb1.customer_age', 'tb1.customer_sex', 'tb1.customer_job']);
            foreach ($query->cursor() as $r) {
                yield array_map($cell, [$r->mailmag_master_cd, $r->id, date('Y/m/d H:i', (int) $r->touroku_date), $r->customer_age, $r->customer_sex, $r->customer_job]);
            }
        };

        return LegacyCsv::download('user.csv', $rows, null, ['quote' => 'none']);
    }
}
