<?php

namespace App\Services\Integraciones\Appview;

use App\Services\Customers\CustomerRegistrationService;
use App\Services\Integraciones\PosGateway;
use App\Services\Promotions\StampService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * appview/class/Customer.php, paquete «crm» (los paquetes swa/rwa son BD del POS, fuera de alcance): formulario de
 * alta/edición del socio armado con tb_koumoku (campos de tb_customer) y tb_search_cate/tb_search_form (campos
 * personalizados, ext_colum[<form_cd>]), datos para rellenarlo y alta o modificación.
 *
 * Diferencias con el legacy (anotadas para Japón): los valores que vuelven al formulario salen con h() (el legacy
 * los imprimía tal cual), y todo el SQL va con parámetros. Los años de las fechas salen de now() (Carbon).
 */
class AppCustomer
{
    public function __construct(
        private readonly CustomerRegistrationService $registration,
        private readonly StampService $stamps,
        private readonly PosGateway $pos,
    ) {}

    /** getCustomerRegistSetting(): campos de tb_customer visibles en el alta (label_name, input_name, required) */
    public function settings(): array
    {
        return DB::table('tb_koumoku')->where('system_cate', '1')->where('tb_name', 'tb_customer')->where('koumoku_disp', '1')
            ->orderBy('rank')->orderBy('cd')
            ->get(['tb_koumoku_name as label_name', 'tb_koumoku as input_name', 'koumoku_check'])
            ->map(fn ($r) => ['label_name' => (string) $r->label_name, 'input_name' => (string) $r->input_name, 'required' => $r->koumoku_check === '1' ? 1 : 0])
            ->all();
    }

    /** getCustomerSubRegistSettingCRM(): campos personalizados visibles (categorías que no son de 来店記録) */
    public function subSettings(): array
    {
        return DB::table('tb_search_cate as tb1')->join('tb_search_form as tb2', 'tb2.tb_search_cate_cd', '=', 'tb1.cd')
            ->where('tb2.disp_flg', '1')->where('tb2.hyouji_flg', '1')->where('tb1.disp_flg', '1')->where('tb1.hyouji_flg', '1')
            ->whereRaw("coalesce(tb1.record_type, '') != '1'")
            ->orderBy('tb1.rank')->orderBy('tb1.cd')->orderBy('tb2.rank')->orderBy('tb2.cd')
            ->get(['tb1.cd as category_cd', 'tb1.name as category_name', 'tb2.cd as form_cd', 'tb2.name as label_name', 'tb2.titles',
                'tb2.data', 'tb2.comment', 'tb2.unit', 'tb2.hissu', 'tb2.form_type', 'tb2.style_class'])
            ->map(fn ($r) => array_map(fn ($v) => $v === null ? null : (string) $v, (array) $r))
            ->all();
    }

    /** getSettingNameSplit(): nombre en dos campos (姓/名) si contents_master tb_c1_customer/name_input_mode_flg = '1' */
    public function nameSplit(): bool
    {
        return DB::table('contents_master')->where('category', 'tb_c1_customer')->where('field', 'name_input_mode_flg')
            ->where('disp_flg', '1')->value('text_data') === '1';
    }

    // ------------------------------------------------------------------ formulario

