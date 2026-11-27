<?php

namespace App\Services\Reservations;

use App\Models\AdminMaster;
use App\Models\Reservations\ReservationConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 予約設定 (category9/yoyaku/yoyaku_*_config.php): lectura y guardado de las cuatro pantallas.
 *
 * - 各店舗設定: `tb_yoyaku_config` (una fila por tienda, `cd` con hatuban al crearla).
 * - 予約フォーム設定: `tb_koumoku` (`system_cate = '9'`, `tb_name = 'tb_yoyaku'`).
 * - 機能設定: `admin_master` (cd = 1): `yoyaku_time_interval`, `meyasu_jikan_flg`.
 * - 注釈設定: `tb_manage_words` (`content_name = 'mobile_yoyaku[<項目>]'`).
 */
class ReservationSettingService
{
    /** Intervalos permitidos del calendario de reservas (yoyaku_kinou_config.php; 40 = personalización 2013) */
    public const TIME_INTERVALS = [5, 10, 15, 20, 30, 40, 60];

    public const DEFAULT_TIME_INTERVAL = 30;

    /** Campos básicos del formulario de reserva (tb_koumoku.tb_koumoku) */
    public const BASIC_FORM_FIELDS = ['name', 'name_kana', 'mail', 'tel'];

    /**
     * Notas (注釈) del formulario de reserva: cd de tb_manage_words => etiqueta.
     * El `content_name` es 'mobile_yoyaku[<etiqueta>]', como en el legacy.
     */
    public const COMMENT_FIELDS = [
        9 => '店舗選択_注釈_上',
        10 => '店舗選択_注釈_下',
        11 => 'スタッフ選択_注釈_上',
        12 => 'スタッフ選択_注釈_下',
        13 => 'メニュー選択_注釈_上',
        14 => 'メニュー選択_注釈_下',
        15 => '日付選択_注釈_上',
        16 => '日付選択_注釈_下',
        17 => '時間選択_注釈_上',
        18 => '時間選択_注釈_下',
        19 => '顧客情報入力_注釈_上',
        20 => '顧客情報入力_注釈_下',
        // 2016.11.04 V2予約の注釈
        26 => 'V2予約のスタッフ選択箇所',
        27 => 'V2予約のメニュー選択箇所',
        28 => 'V2予約の店舗選択箇所',
        29 => 'V2予約の日時選択箇所',
        30 => 'V2予約の顧客情報入力箇所',
    ];

    // ------------------------------------------------------------------ 各店舗設定

    /** Datos de la tienda en el formato de ajax_yoyaku_config.php (todo texto; '' si no hay fila) */
    public function shopConfigData(int $shopCd): array
    {
        $config = ReservationConfig::query()->where('tb_shop_cd', $shopCd)->first();

        $keys = ['start_time', 'end_time', 'shimekiri', 'shimekiri_time', 'automail', 'automail_time',
            'uketuke_type', 'timeline_sort_type', 'setsubi_capacity'];

        return collect($keys)->mapWithKeys(fn ($key) => [$key => (string) ($config?->getAttribute($key) ?? '')])->all();
    }

    /**
     * Guarda la configuración de la tienda (crea la fila con hatuban si no existe), como yoyaku_config.php.
     *
     * `$input`: start_time1/2, end_time1/2, shimekiri, shimekiri_time, uketuke_type, timeline_sort_type,
     * automail, automail_time1/2, setsubi_capacity.
     */
    public function saveShopConfig(int $shopCd, array $input): ReservationConfig
    {
        return DB::transaction(function () use ($shopCd, $input) {
            $config = ReservationConfig::query()->where('tb_shop_cd', $shopCd)->first()
                ?? new ReservationConfig(['tb_shop_cd' => $shopCd]);

            $text = fn (string $key) => (string) ($input[$key] ?? '');
            $capacity = (int) ($input['setsubi_capacity'] ?? 0);

            $config->fill([
                'tb_shop_cd' => $shopCd,
                'start_time' => $text('start_time1') . ':' . $text('start_time2'),
                'end_time' => $text('end_time1') . ':' . $text('end_time2'),
                'shimekiri' => $text('shimekiri'),
                'shimekiri_time' => $text('shimekiri_time'),
                'uketuke_type' => $text('uketuke_type'),
                'timeline_sort_type' => $text('timeline_sort_type'),
                // La リマインダーメール está oculta en el legacy desde 2013 (ahora リマインダーメール管理):
                // estos campos llegan vacíos y se guardan como el legacy ('' y ':').
                'automail' => $text('automail'),
                'automail_time' => $text('automail_time1') . ':' . $text('automail_time2'),
                'setsubi_capacity' => $capacity > 0 ? $capacity : null,
            ]);
            $config->save();

            // mailmag_master (type_flg = 'yoyaku') + cSendSchedule: el legacy lo dejó comentado en 2013.03.14
            // al pasar los recordatorios a リマインダーメール管理. No se reproduce.
            // TODO(S1): modelo MailmagMaster, si algún día vuelve.

            return $config;
        });
    }

    // ------------------------------------------------------------------ 予約フォーム設定

