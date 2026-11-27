<?php

namespace App\Services\Reservations;

use App\Models\Customers\Customer;
use App\Models\Shop;
use App\Models\Staff;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Datos comunes del flujo de reserva de la administración (category9/yoyaku/yoyaku.php → yoyaku_calendar.php
 * → yoyaku_customer_select.php → yoyaku_register.php): campos del formulario (cYoyaku::yoyakuFormKoumokuCheck),
 * menús elegidos del empleado, textos de tb_manage_words y la consulta `$getData` que pasa de una pantalla a otra.
 */
class ReservationFlowService
{
    /** Campos que viajan entre las pantallas del flujo (`$getData` del legacy) */
    public const CARRIED_FIELDS = ['kaiin', 'shop', 'staff', 'name', 'name_kana', 'tel', 'mail', 'people', 'mark'];

    /** @var array<string, array<string, mixed>>|null */
    private ?array $koumoku = null;

    /**
     * cYoyaku::yoyakuFormKoumokuCheck(): `tb_koumoku` del formulario de reservas indexado por `tb_koumoku`
     * (name, name_kana, tel, mail, people, mark, free_koumoku_N…), orden `rank`.
     *
     * @return array<string, array<string, mixed>>
     */
    public function koumoku(): array
    {
        // TODO(S1): modelo Koumoku (tb_koumoku)
        return $this->koumoku ??= DB::table('tb_koumoku')
            ->where('system_cate', '9')
            ->where('tb_name', 'tb_yoyaku')
            ->orderBy('rank')
            ->get()
            ->mapWithKeys(fn ($row) => [(string) $row->tb_koumoku => (array) $row])
            ->all();
    }

    /** ¿El campo está activo (`koumoku_disp = '1'`)? */
    public function shows(string $field): bool
    {
        return (string) ($this->koumoku()[$field]['koumoku_disp'] ?? '') === '1';
    }

    /**
     * Campos libres activos: [N => fila de tb_koumoku] (N del nombre `free_koumoku_N`), en el orden de `rank`.
     *
     * @return array<int, array<string, mixed>>
     */
    public function freeFields(): array
    {
        $fields = [];
        foreach ($this->koumoku() as $key => $row) {
            if (preg_match('/^free_koumoku_([0-9]+)/', $key, $m) && (string) $row['koumoku_disp'] === '1') {
                $fields[(int) $m[1]] = $row;
            }
        }

        return $fields;
    }

    /** cYoyaku::getYoyakuFreeKoumokuCount(): número de campos libres definidos (activos o no) */
    public function freeFieldCount(): int
    {
        return DB::table('tb_koumoku')
            ->where('system_cate', '9')
            ->where('tb_name', 'tb_yoyaku')
            ->where('tb_koumoku', 'like', 'free_koumoku%')
            ->count();
    }

    /**
     * `free_koumoku` de tb_yoyaku: los valores 1..N con rawurlencode unidos por '<>' (yoyaku_register.php).
     */
    public function encodeFreeFields(array $values): string
    {
        $encoded = [];
        for ($i = 1, $count = $this->freeFieldCount(); $i <= $count; $i++) {
            $value = $values[$i] ?? '';
            $encoded[] = rawurlencode(is_scalar($value) ? (string) $value : '');
        }

        return implode('<>', $encoded);
    }

    /**
     * manageWord() del legacy: el texto de `tb_manage_words` o el de por defecto.
     * TODO(S1): modelo ManageWord (tb_manage_words)
     */
    public function manageWord(string $default, string $contentName): string
    {
        $naiyou = DB::table('tb_manage_words')->where('content_name', $contentName)->value('naiyou');

        return filled($naiyou) ? (string) $naiyou : $default;
    }

    /** cd de los menús marcados (`menu[cd]=1` + `option_menu[cd]=1`), en ese orden */
    public static function menuCds(mixed $menu, mixed $optionMenu = null): array
    {
        $cds = [];
        foreach ([$menu, $optionMenu] as $list) {
            foreach (is_array($list) ? array_keys($list) : [] as $cd) {
                if (is_numeric($cd)) {
                    $cds[] = (int) $cd;
                }
            }
        }

        return $cds;
    }

