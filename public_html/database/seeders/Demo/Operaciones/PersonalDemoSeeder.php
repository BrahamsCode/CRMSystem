<?php

namespace Database\Seeders\Demo\Operaciones;

use App\Models\Operaciones\BelongPost;
use App\Models\Operaciones\Information;
use App\Models\Operaciones\StaffCard;
use App\Models\Staff;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Datos de personal de 業務支援管理 (el staff y sus permisos los crea NucleoDemoSeeder, S1):
 * - 所属部署 y, en el staff demo (correo @example.com), los campos vacíos de la ficha: 所属, 役職, dirección,
 *   交通費, 給料単価, 有給日数, 備考 (uno con 255 caracteres, el máximo de la columna).
 * - Tarjetas FeliCa (tb_card): tres asignadas (con felica_no en la ficha, para 関連付けを解除する) y dos libres.
 * - スタッフ検索項目設定: más campos en 検索 y CSV.
 * - インフォメーション: generales, por tienda, por cargo, una larga y una en kana de ancho medio; fechas escalonadas.
 */
class PersonalDemoSeeder extends Seeder
{
    public const BELONG_POSTS = ['ホール', 'キッチン', '本部', '受付'];

    /** Campos de スタッフ検索項目設定 que se activan: [tb_koumoku, 検索, CSV] */
    private const SEARCH_FIELDS = [
        ['name_kana', true, true], ['employmentsystem', true, true], ['officialposition', true, true],
        ['entranceday', true, false], ['mtel1', false, true],
    ];

    /**
     * インフォメーション (la pantalla solo usa `comment`, de una línea): [texto, tienda (null = 全店舗,
     * n = n-ésima tienda activa), 役職, 所属, días atrás]. La lista enseña las 10 últimas: hay 12.
     */
    private const NOTICES = [
        ['来月分の希望シフトは毎月20日までに店長へ提出してください。', null, '', '', 1],
        ['12月31日は18時閉店、1月1日は休業です。年始は1月2日10時から通常営業です。', null, '', '', 3],
        ['出勤時の手洗い・検温を徹底してください。体調不良の場合は必ず事前に連絡をお願いします。', null, '', '', 6],
        ['来週月曜日14時から本部会議室で店長会議を行います。売上報告の資料を持参してください。', null, '主任 部長', '', 9],
        ['水曜日の定休日にフライヤーと冷蔵庫の点検が入ります。前日の閉店後に清掃をお願いします。', null, '', 'キッチン', 12],
        ['レジ締めは2名で行い、差額がある場合は店長に報告してください。', 1, '', '', 15],
        ['従業員の自転車は裏口の駐輪場に停めてください。正面入口前は駐輪禁止です。', 2, '', '', 18],
        ['11月の週末は観光客が増えるため、他店からの応援スタッフが入ります。', 3, '', '', 21],
        ['新しく入ったスタッフ向けの研修を行います。対象者には個別に連絡します。', null, '係員', 'ホール', 25],
        ['【重要】年末調整の書類（扶養控除等申告書・保険料控除申告書・住宅借入金等特別控除申告書）を配布しました。記入例を確認のうえ、12月10日までに本部総務へ提出してください。提出がない場合はご自身で確定申告が必要になります。不明点は総務担当まで内線でお問い合わせください。', null, '', '', 30],
        ['ﾊﾝｶｸ ｶﾅ ﾉ ﾋｮｳｼﾞ ｶｸﾆﾝﾖｳ ﾉ ｵｼﾗｾ ﾃﾞｽ。', null, '', '', 40],
        ['夏季休暇は7月から9月の間で3日間取得できます。希望日を申請してください。', null, '', '', 120],
    ];

    private Generator $faker;

    public function run(): void
    {
        $this->faker = Factory::create('ja_JP');
        $this->faker->seed(20261127);

        DB::transaction(function () {
            foreach (self::BELONG_POSTS as $name) {
                BelongPost::findOrCreateByName($name);
            }
            $this->staffDetails();
            $this->cards();
            $this->searchFields();
            $this->notices();
        });
    }

    /** Staff demo de S1 (correo @example.com); nunca el master ni el del backup */
    public static function demoStaff(): Collection
    {
        return Staff::withDeleted()->where('email1', 'like', '%@example.com')
            ->where('login_id', '!=', Staff::MASTER_LOGIN_ID)->orderBy('cd')->get();
    }