    /** getRegistFormCRM(): HTML de los campos con los valores de $request */
    public function form(array $request): string
    {
        $split = $this->nameSplit();
        $html = '';
        foreach ($this->settings() as $setting) {
            $name = $setting['input_name'];
            $label = $setting['label_name'];
            $required = $setting['required'];
            $req = $request[$name] ?? null;

            $part = match ($name) {
                'name' => $this->inputName($name, $label, $required, $split ? ($request['name_1'] ?? '').' '.($request['name_2'] ?? '') : $req, $split),
                'name_kana' => $this->inputName($name, $label, $required, $split
                    ? mb_convert_kana((string) ($request['name_kana_1'] ?? ''), 'KVC').' '.mb_convert_kana((string) ($request['name_kana_2'] ?? ''), 'KVC')
                    : $req, $split, 'カナ'),
                'zip' => $this->inputZip($name, $label, $required, $req),
                'pref' => $this->select($name, $required, $this->options(['北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県', '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県', '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県', '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県', '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県', '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県', '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'], $req)),
                'type' => $this->select($name, $required, $this->options(['個人', '法人'], $req)),
                'kojin_sex' => $this->select($name, $required, $this->options(['男性', '女性'], $req)),
                'kojin_bloodtype' => $this->select($name, $required, $this->options(['A', 'B', 'O', 'AB'], $req)),
                'mailmaga_flg' => $this->select($name, $required, $this->mailmagaOptions($req)),
                'houjin_gyoushu', 'kinmusaki_gyoushu' => $this->select($name, $required, $this->options(self::INDUSTRIES, $req)),
                'job' => $this->select($name, $required, $this->options(DB::table('tb_job')->orderBy('rank')->pluck('name')->map(fn ($v) => (string) $v)->all(), $req)),
                'tb_raiten_douki_master_cd' => $this->select($name, $required, $this->keyedOptions(
                    DB::table('tb_raiten_douki_master')->where('disp_flg', '1')->orderBy('rank')->pluck('name', 'cd')->all(), $req)),
                'tb_staff_cd' => $this->select($name, $required, $this->keyedOptions(
                    DB::table('tb_staff')->where('disp_flg', '1')->where('reserve_flg', '1')->orderBy('common_flg')->orderBy('rank')->orderBy('cd')->pluck('name', 'cd')->all(), $req)),
                'kojin_birth', 'houjin_setsuritsu', 'daihyousha_birth' => $this->date($name, $required, $req),
                default => $this->input($name, $label, $required, $req),
            };

            $html .= '<div class="form-group .col-sm-6">';
            $html .= '<label for="'.$name.'" class="control-label" style="display: block;">'.$label.'</label>';
            $html .= $part;
            if ($name !== 'name' || ! $split) {
                $html .= '<div class="help-block with-errors"></div>';
            }
            $html .= '</div>';
        }

        foreach ($this->subSettings() as $sub) {
            $name = 'ext_colum['.$sub['form_cd'].']';
            $label = $sub['label_name'];
            $req = $request['ext_colum'][$sub['form_cd']] ?? null;
            $unit = (string) $sub['unit'];
            $titles = explode('<>', (string) $sub['titles']);

            $part = match ($sub['form_type']) {
                'ラジオボタン', 'セレクト' => $this->select($name, false, $this->options($titles, $req), $unit),
                'チェックボックス' => $this->checkbox($name, $titles, $req, $unit),
                '年' => $this->select($name, false, $this->options(range(now()->year + 1, 1900), $req), $unit),
                '年月' => $this->yearMonth($name, false, $req),
                '年月日', '起算日' => $this->date($name, false, $req),
                '月日' => $this->monthDate($name, $req),
                'テキストエリア' => $this->textarea($name, $label, false, $req, $unit),
                default => $this->input($name, $label, false, $req, $unit),
            };
            $part .= $sub['comment'] != '' ? '<div style="font-size:80%; color:#777777;">'.nl2br((string) $sub['comment']).'</div>' : '';

            $html .= '<div class="form-group .col-sm-6">';
            $html .= '<label for="'.$name.'" class="control-label" style="display: block;">'.$label.'</label>';
            $html .= $part;
            $html .= '<div class="help-block with-errors"></div>';
            $html .= '</div>';
        }

        return $html;
    }

    private const INDUSTRIES = ['農業', '林業', '漁業', '鉱業', '採石業', '砂利採取業', '建設業', '製造業', '電気・ガス・熱供給・水道業', '情報通信業', '運輸業', '郵便業', '卸売業', '小売業', '金融業', '保険業', '不動産業', '物品賃貸業', '学術研究', '専門・技術サービス業', '宿泊業', '飲食サービス業', '生活関連サービス業', '娯楽業', '教育', '学習支援業', '医療', '福祉', '他の営利事業', 'その他（政治・経済・文化・宗教団体など）'];

    private static function h(mixed $value): string
    {
        return is_array($value) ? '' : htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8', false);
    }

    private static function req(mixed $required): string
    {
        return $required ? ' required' : '';
    }