    /**
     * Menús elegidos que el empleado tiene asignados (tb_staff_works + tb_yoyaku_koumoku_master activos),
     * orden type_flg, tb_shop_cd DESC, cd como yoyaku_calendar.php / yoyaku_customer_select.php.
     * Cada fila: cd, shoyou_time, name, naiyou, tb_shop_cd, type_flg.
     */
    public function staffMenus(int $staffCd, array $menuCds): Collection
    {
        if ($menuCds === []) {
            return collect();
        }

        return $this->staffMenuQuery($staffCd, $menuCds)
            ->orderBy('tb2.type_flg')
            ->orderByDesc('tb2.tb_shop_cd')
            ->orderBy('tb2.cd')
            ->get();
    }

    /** Suma de `shoyou_time` (minutos) de los menús elegidos */
    public function totalMinutes(int $staffCd, array $menuCds): int
    {
        return (int) $this->staffMenus($staffCd, $menuCds)->sum('shoyou_time');
    }

    /** Base de las consultas de menús del empleado (tb1 = tb_staff_works, tb2 = tb_yoyaku_koumoku_master) */
    public function staffMenuQuery(int $staffCd, array $menuCds)
    {
        return DB::table('tb_staff_works as tb1')
            ->join('tb_yoyaku_koumoku_master as tb2', 'tb1.tb_yoyaku_koumoku_master_cd', '=', 'tb2.cd')
            ->where('tb1.tb_staff_cd', $staffCd)
            ->whereIn('tb1.tb_yoyaku_koumoku_master_cd', $menuCds)
            ->where('tb1.disp_flg', '1')
            ->where('tb2.disp_flg', '1')
            ->where('tb1.hyouji_flg', '1')
            ->where('tb2.hyouji_flg', '1')
            ->select('tb2.cd', 'tb1.shoyou_time', 'tb2.name', 'tb2.naiyou', 'tb2.tb_shop_cd', 'tb2.type_flg');
    }

    /** Empleado sin mirar `disp_flg` (el legacy hacía SELECT * FROM tb_staff WHERE cd = …) */
    public function staff(mixed $staffCd): ?Staff
    {
        return is_numeric($staffCd) ? Staff::withDeleted()->find((int) $staffCd) : null;
    }

    /** getShopNameFromCd() */
    public function shopName(mixed $shopCd): ?string
    {
        return is_numeric($shopCd) ? Shop::withDeleted()->whereKey((int) $shopCd)->value('kaishamei') : null;
    }

    /** Texto del socio elegido para setMember(): '【id】 nombre' (null si no hay socio) */
    public function memberLabel(mixed $customerCd): ?string
    {
        if (! is_numeric($customerCd) || (int) $customerCd <= 0) {
            return null;
        }
        $customer = Customer::withDeleted()->find((int) $customerCd);

        return '【' . ($customer?->id ?? '') . '】 ' . ($customer?->name ?? '');
    }

    /**
     * Limpia los parámetros del flujo como `array_walk_recursive($_REQUEST, 'form2Db')`.
     *
     * @return array<string, mixed>
     */
    public static function clean(array $input): array
    {
        array_walk_recursive($input, function (&$value) {
            $value = ReservationInput::form2Db($value);
        });

        return $input;
    }

    /**
     * `$getData` del legacy: la consulta que lleva los datos del cliente y los menús entre pantallas.
     * $extra se añade al final (yoyaku_date, kijun_time…).
     */
    public static function query(array $input, array $extra = []): string
    {
        $params = [];
        foreach (self::CARRIED_FIELDS as $field) {
            $value = $input[$field] ?? '';
            $params[$field] = $field === 'people' ? (int) (is_scalar($value) ? $value : 0) : (is_scalar($value) ? (string) $value : '');
        }
        foreach (['menu', 'option_menu', 'free_koumoku'] as $list) {
            if (isset($input[$list]) && is_array($input[$list])) {
                $params[$list] = array_filter($input[$list], 'is_scalar');
            }
        }

        return http_build_query($params + $extra, '', '&', PHP_QUERY_RFC3986);
    }
}
