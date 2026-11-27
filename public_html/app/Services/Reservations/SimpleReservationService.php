<?php

namespace App\Services\Reservations;

use App\Models\Reservations\SimpleReservation;
use App\Models\Reservations\SimpleReservationCategory;
use App\Models\Shop;
use App\Support\LegacyText;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Reserva simple de category3/reservation (`tb_reserve` + categorías `tb_reserve_name`):
 * alta en pasos (reserve_new*.php), lista del día (reserve_list.php), detalle, cambio de estado y 予約項目設定.
 *
 * Texto: el formulario trabaja con el texto tal cual (recortado) y se imprime con `{{ }}`. Al guardar se reproduce la
 * cadena del legacy (confirm: get_form2 → register: get_form), así que `tb_reserve` queda con entidades HTML
 * (`&amp;`, `&lt;`…) como en producción: las pantallas que leen la tabla la imprimen con `{!! !!}`.
 */
class SimpleReservationService
{
    /** Campos del formulario que viajan entre los pasos (lo que el legacy reenviaba con «POSTデータを全て返す») */
    public const FORM_FIELDS = ['kaiin_cd', 'tb_shop_cd', 'name', 'tel', 'email', 'hiduke', 'hour1', 'min1', 'ninsuu', 'category_cd', 'staff_cd', 'act', 'reserve_cd', 'mitouroku'];

    /** Mensajes de reserve_change_status.php por acción */
    public const STATUS_MESSAGES = ['del' => '予約取り消し', 'kakutei' => '予約確定', 'visit' => '来店処理'];

    /** Mensajes de reserve_delete.php por acción (sin correo) */
    public const DELETE_MESSAGES = ['del' => '取り消し', 'permanent' => '本予約処理'];

    // ---------------------------------------------------------------- datos del formulario

    /** Tiendas del selector: todas las no borradas (master) o solo la de la sesión */
    public function shops(bool $isMaster, ?int $sessionShopCd): Collection
    {
        return Shop::query()
            ->when(! $isMaster, fn ($q) => $q->where('cd', (int) $sessionShopCd))
            ->orderBy('cd')
            ->get(['cd', 'kaishamei']);
    }

    /**
     * 予約内容 de una tienda: categorías activas de la tienda y las de 全店舗 (`tb_shop_cd` NULL; ajax_category.php
     * preguntaba por `= 0`, que no guarda nadie: se unifica con reserve_new.php).
     */
    public function categories(?int $shopCd): Collection
    {
        if (! $shopCd) {
            return collect();
        }

        return SimpleReservationCategory::query()
            ->where('disp_flg', '1')
            ->where(fn ($q) => $q->whereNull('tb_shop_cd')->orWhere('tb_shop_cd', 0)->orWhere('tb_shop_cd', $shopCd))
            ->orderBy('cd')
            ->get(['cd', 'reserve_cate_name']);
    }

    /** 指名: personal activo con `reserve_flg = '1'` de la tienda (ajax_staff.php) */
    public function nominationStaff(?int $shopCd): Collection
    {
        if (! $shopCd) {
            return collect();
        }

        return DB::table('tb_staff')
            ->where('disp_flg', '1')->where('reserve_flg', '1')->where('shop_cd', $shopCd)
            ->orderBy('cd')
            ->get(['cd', 'name']);
    }

    /** `koumoku_disp` de tb_koumoku (system_cate '3'): 'reserve_people' (人数) o 'reserve_nomination' (指名) */
    public function koumokuDisp(string $koumoku): string
    {
        return (string) DB::table('tb_koumoku')->where('system_cate', '3')->where('tb_koumoku', $koumoku)->value('koumoku_disp');
    }

    /** Socio (tb_customer, sin filtrar borrados como el legacy) */
    public function customer(mixed $kaiinCd): ?object
    {
        if (! is_numeric($kaiinCd) || (int) $kaiinCd <= 0) {
            return null;
        }

        return DB::table('tb_customer')->where('cd', (int) $kaiinCd)->first(['cd', 'id', 'kanri_id', 'name', 'email1', 'tel', 'kojin_mobile', 'login_id']);
    }

