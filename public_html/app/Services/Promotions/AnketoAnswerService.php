<?php

namespace App\Services\Promotions;

use App\Services\Customers\CustomerRegistrationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Respuestas de la encuesta (tb_c3_anketo(2)_sub; una respuesta = filas con el mismo category_inner_group):
 * recuento por ítem (anketo_answer_analyze.php y csv_answer_download.php), respuestas de texto
 * (anketo_answer_shousai.php), 回答一覧 / 回答詳細 de お客様アンケート (anketo_answer_list.php, anketo_answer_detail.php)
 * y el alta de la アンケートテスト (anketo_test_register.php).
 */
class AnketoAnswerService
{
    /** Tipos que se cuentan por opción (el resto se lee en el 詳細 de texto) */
    public const COUNTED_TYPES = ['セレクト', 'ラジオボタン', 'チェックボックス'];

    public function __construct(private readonly CustomerRegistrationService $values) {}

    /**
     * Recuento de un ítem: select/radio por valor (`GROUP BY text_data`), checkbox separando `<>`.
     *
     * @return array{type: string, titles: list<string>, counts: list<int>, total: int}
     */
    public function itemStats(AnketoFamily $family, object|array $form): array
    {
        $form = (array) ($form instanceof \Illuminate\Database\Eloquent\Model ? $form->getAttributes() : $form);
        if (! in_array($form['form_type'], self::COUNTED_TYPES, true)) {
            return ['type' => 'string', 'titles' => [], 'counts' => [], 'total' => 0];
        }

        $titles = explode('<>', (string) $form['titles']);
        $values = explode('<>', (string) $form['data']);
        $answers = DB::table($family->subTable())->where('tb_c3_anketo_form_cd', $form['cd'])->where('text_data', '<>', '');

        $result = [];
        if ($form['form_type'] === 'チェックボックス') {
            foreach ($answers->pluck('text_data') as $text) {
                foreach (explode('<>', (string) $text) as $value) {
                    if ($value !== '') {
                        $result[$value] = ($result[$value] ?? 0) + 1;
                    }
                }
            }
        } else {
            $result = $answers->groupBy('text_data')->selectRaw('count(*) AS count, text_data')->pluck('count', 'text_data')->all();
        }

        $counts = [];
        foreach ($titles as $i => $title) {
            $counts[$i] = (int) ($result[$values[$i] ?? ''] ?? 0);
        }

        return ['type' => 'array', 'titles' => $titles, 'counts' => $counts, 'total' => array_sum($counts)];
    }

    /** anketo_answer_shousai.php: respuestas de texto de un ítem, las últimas primero */
    public function texts(AnketoFamily $family, int $formCd)
    {
        return DB::table($family->subTable())->where('tb_c3_anketo_form_cd', $formCd)->where('text_data', '<>', '')
            ->orderByDesc('touroku_date')->select('text_data');
    }

    /**
     * csv_answer_download.php: líneas UTF-8 del CSV (cabecera de la encuesta y recuento de cada ítem de opciones).
     *
     * @return list<string>
     */
    public function csvLines(AnketoFamily $family, object $cate, Collection $forms): array
    {
        $q = fn (string $s) => '"'.$s.'"';
        $lines = [
            $q('アンケート番号: '.$cate->cd),
            $q('アンケート名: '.$cate->name),
            $q('コメント: '.$cate->comment),
            $cate->start_date !== null && $cate->start_date !== '' ? $q('開始年月日: '.AnketoSurveyService::date($cate->start_date)) : '',
            $cate->end_date !== null && $cate->end_date !== '' ? $q('終了年月日: '.AnketoSurveyService::date($cate->end_date)) : '',
            '',
        ];

        foreach ($forms as $form) {
            $stats = $this->itemStats($family, $form);
            if ($stats['type'] !== 'array') {
                continue;
            }
            $lines[] = '';
            $lines[] = $q($form->name);
            if ($stats['titles'] === []) {
                $lines[] = $q("\t回答はありません。");
            }
            foreach ($stats['titles'] as $i => $title) {
                $text = $title.': '.$stats['counts'][$i].'人';
                if ($form->form_type !== 'チェックボックス') {
                    $percent = $stats['total'] > 0 ? $stats['counts'][$i] * 100 / $stats['total'] : 0;
                    $text .= '    ('.number_format($percent, 1).'%)';
                }
                $lines[] = $q("\t".$text);
            }
        }

        return $lines;
    }

