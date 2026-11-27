<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Models\AdminMaster;
use App\Services\Promotions\MailAudience;
use App\Services\Promotions\StaffContext;
use App\Support\LegacyText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Condiciones de los envíos recurrentes, antes de la redacción común (inc/mail_frm.php):
 * - 自動 (auto/auto_new.php → auto_new_confirm.php → auto_mail_customer_select.php): meses y días, o días de la semana, y hora.
 * - フォロー (follow/follow_new.php → follow_new_confirm.php → follow_mail_new.php): N días desde el último 来店, el alta,
 *   antes del cumpleaños, antes del 来店周期, o antes/después de un campo 年月日/月日 de 顧客追加情報; y hora.
 *   El confirm arma el `振分配信` (SQL sobre tb_customer con fechas relativas a «hoy», que se evalúan al enviar)
 *   y lo deja como búsqueda guardada (MailAudience::storeRaw).
 * `kind = autoPush / followPush` (通知) solo cambia textos y destino (push, M5).
 */
class RuleController extends Controller
{
    public const DOW = ['日', '月', '火', '水', '木', '金', '土'];

    public function __construct(
        private readonly MailAudience $audience,
        private readonly StaffContext $staff,
    ) {}

    /** URL legacy (follow_new.php, auto_new.php, p. ej. ?kind=followPush del menú viejo): a la pantalla migrada con su query */
    public function legacyRedirect(Request $request, string $target): RedirectResponse
    {
        return redirect()->route($target, $request->query());
    }

    // ------------------------------------------------------------------
    // 自動
    // ------------------------------------------------------------------

    public function auto(Request $request): View
    {
        $data = $request->has('modoru') ? $this->clean($request, fn ($v) => LegacyText::form($v)) : $this->defaultTime();
        foreach ([1, 2, 3] as $x) {
            $value = $data['step2_'.$x] ?? '';
            $data['step2_'.$x] = is_array($value) ? $value : ($value !== '' ? explode(',', (string) $value) : []);
        }

        return view('admin.promocion.auto.auto_new', $this->common($request, 'auto') + ['data' => $data]);
    }

    public function autoConfirm(Request $request): View
    {
        // get_form2(): la pantalla y los ocultos usan el texto ya escapado; aquí se escapa al imprimir
        $data = $this->clean($request, fn ($v) => LegacyText::trim((string) $v));
        if (($data['step1'] ?? '') === '1') {
            unset($data['step2_3']);
        } elseif (($data['step1'] ?? '') === '2') {
            unset($data['step2_1'], $data['step2_2']);
        }
        // Los arrays viajan como «1,2,3» (implode del legacy)
        $hidden = array_map(fn ($v) => is_array($v) ? implode(',', $v) : (string) $v, $data);

        return view('admin.promocion.auto.auto_new_confirm', $this->common($request, 'auto') + [
            'data' => $data,
            'hidden' => $hidden,
            'nextAction' => route('admin.promocion.auto.select'),
        ]);
    }

    // ------------------------------------------------------------------
    // フォロー
    // ------------------------------------------------------------------

    public function follow(Request $request): View
    {
        $data = $request->has('modoru') ? $this->clean($request, fn ($v) => LegacyText::form($v)) : $this->defaultTime();
        $visits = $this->childActive('来店履歴管理');
        if (! $visits && ($data['step1'] ?? '') === '') {
            $data['step1'] = '2';
        }

        return view('admin.promocion.follow.follow_new', $this->common($request, 'follow') + [
            'data' => $data,
            'visitsEnabled' => $visits,
            'reservationsEnabled' => $this->reservationsEnabled(),
            'customFields' => $this->customFields(),
            'shops' => DB::table('tb_shop')->where('disp_flg', '<>', '9')
                ->when(! $this->staff->isMaster(), fn ($q) => $q->where('cd', $this->staff->shopCd()))->orderBy('cd')->get(['cd', 'kaishamei']),
            'staffShopName' => (string) DB::table('tb_shop')->where('cd', $this->staff->shopCd())->value('kaishamei'),
        ]);
    }