    /**
     * Datos de reserve_new.php: los enviados (recortados) o, con act=up, los de la reserva guardada.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    public function formData(array $input, bool $back): array
    {
        $data = [];
        foreach (self::FORM_FIELDS as $field) {
            $data[$field] = is_scalar($input[$field] ?? null) ? LegacyText::trim((string) $input[$field]) : '';
        }

        if ($data['act'] === 'up' && ! $back && ($reservation = SimpleReservation::query()->find((int) $data['reserve_cd']))) {
            if ((string) $reservation->user_cd !== '') {
                $data['kaiin_cd'] = (string) $reservation->user_cd;
            }
            // En la BD el texto lleva entidades (get_form2): se devuelve al texto original para el formulario
            $data['name'] = self::decode($reservation->user_name);
            $data['email'] = self::decode($reservation->user_mail);
            $data['tel'] = self::decode($reservation->user_tel);
            $data['tb_shop_cd'] = (string) $reservation->shop_cd;
            $data['staff_cd'] = (string) $reservation->reserve_nomination;
            $data['ninsuu'] = self::decode($reservation->reserve_people);
            $data['category_cd'] = (string) $reservation->reserve_cate;
            $at = $reservation->reservedAt();
            $data['hiduke'] = $at?->format('Y/m/d') ?? '';
            $data['hour1'] = $at?->format('H') ?? '';
            $data['min1'] = $at?->format('i') ?? '';
        }

        return $data;
    }

    /**
     * reserve_new_confirm.php: comprobaciones con los mismos textos. Devuelve [campo => mensaje]; el `tel` sin guiones
     * (lo escrito queda en `tel_display`) y el `hiduke` vaciado si no es válido quedan en `$data`, como el legacy.
     *
     * @param  array<string, string>  $data
     * @return array<string, string>
     */
    public function validate(array &$data): array
    {
        $errors = [];
        if ($data['tb_shop_cd'] === '') {
            $errors['shopcd'] = '※必ず選択して下さい。';
        }
        if ($data['name'] === '') {
            $errors['name'] = '※必須入力項目です。';
        }
        if ($data['tel'] === '') {
            $errors['tel'] = '※必須入力項目です。';
        } else {
            // Se muestra lo escrito, pero al registro viaja sin guiones (el legacy cambiaba $_POST['tel'])
            $data['tel_display'] = $data['tel'];
            $data['tel'] = str_replace(['-', 'ー', '*', '/'], '', $data['tel']);
            if (! preg_match('/^[0-9]+$/', $data['tel'])) {
                $errors['tel'] = ''; // el legacy solo marcaba el fondo, sin texto
            }
        }
        if ($data['email'] === '') {
            $errors['email'] = '※必須入力項目です。';
        } elseif (! preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+([\.][a-z0-9-]+)+$/i', $data['email'])) {
            $errors['email'] = '※不適切な形式です。';
        }
        if ($data['hiduke'] === '') {
            $errors['hiduke'] = '※必須入力項目です。';
        } elseif ($this->parseDate($data['hiduke']) === null) {
            $errors['hiduke'] = '※' . $data['hiduke'] . 'は不適切な形式です。';
            $data['hiduke'] = '';
        }
        if ($data['ninsuu'] !== '' && ! preg_match('/^[0-9]+$/', $data['ninsuu'])) {
            $errors['ninsuu'] = '※半角数字で入力して下さい。';
        }

        return $errors;
    }

    /**
     * reserve_new_register.php: alta (act=new, cd con hatuban) o cambio (act=up) con `reserve_flg = '2'`.
     *
     * @param  array<string, string>  $data  texto tal cual (recortado)
     */
    public function save(array $data): SimpleReservation
    {
        $at = $this->parseDate($data['hiduke']);
        $time = $at?->setTime((int) $data['hour1'], (int) $data['min1'])->getTimestamp();
        $store = fn (string $key) => LegacyText::form(LegacyText::input($data[$key] ?? ''));

        $values = [
            'reserve_time' => $time !== null ? (string) $time : '',
            'reserve_cate' => $store('category_cd'),
            'reserve_people' => $store('ninsuu'),
            'reserve_nomination' => $store('staff_cd'),
            'user_cd' => $store('kaiin_cd'),
            'user_name' => $store('name'),
            'user_mail' => $store('email'),
            'user_tel' => $store('tel'),
            'shop_cd' => $store('tb_shop_cd'),
            'reserve_flg' => SimpleReservation::FLG_CONFIRMED,
        ];

        return DB::transaction(function () use ($data, $values) {
            if ($data['act'] === 'up') {
                $reservation = SimpleReservation::query()->findOrFail((int) $data['reserve_cd']);
                $reservation->fill($values)->save();

                return $reservation;
            }

            return SimpleReservation::query()->create($values);
        });
    }

    /** reserve_change_status.php / reserve_delete.php: del → '9', kakutei|permanent → '2', visit → raiten_flg '1' */
    public function changeStatus(SimpleReservation $reservation, string $act): void
    {
        match ($act) {
            'del' => $reservation->reserve_flg = SimpleReservation::FLG_CANCELED,
            'kakutei', 'permanent' => $reservation->reserve_flg = SimpleReservation::FLG_CONFIRMED,
            'visit' => $reservation->raiten_flg = '1',
        };
        $reservation->save();
    }

    // ---------------------------------------------------------------- lectura