    /** Campos básicos (name, name_kana, mail, tel), orden cd */
    public function basicFormFields(): Collection
    {
        // TODO(S1): modelo Koumoku (tb_koumoku)
        return $this->reservationKoumoku()
            ->whereIn('tb_koumoku', self::BASIC_FORM_FIELDS)
            ->orderBy('cd')
            ->get();
    }

    /** Campos libres (free_koumoku_*), orden rank */
    public function freeFormFields(): Collection
    {
        return $this->reservationKoumoku()
            ->where('tb_koumoku', 'like', 'free_koumoku_%')
            ->orderBy('rank')
            ->get();
    }

    /**
     * Guarda yoyaku_form_config.php. 'name' siempre queda 入力/必須/CSV = '1'.
     * Los demás básicos: koumoku_disp_<campo>, koumoku_check_<campo>, koumoku_select_<campo> ('1' o '').
     * Libres: free_koumoku_cd[] y free_koumoku_{name,disp,check,select}_<cd>.
     */
    public function saveFormFields(array $input): void
    {
        DB::transaction(function () use ($input) {
            foreach (self::BASIC_FORM_FIELDS as $field) {
                $values = $field === 'name'
                    ? ['koumoku_disp' => '1', 'koumoku_check' => '1', 'koumoku_select' => '1']
                    : [
                        'koumoku_disp' => (string) ($input['koumoku_disp_' . $field] ?? ''),
                        'koumoku_check' => (string) ($input['koumoku_check_' . $field] ?? ''),
                        'koumoku_select' => (string) ($input['koumoku_select_' . $field] ?? ''),
                    ];
                $this->reservationKoumoku()->where('tb_koumoku', $field)->update($values);
            }

            foreach ((array) ($input['free_koumoku_cd'] ?? []) as $cd) {
                $cd = (int) $cd;
                // El legacy actualizaba por cd sin más filtro; aquí solo los campos libres de 予約
                $this->reservationKoumoku()
                    ->where('cd', $cd)
                    ->where('tb_koumoku', 'like', 'free_koumoku_%')
                    ->update([
                        'tb_koumoku_name' => (string) ($input['free_koumoku_name_' . $cd] ?? ''),
                        'koumoku_disp' => (string) ($input['free_koumoku_disp_' . $cd] ?? ''),
                        'koumoku_check' => (string) ($input['free_koumoku_check_' . $cd] ?? ''),
                        'koumoku_select' => (string) ($input['free_koumoku_select_' . $cd] ?? ''),
                    ]);
            }
        });
    }

    // ------------------------------------------------------------------ 機能設定

    /** Intervalo actual (30 si está vacío o fuera de la lista, como el legacy) */
    public function timeInterval(AdminMaster $adminMaster): int
    {
        $interval = (int) $adminMaster->yoyaku_time_interval;

        return in_array($interval, self::TIME_INTERVALS, true) ? $interval : self::DEFAULT_TIME_INTERVAL;
    }

    /** Guarda yoyaku_kinou_config.php (UPDATE admin_master WHERE cd = 1; sin fila no hace nada, como el legacy) */
    public function saveFeatures(string $timeInterval, string $meyasuJikanFlg): void
    {
        $adminMaster = AdminMaster::current();
        if (! $adminMaster->exists) {
            return;
        }

        $adminMaster->forceFill([
            'yoyaku_time_interval' => $timeInterval,
            'meyasu_jikan_flg' => $meyasuJikanFlg,
        ])->save();
    }

    // ------------------------------------------------------------------ 注釈設定

    public static function commentContentName(int $cd): string
    {
        return 'mobile_yoyaku[' . self::COMMENT_FIELDS[$cd] . ']';
    }

    /** Texto actual de cada nota: cd => naiyou ('' si no hay fila) */
    public function comments(): array
    {
        // TODO(S1): modelo ManageWord (tb_manage_words)
        $names = collect(self::COMMENT_FIELDS)->keys()->mapWithKeys(fn ($cd) => [self::commentContentName($cd) => $cd]);

        $rows = DB::table('tb_manage_words')->whereIn('content_name', $names->keys())->orderBy('cd')->get();

        $data = array_fill_keys(array_keys(self::COMMENT_FIELDS), '');
        foreach ($rows as $row) {
            $data[$names[$row->content_name]] = (string) $row->naiyou;
        }

        return $data;
    }

    /** Guarda las notas `comment_<cd>` recibidas (también vacías); crea la fila si falta, como el legacy */
    public function saveComments(array $input): void
    {
        DB::transaction(function () use ($input) {
            $now = (string) now()->getTimestamp();

            foreach (self::COMMENT_FIELDS as $cd => $label) {
                if (! array_key_exists('comment_' . $cd, $input)) {
                    continue;
                }
                $contentName = self::commentContentName($cd);
                $words = DB::table('tb_manage_words')->where('content_name', $contentName);

                if (! $words->clone()->exists()) {
                    DB::table('tb_manage_words')->insert([
                        'cd' => $cd, 'content_name' => $contentName, 'hyouji_flg' => '1', 'touroku_date' => $now,
                    ]);
                }

                $words->update(['naiyou' => (string) ($input['comment_' . $cd] ?? ''), 'update_date' => $now]);
            }
        });
    }

    private function reservationKoumoku()
    {
        return DB::table('tb_koumoku')->where('system_cate', '9')->where('tb_name', 'tb_yoyaku');
    }
}