    public function followConfirm(Request $request): View|RedirectResponse
    {
        $data = $this->clean($request, fn ($v) => LegacyText::form($v));
        $tool = $this->toolName($request, 'follow');
        $days = (string) ($data['step2_1'] ?? '');
        // El legacy solo lo comprobaba en JS y lo metía en el SQL: aquí, solo dígitos
        if (! preg_match('/^[0-9]+$/', $days)) {
            return redirect()->route('admin.promocion.follow.rule');
        }
        $n = (int) $days;

        $shopCd = (string) ($data['shop_cd'] ?? '');
        if ($shopCd === '-1' && $this->staff->isMaster()) {
            $whereShop = '';
            $shopName = '全店舗';
        } else {
            $shopCd = $this->staff->isMaster() ? (string) (int) $shopCd : (string) $this->staff->shopCd();
            $whereShop = 'AND tb_shop_cd = '.(int) $shopCd;
            $shopName = (string) DB::table('tb_shop')->where('disp_flg', '<>', '9')->where('cd', (int) $shopCd)->value('kaishamei');
        }
        $data['shop_cd'] = $shopCd;

        $lb3 = '';
        $step1 = (string) ($data['step1'] ?? '');
        switch ($step1) {
            case '1':
                $purpose = '来店後のフォロー'.$tool.'を配信';
                [$lb1, $lb2] = ['最終来店日から', '日後'];
                $sub = "AND cd IN (select user_cd from tb_raiten_rireki where coalesce(disp_flg, '') = '' group by user_cd having max(raiten_time::integer) >= extract(epoch from timestamp 'today'-interval '{$n} days') and max(raiten_time::integer) < extract(epoch from timestamp 'tomorrow'-interval '{$n} days'))";
                if ($this->reservationsEnabled()) {
                    if (($data['step2_yoyakuflg'] ?? '') === '1') {
                        $sub .= " AND cd NOT IN (select distinct(tb_customer_cd) from tb_yoyaku tb1 inner join tb_yoyaku_uketsuke_day tb2 on tb1.tb_yoyaku_uketsuke_day_cd = tb2.cd where coalesce(tb_customer_cd, 0)>0 and tb2.moushikomi_date::date >= timestamp 'today')";
                        $lb3 = '（※既に予約があるお客様に'.$tool.'を配信しない）';
                    } else {
                        $lb3 = '（※既に予約があるお客様に'.$tool.'を配信する）';
                    }
                }
                break;
            case '2':
                $purpose = '会員登録日から○日後のお客様に'.$tool.'配信';
                [$lb1, $lb2] = ['会員登録日から', '日後'];
                $sub = "AND touroku_date::integer >= extract(epoch from timestamp 'today'-interval '{$n} days') AND touroku_date::integer < extract(epoch from timestamp 'tomorrow'-interval '{$n} days')";
                break;
            case '3':
                $purpose = '誕生日から○日前のお客様に'.$tool.'配信';
                [$lb1, $lb2] = ['誕生日から', '日前'];
                $sub = "AND SPLIT_PART(kojin_birth,'/',2)=TO_CHAR(timestamp 'today'+ interval '{$n}days','MM') AND SPLIT_PART(kojin_birth,'/',3)=TO_CHAR(timestamp 'today'+ interval '{$n}days','DD')";
                break;
            case '4':
                $purpose = '最終来店日から来店周期日数後';
                [$lb1, $lb2] = ['最終来店日から来店周期日数後の', '日前'];
                [$lo, $hi] = $this->cycleLimits();
                $sub = "AND NOW()::date - last_raiten_date::date = shuki_heikin - {$n} AND COALESCE(last_raiten_date,'') <> '' AND shuki_heikin >= {$lo} AND shuki_heikin <= {$hi}";
                break;
            default:
                // custom_<cd>: campo 年月日 / 月日 de 顧客追加情報
                $field = str_starts_with($step1, 'custom_') ? ($this->customFields()->firstWhere('cd', (int) substr($step1, 7))) : null;
                if (! $field) {
                    return redirect()->route('admin.promocion.follow.rule');
                }
                $zengo = ($data['step2_zengo'] ?? '') === '後' ? '後' : '前';
                $data['step2_zengo'] = $zengo;
                $purpose = $field->name.'から○日'.$zengo.'のお客様に'.$tool.'配信';
                [$lb1, $lb2] = [$field->name.'から', '日'.$zengo];
                $sign = $zengo === '前' ? '+' : '-';
                $part = fn (int $i, string $fmt) => "AND SPLIT_PART(text_data,'/',{$i})=TO_CHAR(timestamp 'today'{$sign} interval '{$n}days','{$fmt}') ";
                $sub = 'AND cd IN (SELECT tb_customer_cd FROM tb_customer_sub WHERE tb_search_form_cd = '.(int) $field->cd.' '
                    .($field->form_type === '年月日' ? $part(1, 'YYYY').$part(2, 'MM').$part(3, 'DD') : $part(1, 'MM').$part(2, 'DD')).')';
        }

        $display = $lb1.$n.$lb2.$lb3;
        // 2018.07.12 del legacy: no se mira mailmaga_flg
        $uniqid = $this->audience->storeRaw("WHERE disp_flg = '1' AND touroku_kubun = '2' {$whereShop} {$sub}", null, $display);
        $data['step2_1'] = (string) $n;
        if (($request->input('kind') ?? '') === 'followPush') {
            $data['type_flg'] = 'follow';
        }

        return view('admin.promocion.follow.follow_new_confirm', $this->common($request, 'follow') + [
            'data' => $data,
            'shopName' => $shopName,
            'purpose' => $purpose,
            'lb1' => $lb1,
            'lb2' => $lb2,
            'searchUniqid' => $uniqid,
            'reservationsEnabled' => $this->reservationsEnabled(),
            // フォロー通知 (kind=followPush): sigue en プッシュ通知新規作成 de S4 (M5)
            'nextAction' => route(($request->input('kind') ?? '') === 'followPush' ? 'admin.promocion.push.create' : 'admin.promocion.follow.create'),
        ]);
    }

