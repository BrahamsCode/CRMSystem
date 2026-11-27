<?php

namespace Database\Seeders\Demo\Clientes;

use App\Models\Reservations\Facility;
use App\Models\Reservations\Reservation;
use App\Models\Reservations\ReservationConfig;
use App\Models\Reservations\ReservationMenu;
use App\Models\Reservations\ReservationMenuCategory;
use App\Models\Reservations\ReservationSlot;
use App\Models\Reservations\StaffWork;
use App\Services\Reservations\ReservationFlowService;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Datos de demostración de 予約管理 (S2, parte D3; CONVENCIONES §4).
 *
 * Configuración (solo lo que falta; lo existente se usa sin tocarlo):
 * - 各店舗設定 (`tb_yoyaku_config`) de cada tienda activa que no lo tenga (10:00-20:00).
 * - Categorías y menús de reserva de un salón (`tb_yoyaku_koumoku_category`, `tb_yoyaku_koumoku_master`
 *   con `tb_shop_cd = -1` 全店舗), y 設備 (`tb_setsubi`) en las tiendas sin ninguno.
 * - スタッフ施術 (`tb_staff_works`) y 指名許諾 (`tb_staff.reserve_flg = '1'`) solo del personal que aún no
 *   tenía ningún 施術 (el que ya lo tenía se usa tal cual).
 * - 受付日 (`tb_yoyaku_uketsuke_day`) de los últimos 60 días y las próximas 2 semanas (火曜定休),
 *   sin tocar las franjas que ya existan.
 *
 * Reservas (`tb_yoyaku`): ~64 en las próximas 2 semanas y ~40 pasadas, de los socios demo
 * (`demo%@example.com`) y algunos no socios; altas de administración (entry_type ''), Web (100),
 * Web フリー予約 (101) y クイック予約 (201); ~10 % canceladas (`disp_flg = '9'`). No se envía ningún correo.
 *
 * Idempotente: el marcador es la primera reserva (no socio, `mail = demo-yoyaku@example.com`).
 */
class ReservasDemoSeeder extends Seeder
{
    /** Correo de la reserva marcador */
    public const MARKER_MAIL = 'demo-yoyaku@example.com';

    public const SEED = 20261133;

    public const FUTURE_COUNT = 64;

    public const PAST_COUNT = 40;

    /** Días de franjas hacia atrás (las reservas pasadas caen aquí) y hacia delante (2 semanas) */
    private const PAST_DAYS = 60;

    private const FUTURE_DAYS = 14;

    /** Categorías de menú de reserva (nombre => rank relativo) */
    private const CATEGORIES = ['カット', 'カラー', 'パーマ・縮毛矯正', 'ケア・スパ'];

    /** Menús: [nombre, minutos, tipo, categoría, descripción] */
    private const MENUS = [
        ['カット', 60, '1', 'カット', 'シャンプー・ブロー込み'],
        ['前髪カット', 15, '1', 'カット', '前髪のみのカットです'],
        ['カット＋カラー', 120, '1', 'カラー', '根元から毛先までのワンメイクカラー'],
        ['リタッチカラー', 90, '1', 'カラー', '根元のみ（2cm程度）'],
        ['イルミナカラー', 120, '1', 'カラー', '透明感のある仕上がり'],
        ['デジタルパーマ', 150, '1', 'パーマ・縮毛矯正', 'カット込み'],
        ['縮毛矯正', 180, '1', 'パーマ・縮毛矯正', 'カット込み・ショート料金'],
        ['トリートメント', 60, '1', 'ケア・スパ', '髪質改善トリートメント'],
        ['ヘッドスパ', 45, '1', 'ケア・スパ', '炭酸泉を使ったリラクゼーション'],
        ['炭酸スパ', 15, '2', 'ケア・スパ', 'オプションで追加できます'],
        ['眉カット', 15, '2', null, '眉の形を整えます'],
        ['ハイライト追加', 30, '2', 'カラー', 'カラーと同時施術'],
    ];

    private const COMMENTS = ['前回と同じ色でお願いします', '駐車場を利用します', '初めての来店です', 'お子様連れです',
        '短めにしたいとのこと', '担当指名あり', '17時までに終わりたい', '頭皮が敏感なので注意'];

