<?php

namespace App\Services\Promotions;

use Illuminate\Support\Facades\DB;

/**
 * Clics de una お知らせ por perfil (category3/inc/graph_sql.php) sobre tb_mailmag_analyze:
 * sexo, década de edad y ocupación del cliente en el momento del clic.
 */
class ClickStats
{
    /** Colores fijos de ocupación del legacy */
    public const JOB_COLORS = [
        '会社員' => '#1829CE', '自営・自由業' => '#C60094', '公務員' => '#D60000', '学生' => '#FF9C00',
        '専業主婦' => '#219C00', 'フリーター' => '#840000', '無職' => '#5FA8FF', 'その他' => '#CCCCCC', '不明' => '#333333',
    ];

    /**
     * @return array{count: int, otoko_total: int, onna_total: int, fumei_total: int, ages: array<string, int>, jobs: array<string, int>}
     */
    public function forMail(int $mailmagCd): array
    {
        return $this->compute(fn () => DB::table('tb_mailmag_analyze')->where('mailmag_master_cd', $mailmagCd));
    }

    /**
     * クリックカウンタ de una familia (inc/mailmag_graph2.php): todos los clics con ese mailmag_type
     * ('' メルマガ, 'deco', 'coupon', 'anketo').
     */
    public function forType(string $type): array
    {
        return $this->compute(fn () => self::typeQuery($type));
    }

    /** tb_mailmag_analyze de una familia: '' cuenta también los NULL (COALESCE(mailmag_type,'') = '') */
    public static function typeQuery(string $type)
    {
        return DB::table('tb_mailmag_analyze')->whereRaw("COALESCE(mailmag_type, '') = ?", [$type]);
    }

    private function compute(\Closure $base): array
    {

        $sex = $base()->selectRaw("count(*) AS count,
            sum(case when customer_sex = '男性' then 1 else 0 end) AS otoko_total,
            sum(case when customer_sex = '女性' then 1 else 0 end) AS onna_total")->first();

        $ages = (array) $base()->selectRaw('
            sum(case when customer_age is null then 1 else 0 end) AS customer_undef,
            sum(case when customer_age between 0 and 19 then 1 else 0 end) AS customer_0,
            sum(case when customer_age between 20 and 29 then 1 else 0 end) AS customer_20,
            sum(case when customer_age between 30 and 39 then 1 else 0 end) AS customer_30,
            sum(case when customer_age between 40 and 49 then 1 else 0 end) AS customer_40,
            sum(case when customer_age between 50 and 59 then 1 else 0 end) AS customer_50,
            sum(case when customer_age between 60 and 69 then 1 else 0 end) AS customer_60,
            sum(case when customer_age > 69 then 1 else 0 end) AS customer_70')->first();

        $jobs = [];
        foreach ($base()->selectRaw('count(*) AS count, customer_job')->groupBy('customer_job')->get() as $row) {
            $name = (string) $row->customer_job === '' ? '不明' : (string) $row->customer_job;
            $jobs[$name] = ($jobs[$name] ?? 0) + (int) $row->count;
        }

        $count = (int) $sex->count;
        $otoko = (int) $sex->otoko_total;
        $onna = (int) $sex->onna_total;

        return [
            'count' => $count,
            'otoko_total' => $otoko,
            'onna_total' => $onna,
            'fumei_total' => $count - $otoko - $onna,
            'ages' => array_map('intval', $ages),
            'jobs' => $jobs,
        ];
    }

    /** Clics sobre el total enviado: "N件 (x.x%)" del listado/detalle */
    public static function rate(int $clicks, int $total, int $decimals = 2): string
    {
        return $total !== 0 ? number_format($clicks * 100 / $total, $decimals).'%' : '';
    }
}