    // ------------------------------------------------------------------
    // リマインダ (reminder/reminder_new.php → reminder_new_confirm.php → reminder_mail_new.php)
    // ------------------------------------------------------------------

    public function reminder(Request $request): View
    {
        $data = $request->has('modoru') ? $this->clean($request, fn ($v) => LegacyText::form($v)) : $this->defaultTime();

        return view('admin.promocion.reminder.reminder_new', [
            'data' => $data,
            'isMaster' => $this->staff->isMaster(),
            'staffShopName' => (string) DB::table('tb_shop')->where('cd', $this->staff->shopCd())->value('kaishamei'),
            'staffShopCd' => $this->staff->shopCd(),
            'shops' => DB::table('tb_shop')->where('disp_flg', '<>', '9')->orderBy('cd')->get(['cd', 'kaishamei']),
        ]);
    }

    /**
     * reminder_new_confirm.php: el `振分配信` es la SELECT de reservas del día «hoy + N» (tb_yoyaku de la tienda,
     * sin los que pararon los recordatorios y, opcionalmente, solo las de 携帯予約), que MailSender ejecuta al enviar.
     * La tienda del personal no master es siempre la suya; los días, solo dígitos (el legacy lo metía en el SQL).
     */
    public function reminderConfirm(Request $request): View|RedirectResponse
    {
        $data = $this->clean($request, fn ($v) => LegacyText::form($v));
        $days = (string) ($data['step2_1'] ?? '');
        $shopCd = $this->staff->isMaster() ? (string) ($data['shop_cd'] ?? '') : (string) $this->staff->shopCd();
        if (! preg_match('/^[0-9]+$/', $days) || $shopCd === '') {
            return redirect()->route('admin.promocion.reminder.rule');
        }
        $n = (int) $days;
        $data['step2_1'] = (string) $n;
        $data['shop_cd'] = $shopCd;
        $data['mail_type'] = ($data['mail_type'] ?? '') === 'remdeco' ? 'remdeco' : 'yoyaku';

        if ($shopCd === '-1') {
            $whereShop = '';
            $shopName = '全店舗';
        } else {
            $whereShop = 'AND tb2.shop_cd = '.(int) $shopCd;
            $shopName = (string) DB::table('tb_shop')->where('disp_flg', '<>', '9')->where('cd', (int) $shopCd)->value('kaishamei');
        }
        $mobileOnly = ($data['step2_yoyakuflg'] ?? '') === '1' ? "AND COALESCE(s1.entry_type, '') IN ('1','2','3','4','5','6','100','101') " : '';
        $interval = $n > 0 ? " + interval '{$n}days'" : '';

        $select = <<<SQL
SELECT
 s1.tb_customer_cd AS cd,
 s1.start_time,
 CASE WHEN tb_customer_cd > 0 THEN s2.name ELSE s1.name END AS name,
 CASE WHEN tb_customer_cd > 0 THEN s2.tel ELSE s1.tel END AS tel,
 CASE WHEN tb_customer_cd > 0 THEN s2.email1 ELSE s1.mail END AS email1,
 CASE WHEN tb_customer_cd > 0 THEN s2.login_id ELSE '' END AS login_id,
 CASE WHEN tb_customer_cd > 0 THEN s2.mailmaga_email_type ELSE '' END AS mailmaga_email_type,
 CASE WHEN tb_customer_cd > 0 THEN s2.login_pass ELSE '' END AS login_pass,
 s3.moushikomi_date
FROM
 tb_yoyaku AS s1
LEFT JOIN
 tb_customer AS s2
 ON s1.tb_customer_cd = s2.cd
LEFT JOIN
 tb_yoyaku_uketsuke_day AS s3
 ON s1.tb_yoyaku_uketsuke_day_cd = s3.cd
WHERE
     s1.disp_flg='1'
 AND s1.tb_yoyaku_uketsuke_day_cd IN
 (
 SELECT
  tb1.cd
 FROM
  tb_yoyaku_uketsuke_day as tb1
 INNER JOIN
  tb_staff as tb2
  ON tb1.tb_staff_cd = tb2.cd
 WHERE
  tb1.moushikomi_date = TO_CHAR(timestamp 'today'{$interval}, 'YYYY/MM/DD')
  {$whereShop}
 )
 AND coalesce(s2.stop_yoyaku_reminder_mail_flg, '')=''
 {$mobileOnly};
SQL;

        return view('admin.promocion.reminder.reminder_new_confirm', [
            'data' => $data,
            'shopName' => $shopName,
            'searchUniqid' => $this->audience->storeRaw($select, null, '予約日の'.$n.'日前になったら、お客様にメールを配信'),
        ]);
    }

