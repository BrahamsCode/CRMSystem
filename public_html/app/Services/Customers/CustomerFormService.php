<?php

namespace App\Services\Customers;

use App\Models\Customers\Customer;
use App\Models\Staff;
use App\Support\LegacyCheck;
use App\Support\LegacyText;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;

/**
 * Datos y validación de la pantalla de alta/edición de clientes (legacy `system/category1/inc/customer_new.php`
 * y `customer_new_confirm.php`): opciones de los selects, valores iniciales, normalización de lo enviado
 * ($data_array del confirm) y las comprobaciones de error_check.php con los mismos textos.
 */
class CustomerFormService
{
    /** Campos del cliente con mensaje de error en form.blade.php (y 'id', que el legacy valida sin mostrar) */
    public const ERROR_FIELDS = [
        'id', 'tb_shop_cd', 'type', 'name', 'name_kana', 'zip', 'add_kana', 'add_build_kana', 'tel', 'fax', 'email1', 'email2',
        'kojin_birth', 'kojin_mobile', 'imei', 'kojin_mail', 'kinmusaki_name_kana', 'kinmusaki_tel', 'kinmusaki_fax',
        'houjin_setsuritsu', 'houjin_shihon', 'daihyousha_name_kana', 'daihyousha_birth', 'tantou_name_kana', 'tantou_tel',
        'tantou_fax', 'tantou_mail', 'login_pass',
    ];

    /** Teléfonos a los que el confirm quita guiones (replace_tel) */
    private const TEL_FIELDS = ['tel', 'fax', 'kojin_mobile', 'kinmusaki_tel', 'kinmusaki_fax', 'tantou_tel', 'tantou_fax'];

    /** Fechas partidas en _y/_m/_d (mes y día a 2 cifras en el confirm) */
    private const DATE_FIELDS = ['kojin_birth', 'daihyousha_birth', 'houjin_setsuritsu'];

    // ── Opciones de la pantalla ─────────────────────────────────────────────

    /** Variables comunes de form.blade.php / confirm.blade.php */
    public function viewOptions(Staff $staff): array
    {
        $isMaster = $staff->isMaster();
        $shopCd = $this->sessionShopCd($staff);
        $shops = $this->shops($isMaster ? null : $shopCd);

        return [
            'isMaster' => $isMaster,
            'shops' => $shops,
            'currentShopCd' => $shopCd,
            'currentShopName' => DB::table('tb_shop')->where('cd', $shopCd)->value('kaishamei'),
            'nameInputMode' => $this->nameInputMode(),
            'staffEnabled' => $this->staffEnabled(),
            'staffs' => $this->staffs($isMaster ? null : $shopCd),
            'jobs' => DB::table('tb_job')->orderBy('cd')->pluck('name')->all(),
            'imeiEnabled' => $this->imeiEnabled(),
            'reminderEnabled' => DB::table('tb_base_system')->where('system_flg', '1')->whereIn('cd', [9, 11])->exists(),
            'customerGroups' => DB::table('tb_customer_group')->where('disp_flg', '1')->orderBy('rank')->get(['cd', 'name']),
            'raitenDoukis' => DB::table('tb_raiten_douki_master')->where('disp_flg', '1')->orderBy('rank')->get(['cd', 'name']),
            'customCategories' => $this->customCategories(),
            'menuItems' => $this->menuItems($isMaster ? null : $shopCd),
        ];
    }

    /** Tienda de la sesión ($_SESSION['shop_cd']) */
    public function sessionShopCd(Staff $staff): int
    {
        return (int) (session('shop_cd') ?? $staff->shop_cd);
    }

    /** tb_shop visibles (disp_flg <> '9'); sin master solo la de la sesión */
    private function shops(?int $shopCd): Collection
    {
        return DB::table('tb_shop')->whereRaw("COALESCE(disp_flg, '') <> '9'")
            ->when($shopCd !== null, fn ($q) => $q->where('cd', $shopCd))
            ->orderBy('cd')->get(['cd', 'kaishamei']);
    }

    /** contents_master tb_c1_customer / name_input_mode_flg: '1' = 姓/名 separados */
    public function nameInputMode(): string
    {
        return (string) DB::table('contents_master')->where('category', 'tb_c1_customer')
            ->where('field', 'name_input_mode_flg')->where('disp_flg', '1')->value('text_data');
    }

