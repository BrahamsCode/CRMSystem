<?php

namespace App\Services\Customers;

use App\Models\Customers\Customer;
use App\Models\Customers\CustomerSub;
use App\Support\LegacyText;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Alta y edición de clientes como `system/category1/inc/customer_new_register.php`:
 * columnas de tb_customer con get_form() (LegacyText::form), 会員番号 con createID(), cd con createHatsuban(),
 * login_id = 会員番号 y login_pass en claro (contrato de la app y las API), y una fila de tb_customer_sub por
 * campo personalizado (categorías de un solo registro). Tras el alta el legacy no da sellos ni puntos:
 * solo sincroniza tb_customer/tb_customer_sub con los POS de tienda (db_sync_all2), fuera de alcance (PosGateway).
 */
class CustomerRegistrationService
{
    public function __construct(private CustomerFormService $form) {}

    /** Alta (act=new). $data: el POST del confirm ya normalizado; $origin: quién da el alta ('admin') */
    public function register(array $data, int $shopCd, string $origin = 'admin'): Customer
    {
        return DB::transaction(function () use ($data, $shopCd) {
            // LOCK TABLE tb_customer IN ACCESS SHARE MODE del legacy: no bloquea nada, createID cuenta filas igual
            $id = $this->createId($shopCd);
            $data['tb_shop_cd'] = $shopCd;

            $customer = new Customer;
            $customer->forceFill($this->columns($data) + [
                'cd' => $this->uniqueCd($shopCd),
                'id' => $id,
                'login_id' => $id,
                'touroku_kubun' => (string) (int) ($data['touroku_kubun'] ?? Customer::MEMBER_KUBUN),
                'disp_flg' => '1',
            ]);
            $customer->save();

            $this->saveCustomFields($customer, $data, $shopCd);

            return $customer;
        });
    }