    /**
     * anketo_answer_list.php (お客様アンケート): respuestas agrupadas por category_inner_group con los datos del
     * cliente, filtradas por fecha de registro y, desde 回答解析 «内訳», por la respuesta a un ítem.
     *
     * @param  array{from?: ?int, to?: ?int, fcd?: ?int, answer?: ?string}  $filter
     * @return array{answers: list<array>, customers: array<int, array>}
     */
    public function answers(AnketoFamily $family, int $cateCd, array $filter): array
    {
        $sub = $family->subTable();
        $query = DB::table($sub.' as tb1')
            ->join('tb_customer as tb2', 'tb1.tb_customer_cd', '=', 'tb2.cd')
            ->where('tb1.tb_c3_anketo_cate_cd', $cateCd)
            ->where('tb1.disp_flg', '1')
            ->when($filter['from'] ?? null, fn ($q, $from) => $q->whereRaw('CAST(tb1.touroku_date AS bigint) >= ?', [$from]))
            ->when($filter['to'] ?? null, fn ($q, $to) => $q->whereRaw('CAST(tb1.touroku_date AS bigint) <= ?', [$to]))
            ->when(($filter['fcd'] ?? null) !== null, fn ($q) => $q->whereIn('tb1.category_inner_group', DB::table($sub)
                ->select('category_inner_group')
                ->where('tb_c3_anketo_cate_cd', $cateCd)
                ->where('tb_c3_anketo_form_cd', (int) $filter['fcd'])
                ->where('text_data', 'like', '%'.addcslashes((string) ($filter['answer'] ?? ''), '%_\\').'%')))
            ->orderByDesc('tb1.rank')
            ->orderByDesc('tb1.cd')
            ->get(['tb1.cd', 'tb1.tb_customer_cd', 'tb1.tb_c3_anketo_form_cd', 'tb1.text_data', 'tb1.category_inner_group', 'tb1.touroku_date',
                'tb2.id', 'tb2.name', 'tb2.email1', 'tb2.kojin_birth', 'tb2.kojin_sex', 'tb2.disp_flg']);

        $today = (int) Carbon::now(LegacyTime::ZONE)->format('Ymd');
        $answers = [];
        $customers = [];
        foreach ($query as $row) {
            $group = (string) $row->category_inner_group;
            $answers[$group] ??= ['cd' => $row->cd, 'tb_customer_cd' => (int) $row->tb_customer_cd, 'category_inner_group' => $group,
                'touroku_date' => $row->touroku_date, 'data' => []];
            $answers[$group]['data'][(int) $row->tb_c3_anketo_form_cd] = (string) $row->text_data;

            $birth = str_replace('/', '', (string) $row->kojin_birth);
            $customers[(int) $row->tb_customer_cd] ??= [
                'id' => $row->id, 'name' => $row->name, 'email1' => (string) $row->email1, 'kojin_sex' => $row->kojin_sex,
                'disp_flg' => (string) $row->disp_flg,
                'age' => ctype_digit($birth) && strlen($birth) === 8 ? (int) floor(($today - (int) $birth) / 10000) : '',
                'groups' => [],
            ];
            $customers[(int) $row->tb_customer_cd]['groups'][$group] = true;
        }

        // 来店回数 y アンケート回数 (respuestas del cliente en esta lista)
        $visits = $customers === [] ? [] : DB::table('tb_raiten_rireki')->whereIn('user_cd', array_keys($customers))
            ->whereRaw("COALESCE(disp_flg, '') <> '9'")->groupBy('user_cd')->selectRaw('user_cd, count(shop_cd) AS raiten_cnt')
            ->pluck('raiten_cnt', 'user_cd')->all();
        foreach ($customers as $cd => &$customer) {
            $customer['answer_cnt'] = count($customer['groups']);
            $customer['raiten_cnt'] = (int) ($visits[$cd] ?? 0);
        }
        unset($customer);

        return ['answers' => array_values($answers), 'customers' => $customers];
    }

    /** anketo_answer_detail.php: respuesta de un cliente (grupo) por ítem */
    public function answer(AnketoFamily $family, int $cateCd, int $customerCd, string $group): array
    {
        return DB::table($family->subTable())->where('tb_customer_cd', $customerCd)->where('tb_c3_anketo_cate_cd', $cateCd)
            ->where('category_inner_group', $group)->where('disp_flg', '1')
            ->pluck('text_data', 'tb_c3_anketo_form_cd')->map(fn ($v) => (string) $v)->all();
    }

    /**
     * anketo_test_register.php: una fila por ítem con el siguiente category_inner_group (MAX + 1, con bloqueo),
     * los valores de createform_sql (servicio de S2) y form_title = «encuesta<>ítem».
     */
    public function storeTest(AnketoFamily $family, object $cate, Collection $forms, int $customerCd, array $data): string
    {
        return DB::transaction(function () use ($family, $cate, $forms, $customerCd, $data) {
            $last = DB::table($family->subTable())->whereRaw("category_inner_group ~ '^[0-9]+$'")
                ->orderByRaw('CAST(category_inner_group AS bigint) DESC')->lockForUpdate()->value('category_inner_group');
            $group = (string) ((int) $last + 1);

            foreach ($forms as $form) {
                $family->subs()->create($this->values->customFieldValues($form->getAttributes(), $data) + [
                    'tb_customer_cd' => $customerCd,
                    'tb_c3_anketo_cate_cd' => $cate->cd,
                    'tb_c3_anketo_form_cd' => $form->cd,
                    'form_title' => $cate->name.'<>'.$form->name,
                    'form_type' => '1',
                    'disp_flg' => '1',
                    'category_inner_group' => $group,
                ]);
            }

            return $group;
        });
    }
}