    /** Módulo スタッフ管理 activo (tb_base_system_child) */
    private function staffEnabled(): bool
    {
        return DB::table('tb_base_system_child')->where('system_name', 'スタッフ管理')->where('system_flg', '1')->exists();
    }

    /** 指名担当者: tb_staff con disp_flg='1' y reserve_flg='1' */
    private function staffs(?int $shopCd): Collection
    {
        return DB::table('tb_staff')->where('disp_flg', '1')->where('reserve_flg', '1')
            ->when($shopCd !== null, fn ($q) => $q->where('shop_cd', $shopCd))
            ->orderBy('shop_cd')->orderBy('cd')->get(['cd', 'name', 'shop_cd']);
    }

    /** checkImeiItem(): tb_koumoku system_cate='1' y tb_koumoku='imei' */
    public function imeiEnabled(): bool
    {
        return DB::table('tb_koumoku')->where('system_cate', '1')->where('tb_koumoku', 'imei')->exists();
    }

    /**
     * Categorías de campos personalizados de un solo registro (record_type vacío) con sus campos
     * de administración (hyouji_flg 1 o 2), como el bloque カスタマイズ de customer_new*.php.
     */
    public function customCategories(): Collection
    {
        $categories = DB::table('tb_search_cate')
            ->where('category', 'category1')
            ->whereRaw("COALESCE(disp_flg, '') <> '9'")
            ->where('hyouji_flg', '1')
            ->whereRaw("COALESCE(record_type, '') = ''")
            ->orderBy('rank')
            ->get(['cd', 'name', 'disp_column']);

        $forms = DB::table('tb_search_form')
            ->whereIn('tb_search_cate_cd', $categories->pluck('cd'))
            ->whereRaw("COALESCE(disp_flg, '') <> '9'")
            ->whereIn('hyouji_flg', ['1', '2'])
            ->orderBy('rank')
            ->get()->groupBy('tb_search_cate_cd');

        return $categories->map(fn ($c) => [
            'cd' => (int) $c->cd,
            'name' => $c->name,
            'disp_column' => $c->disp_column,
            'forms' => ($forms[$c->cd] ?? collect())->map(fn ($f) => (array) $f)->values()->all(),
        ]);
    }

    /** makeMenuTable(): menús de tb_yoyaku_koumoku_master para el tipo メニュー項目 */
    public function menuItems(?int $shopCd): Collection
    {
        return DB::table('tb_yoyaku_koumoku_master as tb1')
            ->leftJoin('tb_yoyaku_koumoku_category as tb2', function ($join) {
                $join->on('tb1.tb_yoyaku_koumoku_category_cd', '=', 'tb2.cd')->where('tb2.disp_flg', '1');
            })
            ->where('tb1.disp_flg', '1')
            ->when($shopCd !== null, fn ($q) => $q->whereRaw("COALESCE(tb2.disp_flg, '') <> '9'")->whereIn('tb1.tb_shop_cd', [$shopCd, -1]))
            ->orderBy('tb1.type_flg')->orderBy('tb2.rank')->orderBy('tb1.tb_shop_cd')->orderBy('tb1.rank')
            ->get(['tb1.cd', 'tb1.name', 'tb1.tb_shop_cd', 'tb2.name as category_name'])
            ->map(fn ($r) => (array) $r);
    }

    // ── Valores iniciales ───────────────────────────────────────────────────

    /** Alta: 会員番号 de createID con la tienda de la sesión, contraseña de 4 cifras y ?tel= de CTI */
    public function newData(Staff $staff, ?string $tel, CustomerRegistrationService $registration): array
    {
        $data = [
            'login_pass' => $registration->randomPassword(),
            'id' => $registration->createId($this->sessionShopCd($staff)),
        ];
        if ($tel) {
            $data['tel'] = $tel;
        }

        return $data;
    }