    /**
     * Datos de una reserva para complete / detail / correo: tienda, socio, 指名 y 予約内容.
     *
     * @return array{customer: ?object, shopName: ?string, staff: ?object, categoryName: ?string}
     */
    public function related(SimpleReservation $reservation): array
    {
        return [
            'customer' => $this->customer($reservation->user_cd),
            'shopName' => is_numeric($reservation->shop_cd) ? Shop::withDeleted()->whereKey((int) $reservation->shop_cd)->value('kaishamei') : null,
            'staff' => is_numeric($reservation->reserve_nomination)
                ? DB::table('tb_staff')->where('cd', (int) $reservation->reserve_nomination)->first(['cd', 'name', 'email1'])
                : null,
            'categoryName' => is_numeric($reservation->reserve_cate)
                ? SimpleReservationCategory::withDeleted()->whereKey((int) $reservation->reserve_cate)->value('reserve_cate_name')
                : null,
        ];
    }

    /**
     * reserve_list.php: reservas de una tienda (o todas) en un día ('Y/n/j'), ORDER BY reserve_time DESC.
     *
     * @return array{total: int, rows: Collection, date: ?Carbon}
     */
    public function dayList(?string $shopCd, ?string $dd, int $page, int $kensu): array
    {
        $date = $dd !== null && $dd !== '' ? $this->parseDate($dd) : null;
        $epoch = "CASE WHEN reserve_time ~ '^[0-9]+$' THEN CAST(reserve_time AS BIGINT) END";
        $query = SimpleReservation::query()
            ->when($shopCd !== null && $shopCd !== '', fn ($q) => $q->where('shop_cd', $shopCd))
            ->when($date !== null, fn ($q) => $q->whereRaw("{$epoch} BETWEEN ? AND ?", [$date->copy()->startOfDay()->getTimestamp(), $date->copy()->endOfDay()->getTimestamp()]))
            // dd no válido: el legacy comparaba con strtotime() = false (ninguna fila)
            ->when($dd !== null && $dd !== '' && $date === null, fn ($q) => $q->whereRaw('1 = 0'));

        $total = (clone $query)->count();
        $rows = $query->orderByRaw("{$epoch} DESC NULLS LAST")->orderByDesc('cd')
            ->offset(max(0, ($page - 1) * $kensu))->limit($kensu)->get();

        $customerCds = $rows->pluck('user_cd')->filter(fn ($cd) => is_numeric($cd))->map(fn ($cd) => (int) $cd)->unique()->values();
        $customers = $customerCds->isEmpty() ? collect() : DB::table('tb_customer')->whereIn('cd', $customerCds)->pluck('id', 'cd');
        $rows->each(function (SimpleReservation $row) use ($customers) {
            $row->setAttribute('customer_id', is_numeric($row->user_cd) && $customers->has((int) $row->user_cd) ? (string) $customers[(int) $row->user_cd] : null);
        });

        return ['total' => $total, 'rows' => $rows, 'date' => $date];
    }

    // ---------------------------------------------------------------- 予約項目設定 (tb_koumoku, tb_name = 'tb_reserve')

    public function configItems(): Collection
    {
        return DB::table('tb_koumoku')->where('tb_name', 'tb_reserve')->orderBy('cd')->get();
    }

    /**
     * config_register.php: para cada fila (item_cd<n>) guarda koumoku_disp / koumoku_check de las casillas
     * item_cd1_<n> / item_cd2_<n>; mail_mag_select y koumoku_select quedan como envía el formulario (vacíos).
     */
    public function updateConfig(array $input): void
    {
        $max = (int) ($input['item_max_no'] ?? 0);
        DB::transaction(function () use ($input, $max) {
            for ($n = 1; $n <= $max; $n++) {
                $value = fn (string $key) => is_scalar($input[$key] ?? null) ? (string) $input[$key] : '';
                DB::table('tb_koumoku')->where('tb_name', 'tb_reserve')->where('cd', (int) $value('item_cd' . $n))->update([
                    'koumoku_disp' => $value('item_cd1_' . $n),
                    'koumoku_check' => $value('item_cd2_' . $n),
                    'mail_mag_select' => $value('item_cd3_' . $n),
                    'koumoku_select' => $value('item_cd4_' . $n),
                ]);
            }
        });
    }

    // ---------------------------------------------------------------- auxiliares

    /** 'Y/m/d' (o 'Y/n/j') válida → Carbon 00:00 Asia/Tokyo; si no, null (explode('/') + checkdate del legacy) */
    public function parseDate(string $value): ?Carbon
    {
        $parts = explode('/', $value);
        if (count($parts) !== 3 || ! ctype_digit($parts[0]) || ! ctype_digit($parts[1]) || ! ctype_digit($parts[2])
            || ! checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
            return null;
        }

        return Carbon::create((int) $parts[0], (int) $parts[1], (int) $parts[2], 0, 0, 0, 'Asia/Tokyo');
    }

    /** Texto guardado con get_form2 (entidades) → texto original */
    public static function decode(mixed $value): string
    {
        return html_entity_decode((string) $value, ENT_COMPAT | ENT_HTML401, 'UTF-8');
    }
}