    private function input(string $name, string $label, mixed $required, mixed $val, string $unit = ''): string
    {
        $width = $unit != '' ? 'display:inline-block; width:70%;' : '';
        $html = '<input type="text" name="'.$name.'" class="form-control" id="'.$name.'" value="'.self::h($val).'" placeholder="'.$label.'を入力してください" data-error="'.$label.'を入力してください"'.self::req($required).' style="'.$width.'">'.$unit;
        if ($name === 'email1') {
            $html .= '<small style="color:#aaa">※ログイン時や、パスワードをリセットする場合に、このメールを使用します。</small>';
        }

        return $html;
    }

    private function inputZip(string $name, string $label, mixed $required, mixed $val): string
    {
        $unit = '<input type="button" class="btn btn-default" value="郵便番号検索" id="ajax3zip_'.$name.'" style="margin-top: -4px;">';

        return '<input type="text" name="'.$name.'" class="form-control" id="'.$name.'" value="'.self::h($val).'" placeholder="'.$label.'を入力してください" data-error="'.$label.'を入力してください"'.self::req($required).' style="display:inline-block; width:50%;">'.$unit
            .'<br><small style="color:#aaa">※ハイフン無しで入力ください。</small>';
    }

    private function textarea(string $name, string $label, mixed $required, mixed $val, string $unit = ''): string
    {
        $width = $unit != '' ? 'display:inline-block; width:80%;' : '';

        return '<textarea name="'.$name.'" class="form-control" id="'.$name.'" placeholder="'.$label.'を入力してください" data-error="'.$label.'を入力してください"'.self::req($required).' style="'.$width.'">'.self::h($val).'</textarea>'.$unit;
    }

    /** Partes de una fecha "Y/m/d" o "Y-m-d" (como el legacy: lo que falte, null) */
    private static function dateParts(mixed $req): array
    {
        $value = is_string($req) ? $req : '';
        $parts = str_contains($value, '/') ? explode('/', $value) : explode('-', $value);

        return [$parts[0] ?? null, $parts[1] ?? null, $parts[2] ?? null];
    }

    /** Nombre del select de una parte: "x_y" o "ext_colum[10_y]" */
    private static function partName(string $name, string $suffix): string
    {
        return str_contains($name, '[') && str_contains($name, ']') ? str_replace(']', '', $name).'_'.$suffix.']' : $name.'_'.$suffix;
    }

    private static function numberOptions(int $from, int $to, mixed $selected): string
    {
        $html = '<option value=""></option>';
        for ($i = $from; $from > $to ? $i >= $to : $i <= $to; $from > $to ? $i-- : $i++) {
            $html .= $i == $selected && $selected !== null && $selected !== ''
                ? '<option value="'.$i.'" selected>'.$i.'</option>'
                : '<option value="'.$i.'">'.$i.'</option>';
        }

        return $html;
    }

    /** getRegistFormDate() */
    private function date(string $name, mixed $required, mixed $req): string
    {
        [$y, $m, $d] = self::dateParts($req);
        $r = self::req($required);

        return '<div class="form-inline" style="font-size: 80%;">'
            .'<select name="'.self::partName($name, 'y').'" class="form-control"'.$r.' style="display: inline-block; width: 5.5em;">'.self::numberOptions(now()->year, 1900, $y).'</select>年 '
            .'<select name="'.self::partName($name, 'm').'" class="form-control"'.$r.' style="display: inline-block; width: 4.5em;">'.self::numberOptions(1, 12, $m).'</select>月 '
            .'<select name="'.self::partName($name, 'd').'" class="form-control"'.$r.' style="display: inline-block; width: 4.5em;">'.self::numberOptions(1, 31, $d).'</select>日 '
            .'</div>';
    }

    /** getRegistFormYearMonth() */
    private function yearMonth(string $name, mixed $required, mixed $req): string
    {
        [$y, $m] = self::dateParts($req);
        $r = self::req($required);

        return '<div class="form-inline" style="font-size: 80%;">'
            .'<select name="'.self::partName($name, 'y').'" class="form-control"'.$r.' style="display: inline-block; width: 6em;">'.self::numberOptions(now()->year, 1900, $y).'</select>年 '
            .'<select name="'.self::partName($name, 'm').'" class="form-control"'.$r.' style="display: inline-block; width: 5em;">'.self::numberOptions(1, 12, $m).'</select>月 '
            .'</div>';
    }