    /** Edición: columnas de tb_customer + 姓/名 partidos, fechas partidas y el 紹介者 '【id】  nombre' */
    public function editData(Customer $customer): array
    {
        $data = $customer->getAttributes();
        [$data['name_sei'], $data['name_mei']] = $customer->nameParts('name');
        [$data['name_kana_sei'], $data['name_kana_mei']] = $customer->nameParts('name_kana');

        $referrer = $customer->shoukai_tb_customer_cd
            ? DB::table('tb_customer')->where('cd', $customer->shoukai_tb_customer_cd)->first(['id', 'name'])
            : null;
        $data['shoukai_tb_customer_cd'] = $referrer ? $customer->shoukai_tb_customer_cd : null;
        $data['shoukaisha_name'] = $referrer ? '【' . $referrer->id . '】  ' . $referrer->name : null;

        return $this->splitDates($data);
    }

    /** Datos que vuelven del confirm (書き直す) o de un error: mismos nombres que el POST */
    public function postedData(array $data): array
    {
        $data['shoukai_tb_customer_cd'] = $data['searchKaiin_cd'] ?? null;
        $data['shoukaisha_name'] = $data['searchKaiin_name'] ?? null;

        return $data;
    }

    /** Valores de los campos personalizados guardados (createform_insert_request) */
    public function customValues(Customer $customer): array
    {
        $values = [];
        $subs = DB::table('tb_customer_sub')->where('tb_customer_cd', $customer->cd)
            ->whereRaw("COALESCE(disp_flg, '') <> '9'")->orderBy('cd')->get()->groupBy('tb_search_form_cd');

        foreach ($this->customCategories() as $category) {
            foreach ($category['forms'] as $form) {
                $values = array_replace_recursive($values, $this->fieldValues($form, $subs[$form['cd']][0] ?? null));
            }
        }

        return $values;
    }

    /**
     * createform_insert_request(): valores de formulario (form{cd}, form{cd}_{n}, form{cd}yyy…) de UN campo a partir
     * de su fila de tb_customer_sub (null = sin valor). Lo usan la edición del cliente y 来店履歴の追加・修正.
     */
    public function fieldValues(array $form, ?object $sub): array
    {
        $values = [];
        $text = (string) ($sub->text_data ?? '');
        $f = 'form' . $form['cd'];

        switch ($form['form_type']) {
            case 'テキスト': case '半角英数テキスト': case 'メールアドレス': case 'テキストエリア':
            case '画像とテキスト': case 'ラジオボタン': case 'セレクト':
                $values[$f] = $sub->text_data ?? null;
                break;
            case '数値テキスト':
                $values[$f] = $sub->float_data ?? null;
                break;
            case 'チェックボックス':
                foreach (explode('<>', (string) $form['data']) as $i => $value) {
                    if ($value !== '' && preg_match('/>' . preg_quote($value, '/') . '</', '>' . $text)) {
                        $values[$f . '_' . $i] = $value;
                    }
                }
                break;
            case '年':
                $values[$f . 'yyy'] = $sub->text_data ?? null;
                break;
            case '年月':
                $parts = explode('/', $text);
                [$values[$f . 'yyy'], $values[$f . 'mmm']] = [$parts[0] ?? null, $parts[1] ?? null];
                break;
            case '年月日': case '起算日':
                $parts = explode('/', $text);
                [$values[$f . 'yyy'], $values[$f . 'mmm'], $values[$f . 'ddd']] = [$parts[0] ?? null, $parts[1] ?? null, $parts[2] ?? null];
                break;
            case '月日':
                $parts = explode('/', $text);
                [$values[$f . 'mmm'], $values[$f . 'ddd']] = [$parts[0] ?? null, $parts[1] ?? null];
                break;
            case 'メニュー項目':
                if ($text !== '') {
                    foreach (explode('<>', $text) as $menuCd) {
                        $values['yoyaku_menu_cd'][$menuCd] = '1';
                    }
                }
                break;
            case 'テーブル':
                foreach (explode('||', $text) as $col => $row) {
                    foreach (explode('<>', $row) as $i => $cell) {
                        $values[$f . '_' . $col . '_' . $i] = $cell;
                    }
                }
                break;
        }

        return $values;
    }