    /** Edición (act=up): mismas columnas salvo id, login_id, touroku_kubun, touroku_date y disp_flg */
    public function update(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data) {
            $customer->forceFill($this->columns($data));
            // update_date siempre, aunque no cambie nada (el legacy lo ponía en cada UPDATE)
            $customer->setUpdatedAt($customer->freshTimestamp());
            $customer->save();

            $this->saveCustomFields($customer, $data, (int) $customer->tb_shop_cd);

            return $customer;
        });
    }

    /**
     * createID($con, 'tb_customer', $shopCd) de system/inc/function.php: tienda + '1' (alta desde el CRM; el POS usa '2')
     * + nº de filas de tb_customer (todas, también borradas) + 1, con 5 cifras como mínimo.
     */
    public function createId(int $shopCd): string
    {
        $cnt = DB::table('tb_customer')->count() + 1;

        return $shopCd . '1' . ($cnt > 99999 ? (string) $cnt : sprintf('%05s', $cnt));
    }

    /** createHatsuban($shopCd): epoch + tienda en 3 cifras + 6 cifras al azar (cd único entre servidores) */
    public function uniqueCd(int $shopCd): int
    {
        return (int) (time() . sprintf('%03s', $shopCd) . sprintf('%06s', random_int(0, 999999)));
    }

    /** $pw de kihon.php: contraseña inicial de 4 cifras */
    public function randomPassword(): string
    {
        return sprintf('%04d', random_int(0, 9999));
    }

    /** Columnas del UPDATE de customer_new_register.php, con get_form() en cada valor */
    private function columns(array $data): array
    {
        $f = fn (string $key) => LegacyText::form($data[$key] ?? '');
        $intOrNull = fn (string $key) => ($data[$key] ?? '') === '' || ($data[$key] ?? null) === null ? null : (int) $data[$key];
        $date = fn (string $field) => $f($field . '_y') . '/' . $f($field . '_m') . '/' . $f($field . '_d');

        $columns = [
            'tb_shop_cd' => (int) ($data['tb_shop_cd'] ?? 0),
            'kanri_id' => $f('kanri_id'),
            'type' => $f('type'),
            'name' => $f('name'),
            'name_kana' => $f('name_kana'),
            'zip' => $f('zip'),
            'pref' => $f('pref'),
            'add' => $f('add'),
            'add_kana' => $f('add_kana'),
            'add_number' => $f('add_number'),
            'add_build' => $f('add_build'),
            'add_build_kana' => $f('add_build_kana'),
            'email1' => $f('email1'),
            'email2' => $f('email2'),
            'tel' => $f('tel'),
            'fax' => $f('fax'),
            'tb_staff_cd' => $intOrNull('tb_staff_cd'),
        ];

        if (($data['type'] ?? '') === '法人') {
            $columns += [
                'houjin_setsuritsu' => $date('houjin_setsuritsu'),
                'houjin_shihon' => $f('houjin_shihon'),
                'daihyousha_name' => $f('daihyousha_name'),
                'daihyousha_name_kana' => $f('daihyousha_name_kana'),
                'daihyousha_birth' => $date('daihyousha_birth'),
                'daihyousha_sex' => $f('daihyousha_sex'),
                'houjin_gyoushu' => $f('houjin_gyoushu'),
                'houjin_busho' => $f('houjin_busho'),
                'tantou_name' => $f('tantou_name'),
                'tantou_name_kana' => $f('tantou_name_kana'),
                'tantou_tel' => $f('tantou_tel'),
                'tantou_fax' => $f('tantou_fax'),
                'tantou_mail' => $f('tantou_mail'),
            ];
        } else {
            $y = (int) $f('kojin_birth_y');
            $m = (int) $f('kojin_birth_m');
            $d = (int) $f('kojin_birth_d');
            $columns += [
                'kojin_birth' => $date('kojin_birth'),
                // strtotime('Y/m/d') del legacy, en hora de Japón
                'kojin_birth_date' => $y > 0 && checkdate($m, $d, $y) ? Carbon::create($y, $m, $d, 0, 0, 0, 'Asia/Tokyo')->timestamp : null,
                'kojin_sex' => $f('kojin_sex'),
                'kojin_bloodtype' => $f('kojin_bloodtype'),
                'kojin_mobile' => $f('kojin_mobile'),
                'kojin_mail' => $f('kojin_mail'),
                'job' => $f('job'),
                'kinmusaki_name' => $f('kinmusaki_name'),
                'kinmusaki_name_kana' => $f('kinmusaki_name_kana'),
                'kinmusaki_gyoushu' => $f('kinmusaki_gyoushu'),
                'kinmusaki_tel' => $f('kinmusaki_tel'),
                'kinmusaki_fax' => $f('kinmusaki_fax'),
            ];
            if ($this->form->imeiEnabled()) {
                $columns['imei'] = $f('imei');
            }
        }

        $columns['mailmaga_flg'] = $f('mailmaga_flg');
        $columns['mailmaga_email_type'] = $f('mailmaga_email_type');
        // 予約リマインダーメール配信有無: solo si llega el campo
        if (array_key_exists('stop_yoyaku_reminder_mail_flg', $data)) {
            $columns['stop_yoyaku_reminder_mail_flg'] = $f('stop_yoyaku_reminder_mail_flg');
        }
        // error_send_cnt se reinicia al volver a 配信する
        if ($columns['mailmaga_flg'] === '1') {
            $columns['error_send_cnt'] = 0;
        }

        return $columns + [
            'tb_customer_group_cd' => $intOrNull('tb_customer_group_cd'),
            'tb_raiten_douki_master_cd' => $intOrNull('tb_raiten_douki_master_cd'),
            'bikou' => $f('bikou'),
            'login_pass' => $f('login_pass'),
            'synchronize_flg' => '',
            'local_dl_sync_flg' => '',
            'shoukai_tb_customer_cd' => ! empty($data['searchKaiin_cd']) ? (int) $data['searchKaiin_cd'] : null,
        ];
    }

    /**
     * Bloque カスタマイズUPDATE: por cada campo de las categorías de un solo registro, actualiza la fila de
     * tb_customer_sub del cliente (aunque esté borrada, como el legacy) o la crea. Los valores van sin get_form()
     * (createform_sql guardaba el $_REQUEST recortado tal cual).
     */
    private function saveCustomFields(Customer $customer, array $data, int $shopCd): void
    {
        $now = (string) time();

        foreach ($this->form->customCategories() as $category) {
            foreach ($category['forms'] as $form) {
                $values = $this->customFieldValues($form, $data) + [
                    'form_title' => $category['name'] . '<>' . $form['name'],
                    'synchronize_flg' => '',
                    'local_sync_flg' => '',
                    'update_date' => $now,
                ];

                $updated = CustomerSub::query()->withDeleted()
                    ->where('tb_search_form_cd', $form['cd'])->where('tb_customer_cd', $customer->cd)
                    ->toBase()->update($values);

                if ($updated === 0) {
                    DB::table('tb_customer_sub')->insert($values + [
                        'cd' => $this->uniqueCd($shopCd),
                        'tb_customer_cd' => $customer->cd,
                        'tb_search_cate_cd' => $category['cd'],
                        'tb_search_form_cd' => $form['cd'],
                        'form_type' => '1',
                        'touroku_date' => $now,
                        'disp_flg' => '1',
                    ]);
                }
            }
        }
    }

    /** createform_sql(): columna(s) de valor según form_type */
    private function customFieldValues(array $form, array $data): array
    {
        $f = 'form' . $form['cd'];
        $v = fn (string $key) => is_array($data[$key] ?? null) ? '' : LegacyText::trim((string) ($data[$key] ?? ''));
        $pad = fn (string $key) => $v($key) !== '' ? str_pad($v($key), 2, '0', STR_PAD_LEFT) : '';

        switch ($form['form_type']) {
            case 'テキスト': case '半角英数テキスト': case 'メールアドレス': case 'テキストエリア':
            case '画像とテキスト': case 'ラジオボタン': case 'セレクト':
                return ['text_data' => $v($f)];

            case '数値テキスト':
                return ['float_data' => preg_match('/^[0-9.]+$/', $v($f)) && is_numeric($v($f)) ? (float) $v($f) : null];

            case 'チェックボックス':
                $text = '';
                foreach (explode('<>', (string) $form['data']) as $i => $value) {
                    if ($i < (int) $form['flds_row'] && array_key_exists($f . '_' . $i, $data) && $value == $v($f . '_' . $i)) {
                        $text .= $v($f . '_' . $i) . '<>';
                    }
                }

                return ['text_data' => $text];

            case '年':
                return ['text_data' => $v($f . 'yyy')];

            case '年月':
                return ['text_data' => $v($f . 'yyy') . '/' . $pad($f . 'mmm')];

            case '年月日': case '起算日':
                return ['text_data' => $v($f . 'yyy') . '/' . $pad($f . 'mmm') . '/' . $pad($f . 'ddd')];

            case '月日':
                return ['text_data' => $pad($f . 'mmm') . '/' . $pad($f . 'ddd')];

            case 'メニュー項目':
                $menus = $data['yoyaku_menu_cd'] ?? null;

                return ['text_data' => is_array($menus) ? implode('<>', array_keys($menus)) : ''];

            case 'テーブル':
                $rows = [];
                for ($col = 0; $col < (int) $form['flds_col']; $col++) {
                    $cells = [];
                    for ($i = 0; $i < (int) $form['flds_row']; $i++) {
                        $cells[] = $v($f . '_' . $col . '_' . $i);
                    }
                    $rows[] = implode('<>', $cells);
                }

                return ['text_data' => implode('||', $rows)];
        }

        // Tipos sin valor en el alta (画像…): el legacy solo actualizaba form_title y fechas
        return [];
    }
}