    /** getRegistFormMonthDate(): dos number para mes y día */
    private function monthDate(string $name, mixed $val): string
    {
        $at = fn (int $i) => is_array($val) ? ($val[$i] ?? null) : null;
        $box = fn (int $i, string $str) => '<div class="form-group" style="display: inline-block; vertical-align: top; width:40%;">'
            .'<input type="number" name="'.$name.'['.$i.']" class="form-control" id="'.$name.'_'.($i + 1).'" value="'.self::h($at($i)).'" placeholder="'.$str.'" data-error="'.$str.'を入力してください" style="display:inline-block; width:80%;">'.$str.($i === 0 ? ' ' : '')
            .'<div class="help-block with-errors"></div></div>';

        return $box(0, '月').$box(1, '日');
    }

    /** getRegistFormInputName(): un campo o, con name_input_mode_flg, dos (姓/名 o セイ/メイ) */
    private function inputName(string $name, string $label, mixed $required, mixed $val, bool $split, string $type = '漢字'): string
    {
        $r = self::req($required);
        if (! $split) {
            return '<input type="text" name="'.$name.'" class="form-control" id="'.$name.'" value="'.self::h($val).'" placeholder="'.$label.'を入力してください" data-error="'.$label.'を入力してください"'.$r.'>';
        }
        [$str1, $str2] = $type === 'カナ' ? ['セイ', 'メイ'] : ['姓', '名'];
        $data = explode(' ', is_string($val) ? $val : '');

        return '<div class="form-group" style="display: inline-block; vertical-align: top;">'
            .'<input type="text" name="'.$name.'_1" class="form-control" id="'.$name.'_1" value="'.self::h($data[0] ?? '').'" placeholder="'.$str1.'" data-error="'.$str1.'を入力してください" style="display: inline; width: 8rem;"'.$r.'> '
            .'<div class="help-block with-errors"></div></div>'
            .'<div class="form-group" style="display: inline-block; vertical-align: top;">'
            .'<input type="text" name="'.$name.'_2" class="form-control" id="'.$name.'_2" value="'.self::h($data[1] ?? '').'" placeholder="'.$str2.'" data-error="'.$str2.'を入力してください" style="display: inline; width: 8rem;"'.$r.'>'
            .'<div class="help-block with-errors"></div></div>';
    }

    private function select(string $name, mixed $required, string $options, string $unit = ''): string
    {
        $width = $unit != '' ? 'max-width:80%;' : 'max-width:100%;';

        return '<select name="'.$name.'" class="form-control" style="display:inline; width: auto; '.$width.'"'.self::req($required).'>'.$options.'</select>'.$unit;
    }

    private function checkbox(string $name, array $titles, mixed $req, string $unit): string
    {
        $html = '';
        foreach ($titles as $i => $title) {
            $checked = in_array($title, (array) $req) ? ' checked' : '';
            $html .= '<div style="display:inline-block; margin-right:10px;">'
                .'<input type="checkbox" id="'.$name.'_'.$i.'" name="'.$name.'[]" class="form-control" value="'.$title.'" style="display: inline; width: 30px; height: 30px; vertical-align: middle;"'.$checked.'>'.$title.$unit
                .'</div>';
        }

        return $html;
    }

    /** makeOption() y los make*Options() por valor */
    private function options(array $values, mixed $selected): string
    {
        $html = '<option value="">選択してください</option>';
        foreach ($values as $value) {
            $html .= '<option value="'.$value.'"'.($value == $selected && ! is_array($selected) ? ' selected' : '').'>'.$value.'</option>';
        }

        return $html;
    }

    /** make*Options() por cd (tb_staff, tb_raiten_douki_master) */
    private function keyedOptions(array $values, mixed $selected): string
    {
        $html = '<option value="">選択してください</option>';
        foreach ($values as $key => $value) {
            $html .= '<option value="'.$key.'"'.($key == $selected && ! is_array($selected) ? ' selected' : '').'>'.$value.'</option>';
        }

        return $html;
    }

    /** makeCustomerMailmagazineFlgOptions(): sin opción vacía, comparación estricta */
    private function mailmagaOptions(mixed $selected): string
    {
        $html = '';
        foreach (['1' => '配信あり', '0' => '配信なし'] as $key => $value) {
            $html .= '<option value="'.$key.'"'.((string) $key === $selected ? ' selected' : '').'>'.$value.'</option>';
        }

        return $html;
    }

    // ------------------------------------------------------------------ datos