    private Generator $faker;

    private int $nonMemberSeq = 0;

    public function run(): void
    {
        if (DB::table('tb_yoyaku')->where('mail', self::MARKER_MAIL)->exists()) {
            $this->command?->info('ReservasDemoSeeder: ya sembrado (marcador presente), no se hace nada.');

            return;
        }

        $this->faker = \Faker\Factory::create('ja_JP');
        $this->faker->seed(self::SEED);

        DB::transaction(function () {
            $menus = $this->ensureMenus();
            $shops = DB::table('tb_shop')->where('disp_flg', '1')->whereRaw("COALESCE(hyouji_flg, '') <> '9'")->orderBy('cd')->pluck('cd')->map(fn ($cd) => (int) $cd);

            $slotPool = []; // [ ['slot' => ReservationSlot, 'menus' => [cd => minutos], 'shop' => cd] ]
            foreach ($shops as $shopCd) {
                $config = $this->ensureConfig($shopCd);
                $this->ensureFacilities($shopCd);
                foreach ($this->reservationStaff($shopCd) as $staff) {
                    $staffMenus = $this->ensureStaffWorks((int) $staff->cd, $menus);
                    if ($staffMenus === []) {
                        continue;
                    }
                    foreach ($this->ensureSlots((int) $staff->cd, $config) as $slot) {
                        $slotPool[] = ['slot' => $slot, 'menus' => $staffMenus, 'shop' => $shopCd];
                    }
                }
            }

            if ($slotPool === []) {
                $this->command?->warn('ReservasDemoSeeder: no hay personal ni franjas, no se crean reservas.');

                return;
            }

            $this->createReservations($slotPool, $menus);
        });
    }

    // ---------------------------------------------------------------- configuración

    /** Categorías y menús 全店舗: devuelve [cd => fila] de los menús demo (existentes o nuevos) */
    private function ensureMenus(): Collection
    {
        $categoryCds = [];
        foreach (self::CATEGORIES as $name) {
            $category = ReservationMenuCategory::query()->where('name', $name)->first();
            if (! $category) {
                $category = ReservationMenuCategory::create([
                    'name' => $name,
                    'hyouji_flg' => ReservationMenuCategory::VISIBLE,
                    'disp_flg' => '1',
                    'rank' => (int) ReservationMenuCategory::query()->max('rank') + 1,
                ]);
            }
            $categoryCds[$name] = (int) $category->cd;
        }

        $menus = collect();
        foreach (self::MENUS as [$name, $minutes, $type, $categoryName, $naiyou]) {
            $categoryCd = $categoryName ? $categoryCds[$categoryName] : null;
            $menu = ReservationMenu::query()->where('name', $name)->where('type_flg', $type)->whereIn('tb_shop_cd', [-1])->first();
            if (! $menu) {
                // rank: siguiente del grupo tienda + tipo + categoría, como el alta del legacy
                $rank = (int) ReservationMenu::query()->where('tb_shop_cd', ReservationMenu::ALL_SHOPS)->where('type_flg', $type)
                    ->whereRaw('coalesce(tb_yoyaku_koumoku_category_cd, 0) = ?', [(int) $categoryCd])->max('rank') + 1;
                $menu = ReservationMenu::create([
                    'name' => $name,
                    'shoyou_time' => $minutes,
                    'naiyou' => $naiyou,
                    'tb_shop_cd' => ReservationMenu::ALL_SHOPS,
                    'type_flg' => $type,
                    'tb_yoyaku_koumoku_category_cd' => $categoryCd,
                    'rank' => $rank,
                    'menu_end_time_flg' => '',
                    'hyouji_flg' => '1',
                    'disp_flg' => '1',
                ]);
            }
            $menus->put((int) $menu->cd, $menu);
        }

        return $menus;
    }

    /** 各店舗設定: la existente o una nueva de 10:00 a 20:00 */
    private function ensureConfig(int $shopCd): ReservationConfig
    {
        return ReservationConfig::query()->where('tb_shop_cd', $shopCd)->first()
            ?? ReservationConfig::create([
                'tb_shop_cd' => $shopCd,
                'start_time' => '10:00',
                'end_time' => '20:00',
                'shimekiri' => '0',
                'shimekiri_time' => '1',
                'automail' => '',
                'automail_time' => ':',
                'uketuke_type' => '',
                'timeline_sort_type' => '1',
                'setsubi_capacity' => null,
            ]);
    }

