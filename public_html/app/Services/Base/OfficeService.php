<?php

namespace App\Services\Base;

use App\Models\Office;
use App\Support\LegacyCheck;
use App\Support\LegacyText;
use Illuminate\Support\Facades\DB;

/**
 * 基本情報確認・変更 (category0/config/config*.php) y 基本情報登録 de primer acceso (system/config*.php):
 * empresa en `tb_office` cd=1 y datos del usuario master en `tb_staff` cd=1.
 */
class OfficeService
{
    public const FIELDS = [
        'kaishamei_kana', 'kaishamei', 'daihyoushamei_kana', 'daihyoushamei', 'toukibi', 'gyoushu', 'zip', 'pref',
        'add_kana', 'add', 'add_number', 'add_build_kana', 'add_build', 'tel', 'fax', 'kyuyo_marume',
        'start_work_time1', 'start_work_time2', 'break_time', 'hp_hyouji_name', 'hp_hyouji_tel', 'shop_url', 'email',
        'tantou', 'tantou_tel', 'tantou_fax', 'tantou_email', 'pass',
    ];

    /** Opciones de 業種 (make_houjin_gyoushu_options de inc/kihon.php) */
    public const GYOUSHU = [
        '農業', '林業', '漁業', '鉱業', '採石業', '砂利採取業', '建設業', '製造業', '電気・ガス・熱供給・水道業', '情報通信業',
        '運輸業', '郵便業', '卸売業', '小売業', '金融業', '保険業', '不動産業', '物品賃貸業', '学術研究', '専門・技術サービス業', '宿泊業',
        '飲食サービス業', '生活関連サービス業', '娯楽業', '教育', '学習支援業', '医療', '福祉', '他の営利事業', 'その他（政治・経済・文化・宗教団体など）',
    ];

    /** Datos que llegan del formulario (config_confirm.php: get_form en todo y teléfonos sin guiones) */
    public function normalize(array $input): array
    {
        $data = [];
        foreach (self::FIELDS as $field) {
            $data[$field] = LegacyText::form($input[$field] ?? '');
        }
        foreach (['tel', 'fax', 'tantou_tel', 'tantou_fax'] as $field) {
            $data[$field] = (string) LegacyCheck::replaceTel($data[$field]);
        }

        return $data;
    }

    /** Datos actuales de tb_office para el formulario */
    public function current(): array
    {
        $office = Office::current()->getAttributes();
        $start = explode('<>', (string) ($office['start_work_time'] ?? ''));

        return $office + ['start_work_time1' => $start[0] ?? '', 'start_work_time2' => $start[1] ?? ''];
    }

    public function check(array $data): LegacyCheck
    {
        return (new LegacyCheck)
            ->katakana('kaishamei_kana', $data['kaishamei_kana'])
            ->blank('kaishamei', $data['kaishamei'])
            ->katakana('daihyoushamei_kana', $data['daihyoushamei_kana'])
            ->date('toukibi', $data['toukibi'])
            ->zip('zip', $data['zip'])
            ->katakana('add_kana', $data['add_kana'])
            ->katakanaAlnum('add_build_kana', $data['add_build_kana'])
            ->num('tel', $data['tel'])
            ->num('fax', $data['fax'])
            ->url('shop_url', $data['shop_url'])
            ->mailRequired('email', $data['email'])
            ->blank('tantou', $data['tantou'])
            ->num('tantou_tel', $data['tantou_tel'])
            ->num('tantou_fax', $data['tantou_fax'])
            ->mailRequired('tantou_email', $data['tantou_email'])
            ->alnumRequired('pass', $data['pass']);
    }

    /**
     * config_register.php. En 基本情報確認・変更 la パスワード solo se guarda en tb_office.pass: el legacy la copiaba
     * además a la clave del usuario master (trampa MANUAL-TESTS #123) y no se replica. En el primer acceso
     * (system/config_register.php) sí es la clave de master, y se crea la tienda 1 con los datos de la empresa.
     */
    public function save(array $input, bool $setup): void
    {
        $d = array_map(fn ($v) => is_scalar($v) ? (string) $v : '', array_intersect_key($input, array_flip(self::FIELDS)) + array_fill_keys(self::FIELDS, ''));
        $now = (string) time();
        $startWorkTime = $d['start_work_time1'] . '<>' . $d['start_work_time2'];

        DB::transaction(function () use ($d, $now, $startWorkTime, $setup) {
            $office = [
                'kaishamei' => $d['kaishamei'], 'kaishamei_kana' => $d['kaishamei_kana'],
                'daihyoushamei' => $d['daihyoushamei'], 'daihyoushamei_kana' => $d['daihyoushamei_kana'],
                'toukibi' => $d['toukibi'], 'gyoushu' => $d['gyoushu'], 'zip' => $d['zip'], 'pref' => $d['pref'],
                'add' => $d['add'], 'add_kana' => $d['add_kana'], 'add_number' => $d['add_number'],
                'add_build' => $d['add_build'], 'add_build_kana' => $d['add_build_kana'], 'tel' => $d['tel'], 'fax' => $d['fax'],
                'shop_url' => $d['shop_url'], 'email' => $d['email'], 'tantou' => $d['tantou'], 'tantou_tel' => $d['tantou_tel'],
                'tantou_fax' => $d['tantou_fax'], 'tantou_email' => $d['tantou_email'], 'id' => 'master', 'pass' => $d['pass'],
                'kyuyo_marume' => $d['kyuyo_marume'], 'start_work_time' => $startWorkTime, 'break_time' => $d['break_time'],
                'touroku_date' => $now, 'update_date' => $now,
            ];
            if (! $setup) {
                $office += ['hp_hyouji_name' => $d['hp_hyouji_name'], 'hp_hyouji_tel' => $d['hp_hyouji_tel']];
            }
            DB::table('tb_office')->updateOrInsert(['cd' => Office::ROW_CD], $office);

            if ($setup) {
                DB::table('tb_shop')->updateOrInsert(['cd' => ShopService::HEAD_SHOP_CD], [
                    'kaishamei' => $d['kaishamei'], 'kaishamei_kana' => $d['kaishamei_kana'], 'zip' => $d['zip'], 'pref' => $d['pref'],
                    'add_kana' => $d['add_kana'], 'add' => $d['add'], 'add_number' => $d['add_number'], 'add_build' => $d['add_build'],
                    'add_build_kana' => $d['add_build_kana'], 'tel' => $d['tel'], 'fax' => $d['fax'], 'shop_url' => $d['shop_url'],
                    'email' => $d['email'], 'tantou' => $d['tantou'], 'kyuyo_marume' => $d['kyuyo_marume'],
                    'start_work_time' => $startWorkTime, 'break_time' => $d['break_time'], 'disp_flg' => '1',
                    'touroku_date' => $now, 'update_date' => $now,
                ]);
            }

            $staff = [
                'office_cd' => 1, 'shop_cd' => 1, 'id' => '000001', 'name' => $d['tantou'], 'email1' => $d['tantou_email'],
                'rank_pass' => str_repeat('4,4,4,4,4,4,4,4,4,4<>', 6), 'disp_flg' => '1', 'login_id' => 'master',
            ];
            if ($setup) {
                $staff['login_pass'] = $d['pass'];
            }
            DB::table('tb_staff')->where('cd', 1)->update($staff);
        });
    }
}