    /** getCustomerDataFromPost(): fechas en partes → "Y/m/d" y ext_colum según el tipo */
    public function fromPost(array $post): array
    {
        $res = $post;
        if (isset($res['kojin_birth_y'])) {
            $mm = sprintf('%02d', (int) ($res['kojin_birth_m'] ?? 0));
            $dd = sprintf('%02d', (int) ($res['kojin_birth_d'] ?? 0));
            $res['kojin_birth'] = $res['kojin_birth_y'].'/'.($mm == 0 ? '' : $mm).'/'.($dd == 0 ? '' : $dd);
        }
        foreach (['houjin_setsuritsu', 'daihyousha_birth'] as $field) {
            if (isset($res[$field.'_y'])) {
                $res[$field] = $res[$field.'_y'].'/'.($res[$field.'_m'] ?? '').'/'.($res[$field.'_d'] ?? '');
            }
        }
        $ext = (array) ($post['ext_colum'] ?? []);
        foreach ($this->subSettings() as $sub) {
            $cd = $sub['form_cd'];
            $res['ext_colum'][$cd] = match ($sub['form_type']) {
                '年月' => ($ext[$cd.'_y'] ?? '').'/'.($ext[$cd.'_m'] ?? ''),
                '年月日', '起算日' => ($ext[$cd.'_y'] ?? '').'/'.($ext[$cd.'_m'] ?? '').'/'.($ext[$cd.'_d'] ?? ''),
                default => $ext[$cd] ?? null,
            };
        }

        return $res;
    }

    /** getCustomerDataFromId(): el socio activo con ese id (login_pass as mypage_pass), nombre partido y ext_colum */
    public function fromId(string $id): array|false
    {
        $row = DB::table('tb_customer')->where('id', $id)->where('disp_flg', '1')->orderByDesc('cd')
            ->first([DB::raw('*'), 'login_pass as mypage_pass']);
        $res = $row ? array_map(fn ($v) => $v === null ? null : (is_bool($v) ? ($v ? 't' : 'f') : (string) $v), (array) $row) : false;
        if ($res === false) {
            return false;
        }

        foreach (['name', 'name_kana'] as $field) {
            if (isset($res[$field])) {
                $parts = explode(' ', $res[$field]);
                $res[$field.'_1'] = $parts[0];
                $res[$field.'_2'] = $parts[1] ?? null;
            }
        }
        $res['shop_cd'] = $res['tb_shop_cd'];

        $customerCd = DB::table('tb_customer')->where('id', $id)->value('cd');
        foreach ($this->subSettings() as $sub) {
            $text = DB::table('tb_customer_sub')->where('tb_customer_cd', $customerCd)->where('tb_search_cate_cd', (int) $sub['category_cd'])
                ->where('tb_search_form_cd', (int) $sub['form_cd'])->where('form_type', '1')->where('disp_flg', '1')->value('text_data');
            $res['ext_colum'][$sub['form_cd']] = match ($sub['form_type']) {
                'チェックボックス' => explode('<>', (string) $text),
                '年月', '年月日', '起算日' => str_replace('/', '-', (string) $text),
                '月日' => explode('/', (string) $text),
                default => $text,
            };
        }

        return $res;
    }

    // ------------------------------------------------------------------ alta / modificación

    /**
     * addCustomerCRM(): valida, y da de alta (o modifica, con $update y $post['id']) el socio con los campos de
     * tb_koumoku y sus campos personalizados. Alta: 会員番号 = login_id con createID, contraseña de 4 cifras, sellos
     * de bienvenida (tb_stamp_config cd 1) y grupo por defecto; con touroku_kubun = 1 manda el correo de
     * confirmación (member_comp.php). Devuelve ['status' => 'ok'|'error', 'errMsg' => …, 'data' => fromId()].
     */
    public function save(string $domain, array $post, bool $update = false): array
    {
        $errors = $this->validate($domain, $post, $update);
        if ($errors) {
            return ['status' => 'error', 'errMsg' => implode('<br>', $errors)];
        }

        $shopCd = (string) ($post['shop_cd'] ?? '');
        $time = (string) now()->getTimestamp();
        try {
            $id = DB::transaction(fn () => $update ? $this->updateRow($post, $shopCd, $time) : $this->insertRow($post, $shopCd, $time));
        } catch (Throwable) {
            return ['status' => 'error', 'errMsg' => $update ? '変更に失敗しました' : '会員登録に失敗しました'];
        }

        if (! $update && (int) ($post['touroku_kubun'] ?? 0) === 1 && ! empty($post['email1'])) {
            $this->sendConfirmation($domain, $shopCd, (string) $post['email1'], $id['cd']);
        }
        $this->pos->syncCustomers('unsynchronized'); // synchronizePRO (POS fuera de alcance)

        return ['status' => 'ok', 'errMsg' => '', 'data' => $this->fromId($id['id'])];
    }