    /** Parte kojin_birth/daihyousha_birth/houjin_setsuritsu ('Y/m/d') en _y/_m/_d */
    private function splitDates(array $data): array
    {
        foreach (self::DATE_FIELDS as $field) {
            $parts = explode('/', (string) ($data[$field] ?? ''));
            $data[$field . '_y'] = $parts[0] ?? '';
            $data[$field . '_m'] = $parts[1] ?? '';
            $data[$field . '_d'] = $parts[2] ?? '';
        }

        return $data;
    }

    // ── Confirmación: normalización y errores ───────────────────────────────

    /**
     * $data_array de customer_new_confirm.php: recorte (get_form2 sin entidades: Blade escapa al imprimir), teléfonos sin
     * guiones, mes/día a 2 cifras, 姓/名 unidos con espacio 半角, カナ con mb_convert_kana 'KVC' y 'ゲスト' si no hay nombre.
     * Sin master, la tienda es siempre la de la sesión.
     */
    public function normalize(array $input, Staff $staff): array
    {
        $data = [];
        foreach (collect($input)->except(['_token', '_method', 'enter', 'modoru']) as $key => $value) {
            if ($key === 'yoyaku_menu_cd' && is_array($value)) {
                $data[$key] = array_map(fn ($v) => is_array($v) ? '' : (string) $v, $value);
            } elseif (! is_array($value)) {
                $data[$key] = LegacyText::trim((string) $value);
            }
        }

        if (! $staff->isMaster()) {
            $data['tb_shop_cd'] = (string) $this->sessionShopCd($staff);
        }

        $data = $this->postedData($data);

        foreach (self::TEL_FIELDS as $field) {
            $data[$field] = (string) LegacyCheck::replaceTel($data[$field] ?? '');
        }

        foreach (self::DATE_FIELDS as $field) {
            foreach (['_m', '_d'] as $suffix) {
                if (($data[$field . $suffix] ?? '') !== '') {
                    $data[$field . $suffix] = str_pad($data[$field . $suffix], 2, '0', STR_PAD_LEFT);
                }
            }
        }

        if ($this->nameInputMode() === '1') {
            // 空白は一度全て取り除き、改めて姓と名を空白で繋ぐ
            foreach (['name_kana_sei', 'name_kana_mei', 'name_sei', 'name_mei'] as $field) {
                $data[$field] = (string) preg_replace('/(\s|　)/u', '', $data[$field] ?? '');
            }
            $data['name_kana'] = $data['name_kana_sei'] . ' ' . $data['name_kana_mei'];
            $data['name'] = $data['name_sei'] . ' ' . $data['name_mei'];
        }

        $data['name_kana'] = mb_convert_kana((string) ($data['name_kana'] ?? ''), 'KVC');

        // 名前が空なら「ゲスト」（必須チェックではない: 2013.04.01）
        if (trim(trim((string) ($data['name'] ?? '')), '　') === '') {
            $data['name'] = $data['name_sei'] = 'ゲスト';
        }

        return $data;
    }

