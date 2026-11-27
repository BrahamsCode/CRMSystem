<?php

namespace App\Services\Customers;

use App\Models\Customers\Customer;
use App\Models\Customers\Karte;
use App\Models\Customers\ServiceCategory;
use App\Models\Staff;
use App\Support\LegacyText;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * カルテ del cliente (legacy `system/category1/inc/karute_frm.php` + `karute_register.php`):
 * tiendas y 施術 del formulario, comprobaciones con los textos del legacy y alta/edición en `tb_karute`.
 */
class KarteService
{
    /** Campos que el formulario no reenvía como ocultos ($frm_elements_array del legacy) */
    public const FORM_FIELDS = ['tb_shop_cd', 'hiduke', 'tb_sejutsu_cate_cd', 'comment', 'act', 'admin_login'];

    /** Tiendas del selector (disp_flg <> 9; sin master solo la de la sesión), cd => kaishamei */
    public function shops(Staff $staff, int $sessionShopCd): array
    {
        return DB::table('tb_shop')
            ->whereRaw("COALESCE(disp_flg, '') <> '9'")
            ->when(! $staff->isMaster(), fn ($q) => $q->where('cd', $sessionShopCd))
            ->orderBy('cd')
            ->pluck('kaishamei', 'cd')
            ->all();
    }

    /** 施術 de la tienda o de todas (tb_shop_cd nulo), disp_flg = '1'; cd => name */
    public function serviceCategories(int|string|null $shopCd): array
    {
        if ($shopCd === null || $shopCd === '') {
            return [];
        }

        return ServiceCategory::query()
            ->where('disp_flg', '1')
            ->whereRaw('COALESCE(tb_shop_cd, 0) IN (0, ?)', [(int) $shopCd])
            ->orderBy('cd')
            ->pluck('name', 'cd')
            ->all();
    }

    /** Datos del formulario desde una カルテ guardada (karute_update.php) */
    public function formData(Karte $karte): array
    {
        $date = is_numeric($karte->uketsuke_date)
            ? Carbon::createFromTimestamp((int) $karte->uketsuke_date, 'Asia/Tokyo')->format('Y/m/d')
            : '';

        return [
            'tb_shop_cd' => (string) $karte->tb_shop_cd,
            'hiduke' => $date,
            'tb_sejutsu_cate_cd' => (string) $karte->tb_sejutsu_cate_cd,
            'comment' => (string) $karte->comment,
        ];
    }

    /** get_form() de todo lo enviado, como karute_new.php/karute_update.php con act=up */
    public function clean(array $input): array
    {
        return array_map(fn ($v) => LegacyText::form($v), $input);
    }

    /**
     * Comprobaciones de karute_frm.php. Devuelve los errores por campo (texto del legacy, sin el HTML);
     * vacío = se puede registrar.
     */
    public function errors(array $data): array
    {
        $errors = [];
        if (($data['tb_shop_cd'] ?? '') === '') {
            $errors['tb_shop_cd'] = '※店舗を選択して下さい。';
        }
        if (($data['tb_sejutsu_cate_cd'] ?? '') === '') {
            $errors['tb_sejutsu_cate_cd'] = '※施術を選択して下さい。';
        }
        $hiduke = $data['hiduke'] ?? '';
        if ($hiduke === '') {
            $errors['hiduke'] = '※必須入力項目です。';
        } elseif ($this->date($hiduke) === null) {
            $errors['hiduke'] = '※' . $hiduke . 'は不適切な形式です。';
        }

        return $errors;
    }

    /** Alta (karute_register.php con register_type new) o edición; devuelve el cd */
    public function save(Karte $karte, Customer $customer, array $data): int
    {
        $isNew = ! $karte->exists;
        $karte->fill([
            'tb_customer_cd' => $customer->cd,
            'tb_shop_cd' => (int) $data['tb_shop_cd'],
            'tb_staff_cd' => ($data['tb_staff_cd'] ?? '') !== '' ? (int) $data['tb_staff_cd'] : null,
            'tb_sejutsu_cate_cd' => (int) $data['tb_sejutsu_cate_cd'],
            'comment' => $data['comment'] ?? '',
            'uketsuke_date' => $this->date($data['hiduke'])->getTimestamp(),
        ]);
        if ($isNew) {
            $karte->disp_flg = '1';
        }
        $karte->save();

        return (int) $karte->cd;
    }

    /** Y/m/d válido (checkdate) a medianoche de Tokio; null si no lo es */
    private function date(string $value): ?Carbon
    {
        $parts = explode('/', $value);
        if (count($parts) !== 3 || ! ctype_digit($parts[0]) || ! ctype_digit($parts[1]) || ! ctype_digit($parts[2])
            || ! checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
            return null;
        }

        return Carbon::create((int) $parts[0], (int) $parts[1], (int) $parts[2], 0, 0, 0, 'Asia/Tokyo');
    }
}