    /** Errores de addCustomerCRM() en su orden; normaliza $post (nombre, カナ, 〒, fechas) */
    private function validate(string $domain, array &$post, bool $update): array
    {
        $errors = [];
        if (isset($post['email1']) && $post['email1'] != '') {
            $used = DB::table('tb_customer')->where('disp_flg', '1')->where('touroku_kubun', '2')
                ->whereRaw('trim(email1) = ?', [(string) $post['email1']])
                ->when($update, fn ($q) => $q->where('id', '!=', (string) ($post['id'] ?? '')))->count();
            if ($used > 0) {
                $errors[] = 'すでにそのメールアドレスは使われています';
            }
        }

        foreach (['name' => false, 'name_kana' => true] as $field => $kana) {
            $one = $post[$field.'_1'] ?? null;
            $two = $post[$field.'_2'] ?? null;
            if ((isset($post[$field.'_1']) || isset($post[$field.'_2'])) && ($one != '' || $two != '')) {
                if ($kana) {
                    $one = $one != '' ? mb_convert_kana((string) $one, 'KVC') : $one;
                    $two = $two != '' ? mb_convert_kana((string) $two, 'KVC') : $two;
                    [$post[$field.'_1'], $post[$field.'_2']] = [$one, $two];
                }
                $post[$field] = $one.($one && $two ? ' ' : '').$two;
            }
        }
        if (! self::isKana($post['name_kana'] ?? null)) {
            $errors[] = 'カタカナで入力してください';
        }
        if (isset($post['zip']) && $post['zip'] != '') {
            $post['zip'] = mb_convert_kana(str_replace('-', '', (string) $post['zip']), 'a');
            if (! preg_match('/^[0-9]+$/u', $post['zip'])) {
                $errors[] = '半角数字のみで入力してください';
            }
        }
        foreach (['add_build_kana', 'kinmusaki_name_kana'] as $field) {
            if (! self::isKana($post[$field] ?? null)) {
                $errors[] = 'カタカナで入力してください';
            }
        }
        if (in_array($domain, ['air.crm-s.net', 'earth.crm-s.net'], true) && ! empty($post['kojin_mobile'])) {
            if (mb_strlen((string) $post['kojin_mobile']) != 11) {
                $errors[] = '携帯電話番号は11桁で入力してください';
            } elseif (! preg_match('/^(070|080|090|050).*$/', (string) $post['kojin_mobile'])) {
                $errors[] = '携帯電話番号は『070、080、090、050』から開始してください';
            }
        }

        if (! empty($post['kojin_birth_y']) && ! empty($post['kojin_birth_m']) && ! empty($post['kojin_birth_d'])) {
            $post['kojin_birth'] = $post['kojin_birth_y'].'/'.sprintf('%02d', (int) $post['kojin_birth_m']).'/'.sprintf('%02d', (int) $post['kojin_birth_d']);
        }
        foreach (['houjin_setsuritsu', 'daihyousha_birth'] as $field) {
            if (! empty($post[$field.'_y']) && ! empty($post[$field.'_m']) && ! empty($post[$field.'_d'])) {
                $post[$field] = $post[$field.'_y'].'/'.$post[$field.'_m'].'/'.$post[$field.'_d'];
            }
        }

        foreach ($this->subSettings() as $sub) {
            $value = $post['ext_colum'][$sub['form_cd']] ?? null;
            if ($sub['hissu'] && empty($value)) {
                $errors[] = $sub['label_name'].'は必ず入力してください。';
            } elseif ($sub['hissu'] && $sub['form_type'] === '年月日') {
                [$y, $m, $d] = array_pad(explode('/', is_string($value) ? $value : ''), 3, null);
                if ($y == '' || $m == '' || $d == '') {
                    $errors[] = $sub['label_name'].'は必ず入力してください。';
                }
            }
        }

        return $errors;
    }