    /** Las comprobaciones de customer_new_confirm.php, en el mismo orden y con los mismos textos */
    public function validate(array $data, ?int $customerCd): LegacyCheck
    {
        $check = new LegacyCheck;
        $d = fn (string $key) => $data[$key] ?? '';

        // 重複チェック: メールアドレス1 (socios activos)
        if ($d('email1') !== '') {
            $duplicated = DB::table('tb_customer')->where('email1', $d('email1'))
                ->where('touroku_kubun', '2')->where('disp_flg', '1')
                ->when($customerCd, fn ($q) => $q->where('cd', '<>', $customerCd))->exists();
            if ($duplicated) {
                $check->add('email1', '<p class="m0">※既に使用されているメールアドレスです。</p>');
            }
        }

        // カスタムレイアウト (mycolle app_layout_mode = 1) の場合は携帯電話番号の重複チェック
        $appLayoutMode = DB::table('contents_master')->where('category', 'mycolle')->where('field', 'app_layout_mode')
            ->where('disp_flg', '1')->value('text_data');
        if ($appLayoutMode == '1' && $d('kojin_mobile') !== '') {
            $duplicated = DB::table('tb_customer')
                ->whereRaw("replace(replace(kojin_mobile, '-', ''), ' ', '') = ?", [$d('kojin_mobile')])
                ->when($customerCd, fn ($q) => $q->where('cd', '<>', $customerCd))->exists();
            if ($duplicated) {
                $check->add('kojin_mobile', '<p class="m0">※既に使用されている携帯電話番号です。</p>');
            }
        }

        $date = fn (string $field) => $d($field . '_y') . '/' . $d($field . '_m') . '/' . $d($field . '_d');

        $check->alnumRequired('id', $d('id'))
            ->blank('tb_shop_cd', $d('tb_shop_cd'))
            ->blank('type', $d('type'))
            ->katakana('name_kana', $d('name_kana'))
            ->zip('zip', $d('zip'))
            ->katakana('add_kana', $d('add_kana'))
            ->katakanaAlnum('add_build_kana', $d('add_build_kana'))
            ->num('tel', $d('tel'))
            ->num('fax', $d('fax'))
            ->mail('email1', $d('email1'))
            ->mail('email2', $d('email2'))
            ->date2('kojin_birth', $date('kojin_birth'))
            ->num('kojin_mobile', $d('kojin_mobile'))
            ->mail('kojin_mail', $d('kojin_mail'))
            ->katakana('kinmusaki_name_kana', $d('kinmusaki_name_kana'))
            ->num('kinmusaki_tel', $d('kinmusaki_tel'))
            ->num('kinmusaki_fax', $d('kinmusaki_fax'))
            ->date2('houjin_setsuritsu', $date('houjin_setsuritsu'))
            ->num('houjin_shihon', $d('houjin_shihon'))
            ->katakana('daihyousha_name_kana', $d('daihyousha_name_kana'))
            ->date2('daihyousha_birth', $date('daihyousha_birth'))
            ->katakana('tantou_name_kana', $d('tantou_name_kana'))
            ->num('tantou_tel', $d('tantou_tel'))
            ->num('tantou_fax', $d('tantou_fax'))
            ->mail('tantou_mail', $d('tantou_mail'))
            ->blank('login_pass', $d('login_pass'));

        foreach ($this->customCategories() as $category) {
            foreach ($category['forms'] as $form) {
                $message = $this->customFieldError($form, $data);
                if ($message !== '') {
                    $check->add('form' . $form['cd'], $message);
                }
            }
        }

        // El legacy mostraba "半角数字" bajo IMEI al repintar con errores, sin que IMEI provocara el error
        if ($check->fails() && $d('imei') && ! preg_match('/^[0-9]+$/', $d('imei'))) {
            $check->add('imei', '<p class="m0">※半角数字で入力して下さい。</p>');
        }

        return $check;
    }

    /** Errores de LegacyCheck como MessageBag en texto plano (form.blade.php imprime `<p class="m0">{{ $message }}</p>`) */
    public function errorBag(LegacyCheck $check): MessageBag
    {
        $bag = new MessageBag;
        $fields = self::ERROR_FIELDS;
        foreach ($this->customCategories() as $category) {
            foreach ($category['forms'] as $form) {
                $fields[] = 'form' . $form['cd'];
            }
        }

        foreach ($fields as $field) {
            foreach (preg_split('#</p>|<br\s*/?>#i', $check->messages($field)) as $html) {
                $text = trim(strip_tags($html));
                if ($text !== '') {
                    $bag->add($field, $text);
                }
            }
        }

        return $bag;
    }