    /** Rellena solo los campos vacíos de la ficha */
    private function staffDetails(): void
    {
        foreach (self::demoStaff() as $i => $staff) {
            $isManager = str_starts_with((string) $staff->login_id, 'tencho_');
            $hourly = in_array($staff->employmentsystem, ['アルバイト', 'パート'], true);
            $values = [
                'belongpost' => $isManager ? '本部' : self::BELONG_POSTS[$i % 2],
                'officialposition' => $isManager ? '主任' : '係員',
                'bloodtype' => ['A', 'B', 'O', 'AB'][$i % 4],
                'zip' => $this->faker->postcode(),
                'pref' => '大阪府',
                'add' => $this->faker->city(),
                'add_number' => $this->faker->streetAddress(),
                'salary' => $hourly ? '1100' : '250000',
                'paidholiday' => (string) (10 + $i % 5),
                'near_station2' => ['梅田', '難波', '京都', '天王寺'][$i % 4],
                'traf_transport' => '電車',
                'traf_section1_1' => ['梅田', '難波', '京都', '天王寺'][$i % 4],
                'traf_section1_2' => '本町',
                'traf_charge' => (string) (240 + 20 * ($i % 6)),
                'traf_regularfee' => '',
                'remarks' => $i === 0 ? $this->longRemarks() : '',
            ];
            $missing = array_filter($values, fn ($value, $column) => $value !== '' && (string) $staff->{$column} === '', ARRAY_FILTER_USE_BOTH);
            if ($missing !== []) {
                DB::table('tb_staff')->where('cd', $staff->cd)->update($missing);
            }
        }
    }

    /** 備考 con exactamente 255 caracteres (varchar(255)) */
    private function longRemarks(): string
    {
        $text = str_repeat('土日祝の勤務可能。資格：調理師免許、食品衛生責任者。長期休暇の希望あり。', 10);

        return mb_substr($text, 0, 255);
    }

    /** Tres tarjetas asignadas al primer staff demo activo sin tarjeta y dos libres; se reconocen por el número */
    private function cards(): void
    {
        $assignable = self::demoStaff()->where('disp_flg', Staff::ACTIVE_FLAG)->filter(fn (Staff $s) => (string) $s->felica_no === '')->values();

        foreach (range(1, 5) as $n) {
            $felicaNo = sprintf('0114DE%010d', $n);
            if (StaffCard::query()->where('felica_no', $felicaNo)->exists()) {
                continue;
            }
            $staff = $n <= 3 ? $assignable->shift() : null;
            StaffCard::create([
                'card_no' => sprintf('%04d', $n),
                'felica_no' => $felicaNo,
                'staff_cd' => $staff ? (string) $staff->cd : '',
                'card_flg' => $staff ? StaffCard::ASSIGNED : StaffCard::UNASSIGNED,
            ]);
            if ($staff) {
                DB::table('tb_staff')->where('cd', $staff->cd)->update(['felica_no' => $felicaNo]);
            }
        }
    }

    private function searchFields(): void
    {
        foreach (self::SEARCH_FIELDS as [$field, $search, $csv]) {
            $row = DB::table('tb_koumoku')->where('tb_name', 'tb_staff')->where('tb_koumoku', $field);
            if ($search) {
                (clone $row)->where(fn ($q) => $q->whereNull('mail_mag_select')->orWhere('mail_mag_select', ''))->update(['mail_mag_select' => '1']);
            }
            if ($csv) {
                (clone $row)->where(fn ($q) => $q->whereNull('koumoku_select')->orWhere('koumoku_select', ''))->update(['koumoku_select' => '1']);
            }
        }
    }

    /** インフォメーション; se reconocen por el texto */
    private function notices(): void
    {
        $shops = DB::table('tb_shop')->where('disp_flg', '1')->orderBy('cd')->pluck('cd')->values();

        foreach (self::NOTICES as [$comment, $shopIndex, $positions, $belong, $daysAgo]) {
            if (Information::query()->where('comment', $comment)->exists()) {
                continue;
            }
            Information::withoutTimestamps(fn () => Information::create([
                'shop_cd' => $shopIndex === null ? null : ($shops[$shopIndex] ?? $shops->first()),
                'comment' => $comment,
                'belongpost' => $belong,
                'employmentsystem' => '',
                'officialposition' => $positions,
                'staff_name' => '店長',
                'update' => (string) now()->subDays($daysAgo)->getTimestamp(),
                'disp_flg' => '1',
            ]));
        }
    }
}