    // ------------------------------------------------------------------

    private function common(Request $request, string $family): array
    {
        $push = ($request->input('kind') ?? '') === $family.'Push';

        return [
            'toolName' => $this->toolName($request, $family),
            'kind' => (string) $request->input('kind', ''),
            'isPush' => $push,
            'activeAc' => $push ? '.ac12' : ($family === 'auto' ? '.ac11' : '.ac10'),
            'isMaster' => $this->staff->isMaster(),
        ];
    }

    /** getMailmagazineName(); 通知 para los push */
    private function toolName(Request $request, string $family): string
    {
        if (($request->input('kind') ?? '') === $family.'Push') {
            return '通知';
        }

        return (int) AdminMaster::current()->application_use_flg === 1 ? 'お知らせ' : 'メール';
    }

    /** POST sin _token ni botones, cada valor (o elemento de array) limpiado */
    private function clean(Request $request, callable $fn): array
    {
        $out = [];
        foreach ($request->except(['_token', 'modoru', 'enter']) as $key => $value) {
            $out[$key] = is_array($value) ? array_map(fn ($v) => is_array($v) ? '' : $fn($v), $value) : $fn($value);
        }

        return $out;
    }

    /** Hora por defecto: 始業時間 de la empresa (tb_office.start_work_time «HH<>MM») o 12:00 */
    private function defaultTime(): array
    {
        [$h, $m] = array_pad(explode('<>', (string) DB::table('tb_office')->where('cd', 1)->value('start_work_time')), 2, '');

        return ['step2_jikan' => $h !== '' ? $h : '12', 'step2_fun' => $m !== '' ? $m : '00'];
    }

    private function childActive(string $name): bool
    {
        return DB::table('tb_base_system_child')->where('system_name', $name)->where('system_flg', '1')->exists();
    }

    /** 予約管理 (tb_base_system cd 9) activo */
    private function reservationsEnabled(): bool
    {
        return DB::table('tb_base_system')->where('cd', 9)->where('system_name', '予約管理')->where('system_flg', '1')->exists();
    }

    /** Campos 年月日 / 月日 visibles de categorías de 顧客追加情報 sin tipo de registro */
    private function customFields()
    {
        return DB::table('tb_search_form as tb1')->join('tb_search_cate as tb2', 'tb1.tb_search_cate_cd', '=', 'tb2.cd')
            ->whereIn('tb1.form_type', ['年月日', '月日'])->where('tb1.disp_flg', '1')->where('tb1.hyouji_flg', '<>', '')
            ->where('tb2.record_type', '')->where('tb2.disp_flg', '1')->where('tb2.hyouji_flg', '1')
            ->orderBy('tb2.cd')->orderBy('tb1.cd')->get(['tb1.cd', 'tb1.name', 'tb1.form_type', 'tb2.name as cate_name']);
    }

    /** admin_master.shuki_limit «min<>max» del 来店周期: 20–100 por defecto; un 0 toma 1 o 100 */
    private function cycleLimits(): array
    {
        $raw = (string) AdminMaster::current()->shuki_limit;
        [$lo, $hi] = $raw === '' ? [20, 100] : array_map('intval', array_pad(explode('<>', $raw), 2, '0'));

        return match (true) {
            $lo === 0 && $hi === 0 => [20, 100],
            $lo === 0 => [1, $hi],
            $hi === 0 => [$lo, 100],
            default => [$lo, $hi],
        };
    }
}