    /** createform_errorcheck() de system/inc/function.php (mensaje sin el <br> final; '' sin error). También lo usa VisitRecordService */
    public function customFieldError(array $form, array $data): string
    {
        $f = 'form' . $form['cd'];
        $v = fn (string $key) => (string) ($data[$key] ?? '');
        $required = (string) $form['hissu'] === '1';
        $error = '';

        switch ($form['form_type']) {
            case 'テキスト': case '半角英数テキスト': case 'テキストエリア': case '画像とテキスト':
            case 'ラジオボタン': case 'セレクト':
                if ($required && $v($f) === '') {
                    $error = '※必須項目です。';
                }
                break;

            case 'メールアドレス':
                if ($required && $v($f) === '') {
                    $error = '※必須項目です。';
                } elseif ($v($f) !== '' && ! preg_match('/^[a-zA-Z0-9_\.\-]+?@[A-Za-z0-9_\.\-]+$/', $v($f))) {
                    $error = '※メールアドレスが正しくありません。';
                } elseif ($v($f) !== '') {
                    // El legacy también lo comprobaba con el campo vacío (checkdnsrr de un dominio vacío): solo con valor
                    $domain = explode('@', $v($f), 2)[1] ?? '';
                    if ($domain === '' || ! checkdnsrr($domain, 'MX')) {
                        $error = '使用できないメールアドレスです。';
                    }
                }
                break;

            case '数値テキスト':
                if ($required && $v($f) === '') {
                    $error = '※必須項目です。';
                } elseif ($v($f) !== '' && ! preg_match('/^[0-9.]+$/', $v($f))) {
                    $error = '※半角数字で入力して下さい';
                }
                break;

            case 'チェックボックス':
                $checked = false;
                for ($i = 0; $i < (int) $form['flds_row']; $i++) {
                    $checked = $checked || (bool) ($data[$f . '_' . $i] ?? null);
                }
                if ($required && ! $checked) {
                    $error = '※必須項目です。';
                }
                break;

            case '年':
                if ($required && $v($f . 'yyy') === '') {
                    $error = '※必須項目です。';
                }
                if ($v($f . 'yyy') !== '' && ! preg_match('/^[0-9]+$/', $v($f . 'yyy'))) {
                    $error = '※半角数字で入力して下さい';
                }
                break;

            case '年月':
                if ($required && $v($f . 'yyy') === '' && $v($f . 'mmm') === '') {
                    $error = '※必須項目です。';
                }
                if ($v($f . 'yyy') !== '' && $v($f . 'mmm') === '') {
                    $error = '※月を入力して下さい';
                }
                if ($v($f . 'yyy') !== '' && ! preg_match('/^[0-9]+$/', $v($f . 'yyy'))) {
                    $error = '※年は半角数字で入力して下さい';
                }
                if ($v($f . 'mmm') !== '' && ! preg_match('/^[0-9]+$/', $v($f . 'mmm'))) {
                    $error = '※月は半角数字で入力して下さい';
                }
                break;

            case '年月日': case '起算日':
                if ($required && $v($f . 'yyy') === '' && $v($f . 'mmm') === '' && $v($f . 'ddd') === '') {
                    $error = '※必須項目です。';
                }
                if ($v($f . 'yyy') !== '' && ! preg_match('/^[0-9]+$/', $v($f . 'yyy'))) {
                    $error = '※年は半角数字で入力して下さい';
                }
                if ($error === '') {
                    if ($v($f . 'yyy') !== '' && $v($f . 'mmm') === '') {
                        $error = '※月を入力して下さい';
                    }
                    if ($v($f . 'yyy') !== '' && $v($f . 'ddd') === '') {
                        $error = '※日を入力して下さい';
                    }
                }
                if ($v($f . 'mmm') !== '' && ! preg_match('/^[0-9]+$/', $v($f . 'mmm'))) {
                    $error = '※月は半角数字で入力して下さい';
                }
                if ($v($f . 'ddd') !== '' && ! preg_match('/^[0-9]+$/', $v($f . 'ddd'))) {
                    $error = '※日は半角数字で入力して下さい';
                }
                break;

            case '月日':
                if ($required && $v($f . 'mmm') === '' && $v($f . 'ddd') === '') {
                    $error = '※必須項目です。';
                }
                if ($v($f . 'mmm') !== '' && ! preg_match('/^[0-9]+$/', $v($f . 'mmm'))) {
                    $error = '※月は半角数字で入力して下さい';
                }
                if ($v($f . 'ddd') !== '' && ! preg_match('/^[0-9]+$/', $v($f . 'ddd'))) {
                    $error = '※日は半角数字で入力して下さい';
                }
                break;

            case 'メニュー項目':
                if ($required && ! isset($data['yoyaku_menu_cd'])) {
                    $error = '※必須項目です。';
                }
                break;

            case 'テーブル':
                $all = '';
                for ($col = 0; $col < (int) $form['flds_col']; $col++) {
                    for ($i = 0; $i < (int) $form['flds_row']; $i++) {
                        $all .= $v($f . '_' . $col . '_' . $i);
                    }
                }
                if ($required && $all === '') {
                    $error = '※必須項目です。';
                }
                break;
        }

        return $error;
    }
}
