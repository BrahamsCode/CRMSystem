<?php

namespace App\Services\Customers;

use App\Models\Customers\Customer;
use Illuminate\Support\Facades\DB;

/**
 * 追加情報 de varios registros de la ficha (pestaña de categoría personalizada con `record_type = 1`):
 * legacy `system/category1/inc/add_record_new.php`, `add_record_confirm.php`, `add_record_register.php` y `del_record.php`.
 *
 * Cada registro es un grupo de filas de `tb_customer_sub` (una por campo `tb_search_form`) con el mismo
 * `category_inner_group` (número de registro por cliente y categoría: máximo + 1). Los campos se pintan con
 * `partials/custom_field`, se validan con `CustomerFormService::customFieldError` (createform_errorcheck) y se
 * guardan con `CustomerRegistrationService::customFieldValues` (createform_sql), como el alta del cliente.
 */
class CustomRecordService
{
    public function __construct(
        private CustomerFormService $form,
        private CustomerRegistrationService $registration,
        private VisitRecordService $visitRecords,
    ) {}

    /** tb_search_cate de clientes visible (category1, disp_flg <> 9, hyouji_flg 1) con ese cd, o null */
    public function category(int $cateCd): ?array
    {
        $category = DB::table('tb_search_cate')
            ->where('category', 'category1')
            ->whereRaw("COALESCE(disp_flg, '') <> '9'")
            ->where('hyouji_flg', '1')
            ->where('cd', $cateCd)
            ->first();

        return $category ? (array) $category : null;
    }

    /** Campos de la categoría (tb_search_form: disp_flg <> 9, hyouji_flg 1 o 2, ORDER BY rank) */
    public function fields(int $cateCd): array
    {
        return $this->visitRecords->fields($cateCd);
    }

    /** Valores guardados del registro (createform_insert_request con su category_inner_group) */
    public function values(Customer $customer, array $fields, string $group): array
    {
        return $this->visitRecords->fieldValues($customer, $fields, $group);
    }

    /** createform_errorcheck() de cada campo: form{cd} => mensaje (vacío = sin errores) */
    public function validate(array $fields, array $data): array
    {
        $errors = [];
        foreach ($fields as $form) {
            $message = $this->form->customFieldError($form, $data);
            if ($message !== '') {
                $errors['form' . $form['cd']] = $message;
            }
        }

        return $errors;
    }

    /**
     * add_record_register.php: alta (group vacío → máximo + 1) o corrección del registro. Por campo, actualiza su fila
     * activa del grupo o la crea (cd con createHatsuban de la tienda de la sesión). Todo en una transacción con el
     * bloqueo de las filas de la categoría del cliente (el legacy hacía LOCK TABLE hatuban, tb_customer_sub).
     *
     * @return string  category_inner_group guardado
     */
    public function save(Customer $customer, array $category, array $fields, array $data, string $group, int $shopCd): string
    {
        return DB::transaction(function () use ($customer, $category, $fields, $data, $group, $shopCd) {
            $now = time();
            $cateCd = (int) $category['cd'];
            $rows = fn () => DB::table('tb_customer_sub')->where('tb_search_cate_cd', $cateCd)->where('tb_customer_cd', $customer->cd);

            if ($group !== '') {
                // Se conserva la fecha de alta del registro
                $recordingTime = (string) ($rows()->where('category_inner_group', $group)->min('touroku_date') ?: $now);
            } else {
                $recordingTime = (string) $now;
                $last = $rows()->whereRaw("category_inner_group ~ '^[0-9]+$'")
                    ->orderByRaw('CAST(category_inner_group AS INT) DESC')->limit(1)->lockForUpdate()->value('category_inner_group');
                $group = (string) ((int) $last + 1);
            }

            foreach ($fields as $form) {
                $values = $this->registration->customFieldValues($form, $data) + [
                    'form_title' => $category['name'] . '<>' . $form['name'],
                    'touroku_date' => $recordingTime,
                    'update_date' => (string) $now,
                ];

                $exists = $rows()->where('tb_search_form_cd', $form['cd'])->where('category_inner_group', $group)
                    ->where('disp_flg', '1')->exists();

                if ($exists) {
                    $rows()->where('tb_search_form_cd', $form['cd'])->where('category_inner_group', $group)->update($values);
                } else {
                    DB::table('tb_customer_sub')->insert($values + [
                        'cd' => $this->registration->uniqueCd($shopCd),
                        'tb_customer_cd' => $customer->cd,
                        'tb_search_cate_cd' => $cateCd,
                        'tb_search_form_cd' => $form['cd'],
                        'form_type' => '1',
                        'disp_flg' => '1',
                        'category_inner_group' => $group,
                    ]);
                }
            }

            $rows()->where('category_inner_group', $group)->update(['update_date' => (string) $now]);

            return $group;
        });
    }

    /** del_record.php: borrado lógico (disp_flg = 9) de todas las filas del registro */
    public function delete(Customer $customer, int $cateCd, string $group): void
    {
        DB::table('tb_customer_sub')
            ->where('tb_search_cate_cd', $cateCd)->where('tb_customer_cd', $customer->cd)
            ->where('category_inner_group', $group)
            ->update(['disp_flg' => '9']);
    }
}