    /** isKana(): vacío o solo カタカナ y espacios */
    public static function isKana(mixed $value): bool
    {
        return ! isset($value) || $value == '' || preg_match('/^[ァ-ヾ ]+$/u', (string) $value) === 1;
    }

    /** Columnas de tb_customer de los campos de tb_koumoku (+ fecha de nacimiento, 紹介者) */
    private function columns(array $post): array
    {
        $columns = [];
        foreach ($this->settings() as $setting) {
            $name = $setting['input_name'];
            $value = $post[$name] ?? null;
            if (in_array($name, ['tb_staff_cd', 'tb_raiten_douki_master_cd'], true)) {
                if ($value !== null && $value !== '' && ! preg_match('/^-?\d+$/', (string) $value)) {
                    throw new \RuntimeException('cd no numérico'); // el legacy lo ponía sin comillas en el SQL: error
                }
                $columns[$name] = $value === null || $value === '' ? null : (int) $value;
            } else {
                $columns[$name] = is_array($value) ? 'Array' : (string) $value;
            }
        }
        // mailmaga_email_type: el legacy usaba una variable sin asignar → siempre ''
        if (isset($post['email1']) && $post['email1'] != '') {
            $columns['mailmaga_email_type'] = '';
        }
        if (isset($post['kojin_birth']) && ($birth = strtotime((string) $post['kojin_birth'])) !== false) {
            $columns['kojin_birth_date'] = $birth;
        }
        if (isset($post['introducer_cd']) && preg_match('/^[0-9]+$/', (string) $post['introducer_cd'])) {
            $columns['shoukai_tb_customer_cd'] = (int) $post['introducer_cd'];
        }

        return $columns;
    }

    /** @return array{cd: int, id: string} */
    private function insertRow(array $post, string $shopCd, string $time): array
    {
        if (! preg_match('/^-?\d+$/', $shopCd)) {
            throw new \RuntimeException('tienda no numérica');
        }
        $columns = $this->columns($post);
        if (! isset($post['mailmaga_flg'])) {
            $columns['mailmaga_flg'] = '1';
        }

        // Sellos de bienvenida (tb_stamp_config cd 1) con caducidad admin_master.stamp_shikkou_kikan (365 sin fila)
        $config = DB::table('tb_stamp_config')->where('cd', 1)->first(['touroku_bonus', 'stamp_max']);
        $stampCount = min((int) ($config->touroku_bonus ?? 0), (int) ($config->stamp_max ?? 0));
        $stampLimit = null;
        if ($stampCount > 0) {
            $master = DB::table('admin_master')->where('cd', 1)->first(['stamp_shikkou_kikan']);
            $days = $master ? (int) $master->stamp_shikkou_kikan : 365;
            $stampLimit = now()->startOfDay()->addDays($days)->getTimestamp() - 1;
        }

        $group = DB::table('tb_customer_group')->where('disp_flg', '1')->where('default_flg', '1')->value('cd');
        if ($group !== null && preg_match('/^[1-9][0-9]*$/', (string) $group)) {
            $columns['tb_customer_group_cd'] = (int) $group;
        }

        // createID con la tienda tal cual llega (texto) y createHatsuban
        $id = $shopCd.'1'.sprintf('%05s', DB::table('tb_customer')->count() + 1);
        $cd = $this->registration->uniqueCd((int) $shopCd);
        $kubun = (int) ($post['touroku_kubun'] ?? 0) > 0 ? (int) $post['touroku_kubun'] : 2;

        DB::table('tb_customer')->insert(['cd' => $cd, 'id' => $id, 'tb_shop_cd' => (int) $shopCd] + $columns + [
            'disp_flg' => '1', 'touroku_kubun' => (string) $kubun, 'login_id' => $id,
            'login_pass' => $this->registration->randomPassword(), 'synchronize_flg' => '', 'local_dl_sync_flg' => '',
            'stamp_count' => $stampCount > 0 ? $stampCount : null, 'stamp_yukoukigen' => $stampLimit,
            'touroku_date' => $time, 'update_date' => $time,
        ]);
        if ($stampCount > 0) {
            $this->stamps->log(['tb_shop_cd' => (int) $shopCd, 'tb_customer_cd' => $cd, 'sounyu_stamp' => $stampCount, 'action_flg' => '2',
                'raiten_shori' => '', 'meisai_mail' => '', 'shiharai_houhou' => '', 'touroku_date' => $time]);
        }

        foreach ($this->subSettings() as $sub) {
            DB::table('tb_customer_sub')->insert([
                'cd' => $this->registration->uniqueCd((int) $shopCd), 'tb_customer_cd' => $cd,
            ] + $this->subRow($sub, $post, $time));
        }

        return ['cd' => $cd, 'id' => $id];
    }