    /** 設備 de ejemplo en las tiendas que no tienen ninguno */
    private function ensureFacilities(int $shopCd): void
    {
        if (Facility::query()->where('tb_shop_cd', $shopCd)->exists()) {
            return;
        }
        foreach ([['シャンプー台', 3], ['個室', 1]] as [$name, $quantity]) {
            Facility::create(['tb_shop_cd' => $shopCd, 'name' => $name, 'quantity' => $quantity, 'disp_flg' => '1']);
        }
    }

    /** Hasta 4 empleados activos de la tienda; el master solo si no hay otro */
    private function reservationStaff(int $shopCd): Collection
    {
        $staff = DB::table('tb_staff')->where('disp_flg', '1')->where('shop_cd', $shopCd)
            ->orderByRaw('COALESCE(rank, 0)')->orderBy('cd')->get(['cd', 'login_id']);
        $others = $staff->filter(fn ($s) => (string) $s->login_id !== 'master');

        return ($others->isNotEmpty() ? $others : $staff)->take(4)->values();
    }

    /**
     * Menús que atiende el empleado [menu_cd => minutos] (solo ベースメニュー activas).
     * Si no tenía ningún 施術, se le asignan los menús demo y se activa su 指名許諾.
     */
    private function ensureStaffWorks(int $staffCd, Collection $menus): array
    {
        $existing = DB::table('tb_staff_works as w')
            ->join('tb_yoyaku_koumoku_master as m', 'm.cd', '=', 'w.tb_yoyaku_koumoku_master_cd')
            ->where('w.tb_staff_cd', $staffCd)->where('w.disp_flg', '1')->where('m.disp_flg', '1')
            ->get(['m.cd', 'm.type_flg', 'w.shoyou_time', 'm.shoyou_time as menu_time']);

        if (! DB::table('tb_staff_works')->where('tb_staff_cd', $staffCd)->where('disp_flg', '1')->exists()) {
            foreach ($menus as $menu) {
                StaffWork::create([
                    'tb_staff_cd' => $staffCd,
                    'tb_yoyaku_koumoku_master_cd' => $menu->cd,
                    'shoyou_time' => $menu->shoyou_time,
                    'hyouji_flg' => '1',
                    'disp_flg' => '1',
                    'mypage_flg' => '',
                ]);
            }
            DB::table('tb_staff')->where('cd', $staffCd)->update(['reserve_flg' => '1']);

            return $menus->mapWithKeys(fn ($m) => [(int) $m->cd => ['minutes' => (int) $m->shoyou_time, 'type' => (string) $m->type_flg]])->all();
        }

        return $existing->mapWithKeys(fn ($w) => [(int) $w->cd => [
            'minutes' => (int) ($w->shoyou_time ?: $w->menu_time ?: 60),
            'type' => (string) $w->type_flg,
        ]])->all();
    }

    /** Franjas del empleado: las que ya hay se usan; se crean las que faltan (sin 火曜) */
    private function ensureSlots(int $staffCd, ReservationConfig $config): array
    {
        $today = now('Asia/Tokyo')->startOfDay();
        $open = $this->clockSeconds((string) $config->start_time, 10 * 3600);
        $close = $this->clockSeconds((string) $config->end_time, 20 * 3600);
        if ($close <= $open) {
            [$open, $close] = [10 * 3600, 20 * 3600];
        }

        $days = [];
        for ($i = -self::PAST_DAYS; $i < self::FUTURE_DAYS; $i++) {
            $days[] = $today->copy()->addDays($i);
        }
        $existing = ReservationSlot::query()->where('tb_staff_cd', $staffCd)
            ->whereIn('moushikomi_date', array_map(fn (Carbon $d) => $d->format('Y/m/d'), $days))
            ->get()->keyBy('moushikomi_date');

        $slots = [];
        foreach ($days as $day) {
            $date = $day->format('Y/m/d');
            if ($slot = $existing->get($date)) {
                $slots[] = $slot;

                continue;
            }
            if ($day->dayOfWeek === Carbon::TUESDAY) {
                continue; // 火曜定休
            }
            $breakStart = $open + 3 * 3600;
            $hasBreak = $breakStart + 3600 < $close;
            $slots[] = ReservationSlot::create([
                'tb_staff_cd' => $staffCd,
                'moushikomi_date' => $date,
                'start_time' => $open,
                'end_time' => $close,
                'people' => 1,
                'start_break_time' => $hasBreak ? $this->clock($breakStart) : '',
                'end_break_time' => $hasBreak ? $this->clock($breakStart + 3600) : '',
                'status_flg' => ReservationSlot::STATUS_OPEN,
                'hyouji_flg' => '1',
                'disp_flg' => '1',
            ]);
        }

        return $slots;
    }