    /** @return array{cd: int, id: string} */
    private function updateRow(array $post, string $shopCd, string $time): array
    {
        if (! preg_match('/^-?\d+$/', $shopCd)) {
            throw new \RuntimeException('tienda no numérica'); // el legacy la ponía sin comillas: SQL inválido
        }
        $id = (string) ($post['id'] ?? '');
        DB::table('tb_customer')->where('id', $id)->update($this->columns($post) + [
            'tb_shop_cd' => (int) $shopCd, 'update_date' => $time, 'synchronize_flg' => '',
        ]);

        $customerCd = DB::table('tb_customer')->where('id', $id)->value('cd');
        foreach ($this->subSettings() as $sub) {
            $row = $this->subRow($sub, $post, $time);
            $existing = DB::table('tb_customer_sub')->where('tb_customer_cd', $customerCd)->where('tb_search_cate_cd', (int) $sub['category_cd'])
                ->where('tb_search_form_cd', (int) $sub['form_cd'])->where('form_type', '1')->where('disp_flg', '1')->value('cd');
            if ($existing !== null) {
                DB::table('tb_customer_sub')->where('cd', $existing)->update(['update_date' => $time, 'text_data' => $row['text_data'], 'synchronize_flg' => '']);
            } else {
                DB::table('tb_customer_sub')->insert(['cd' => $this->registration->uniqueCd((int) $shopCd), 'tb_customer_cd' => $customerCd] + $row);
            }
        }

        return ['cd' => (int) $customerCd, 'id' => $id];
    }

    /** Fila de tb_customer_sub de un campo personalizado (texto normalizado según el tipo) */
    private function subRow(array $sub, array $post, string $time): array
    {
        $value = $post['ext_colum'][$sub['form_cd']] ?? null;
        $text = match ($sub['form_type']) {
            // (array)'' cuenta 1: el legacy guardaba "<>"
            'チェックボックス' => count((array) $value) > 0 ? implode('<>', (array) $value).'<>' : '',
            '年月', '年月日', '起算日' => str_replace('-', '/', (string) $value),
            '月日' => (is_array($value) ? ($value[0] ?? '') : '').'/'.(is_array($value) ? ($value[1] ?? '') : ''),
            default => is_array($value) ? 'Array' : (string) $value,
        };

        return [
            'tb_search_cate_cd' => (int) $sub['category_cd'], 'tb_search_form_cd' => (int) $sub['form_cd'], 'text_data' => $text,
            'form_type' => '1', 'disp_flg' => '1', 'form_title' => $sub['label_name'], 'touroku_date' => $time, 'update_date' => $time,
            'synchronize_flg' => '', 'local_sync_flg' => '',
        ];
    }

    /** Correo 会員登録確認メール con el enlace a member_comp.php (alta provisional, touroku_kubun = 1) */
    private function sendConfirmation(string $domain, string $shopCd, string $email, int $customerCd): void
    {
        $shopName = (string) DB::table('tb_shop')->where('cd', (int) $shopCd)->value('kaishamei');
        $body = '「'.$shopName."」に会員登録ありがとうございます。\n"
            ."下記のURLにアクセスすると正式に登録完了となります。\n"
            .'http://'.$domain.'/appview/member_comp.php?shopid='.$shopCd.'&kaiincd='.$customerCd."\n"
            ."（※もし本メールに心当たりの無い場合は上記URLにアクセスしないようお願いします）\n";

        Mail::raw($body, fn ($message) => $message->to($email)->subject('会員登録確認メール')
            ->from('mail_mag@'.$domain, $shopName.'登録センター'));
    }
}