    // ---------------------------------------------------------------- reservas

    private function createReservations(array $slotPool, Collection $menus): void
    {
        $today = now('Asia/Tokyo')->startOfDay();
        $future = array_values(array_filter($slotPool, fn ($p) => $p['slot']->dayStart()?->gte($today)));
        $past = array_values(array_filter($slotPool, fn ($p) => $p['slot']->dayStart()?->lt($today)));

        $members = DB::table('tb_customer')->where('email1', 'like', 'demo%@example.com')->where('disp_flg', '1')
            ->orderBy('cd')->get(['cd', 'tb_shop_cd', 'name', 'tel', 'kojin_mobile', 'touroku_kubun']);
        $occupied = []; // [slot cd => [[inicio, fin], …]]
        $first = true;

        foreach ([[$future, self::FUTURE_COUNT, false], [$past, self::PAST_COUNT, true]] as [$pool, $count, $isPast]) {
            $made = 0;
            for ($try = 0; $pool !== [] && $made < $count && $try < $count * 10; $try++) {
                $item = $pool[$this->faker->numberBetween(0, count($pool) - 1)];
                if ($this->createOne($item, $members, $occupied, $isPast, $first)) {
                    $made++;
                    $first = false;
                }
            }
        }
    }

    /** Una reserva en un hueco libre de la franja (rejilla de 30 min, fuera del descanso y sin solapes) */
    private function createOne(array $item, Collection $members, array &$occupied, bool $isPast, bool $isMarker): bool
    {
        /** @var ReservationSlot $slot */
        $slot = $item['slot'];
        $base = array_keys(array_filter($item['menus'], fn ($m) => $m['type'] !== '2'));
        $options = array_keys(array_filter($item['menus'], fn ($m) => $m['type'] === '2'));
        if ($base === []) {
            return false;
        }

        $menuCd = $base[$this->faker->numberBetween(0, count($base) - 1)];
        $optionCds = $options !== [] && $this->faker->boolean(30) ? [$options[$this->faker->numberBetween(0, count($options) - 1)]] : [];
        $minutes = $item['menus'][$menuCd]['minutes'];
        foreach ($optionCds as $cd) {
            $minutes += $item['menus'][$cd]['minutes'];
        }
        $duration = max(15, $minutes) * 60;

        $occupied[$slot->cd] ??= DB::table('tb_yoyaku')->where('tb_yoyaku_uketsuke_day_cd', $slot->cd)->where('disp_flg', '1')
            ->get(['start_time', 'end_time'])->map(fn ($r) => [(int) $r->start_time, (int) $r->end_time])->all();
        $busy = $occupied[$slot->cd];
        if ($slot->start_break_time !== null && $slot->start_break_time !== '') {
            $busy[] = [$this->clockSeconds((string) $slot->start_break_time, 0), $this->clockSeconds((string) $slot->end_break_time, 0)];
        }

        $candidates = [];
        for ($s = (int) $slot->start_time; $s + $duration <= (int) $slot->end_time; $s += 1800) {
            $free = true;
            foreach ($busy as [$bs, $be]) {
                if ($s < $be && $s + $duration > $bs) {
                    $free = false;
                    break;
                }
            }
            if ($free) {
                $candidates[] = $s;
            }
        }
        if ($candidates === []) {
            return false;
        }
        $start = $candidates[$this->faker->numberBetween(0, count($candidates) - 1)];

        // Origen: administración, Web (r/), Web フリー予約, クイック予約
        $entryType = $isMarker ? Reservation::ENTRY_PUBLIC_WEB : $this->faker->randomElement([
            Reservation::ENTRY_ADMIN, Reservation::ENTRY_ADMIN, Reservation::ENTRY_ADMIN, Reservation::ENTRY_ADMIN,
            Reservation::ENTRY_ADMIN, Reservation::ENTRY_ADMIN, Reservation::ENTRY_PUBLIC_WEB, Reservation::ENTRY_PUBLIC_WEB,
            Reservation::ENTRY_PUBLIC_WEB, Reservation::ENTRY_PUBLIC_WEB_FREE, Reservation::ENTRY_QUICK,
        ]);
        $isWeb = in_array($entryType, [Reservation::ENTRY_PUBLIC_WEB, Reservation::ENTRY_PUBLIC_WEB_FREE], true);

        $member = null;
        if (! $isMarker && $members->isNotEmpty() && $this->faker->boolean(85)) {
            $ofShop = $members->where('tb_shop_cd', $item['shop'])->values();
            $list = $ofShop->isNotEmpty() ? $ofShop : $members;
            $member = $list[$this->faker->numberBetween(0, $list->count() - 1)];
        }

        $cancelled = ! $isMarker && $this->faker->boolean(10);
        $startAt = $slot->dayStart()->addSeconds($start);
        $now = now('Asia/Tokyo');
        $bookedAt = $startAt->copy()->subMinutes($this->faker->numberBetween(60, 21 * 24 * 60));
        if ($bookedAt->gt($now)) {
            $bookedAt = $now->copy()->subMinutes($this->faker->numberBetween(5, 600));
        }

        $data = [
            'tb_yoyaku_uketsuke_day_cd' => $slot->cd,
            'start_time' => $start,
            'end_time' => $start + $duration,
            'people' => $this->faker->boolean(8) ? 2 : 1,
            'base_tb_yoyaku_koumoku_master_cd' => (string) $menuCd,
            'option_tb_yoyaku_koumoku_master_cd' => implode(',', $optionCds),
            'free_koumoku' => app(ReservationFlowService::class)->encodeFreeFields([]),
            'entry_type' => $entryType,
            'comment' => ! $isWeb && $this->faker->boolean(30) ? $this->faker->randomElement(self::COMMENTS) : '',
            'mark' => ! $isWeb && $this->faker->boolean(15) ? $this->faker->randomElement(['★', '◎', '新']) : '',
            'hyouji_flg' => '1',
            'disp_flg' => $cancelled ? Reservation::DELETED_FLAG : '1',
        ];

        if ($member) {
            $data['tb_customer_cd'] = (int) $member->cd;
            // Web: el teléfono del formulario; administración: sin datos propios (los del socio)
            $data['tel'] = $isWeb ? (string) ($member->kojin_mobile ?: $member->tel) : null;
        } else {
            $this->nonMemberSeq++;
            $data['name'] = $this->faker->lastName() . ' ' . $this->faker->firstName();
            $data['name_kana'] = $this->faker->lastKanaName() . ' ' . $this->faker->firstKanaName();
            $data['tel'] = sprintf('090-0000-%04d', 5000 + $this->nonMemberSeq);
            $data['mail'] = $isMarker ? self::MARKER_MAIL : sprintf('demo-yoyaku%d@example.com', $this->nonMemberSeq);
        }

        $reservation = new Reservation($data);
        $reservation->timestamps = false;
        $reservation->touroku_date = (string) $bookedAt->getTimestamp();
        $reservation->update_date = (string) $bookedAt->getTimestamp();
        $reservation->save();

        if (! $cancelled) {
            $occupied[$slot->cd][] = [$start, $start + $duration];
        }

        return true;
    }

    // ---------------------------------------------------------------- utilidades

    /** 'HH:MM' → segundos (las horas 24+ pasan de 86400); $default si no se entiende */
    private function clockSeconds(string $clock, int $default): int
    {
        return preg_match('/^(\d{1,2}):(\d{2})$/', trim($clock), $m) ? (int) $m[1] * 3600 + (int) $m[2] * 60 : $default;
    }

    private function clock(int $seconds): string
    {
        return sprintf('%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60));
    }
}
