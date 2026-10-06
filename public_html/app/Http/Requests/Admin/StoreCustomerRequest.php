<?php

namespace App\Http\Requests\Admin;

use App\Enums\AddressType;
use App\Enums\CustomerType;
use App\Enums\Industry;
use App\Enums\MailMagazine;
use App\Enums\Occupation;
use App\Enums\Sex;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreCustomerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'shop_id' => ['required', 'exists:shops,id'],
            'type' => ['required', new Enum(CustomerType::class)],
            'management_no' => ['nullable', 'string', 'max:50'],

            // Datos de persona
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name_kana' => ['nullable', 'string', 'max:255'],
            'first_name_kana' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'sex' => ['nullable', new Enum(Sex::class)],
            'blood_type' => ['nullable', 'string', 'max:5'],
            'occupation' => ['nullable', new Enum(Occupation::class)],

            // Dirección
            'zip' => ['nullable', 'string', 'max:20'],
            'pref' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:255'],
            'city_kana' => ['nullable', 'string', 'max:255'],
            'street_address' => ['nullable', 'string', 'max:255'],
            'building' => ['nullable', 'string', 'max:255'],
            'building_kana' => ['nullable', 'string', 'max:255'],

            // Contacto
            'tel1' => ['nullable', 'string', 'max:50'],
            'tel2' => ['nullable', 'string', 'max:50'],
            'fax' => ['nullable', 'string', 'max:50'],
            'mail1' => ['nullable', 'email', 'max:255'],
            'mail2' => ['nullable', 'email', 'max:255'],
            'mail3' => ['nullable', 'email', 'max:255'],

            // Lugar de trabajo
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_name_kana' => ['nullable', 'string', 'max:255'],
            'company_industry' => ['nullable', new Enum(Industry::class)],
            'company_tel' => ['nullable', 'string', 'max:50'],
            'company_fax' => ['nullable', 'string', 'max:50'],

            // Promoción
            'mail_magazine_flg' => ['nullable', new Enum(MailMagazine::class)],
            'reservation_reminder_flg' => ['nullable', 'integer', 'in:0,1'],
            'address_type' => ['nullable', new Enum(AddressType::class)],
            'customer_group_id' => ['nullable', 'exists:customer_groups,id'],
            'visit_motive_id' => ['nullable', 'exists:visit_motives,id'],
            'note' => ['nullable', 'string'],

            // Información familiar
            'spouse_flg' => ['nullable', 'integer', 'in:0,1'],
            'wedding_date' => ['nullable', 'date'],

            // Acceso a Mi página
            'password' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        // Reutiliza las etiquetas de la pantalla para no mantener dos listas
        return [
            'shop_id' => __('customers.nuevo.tienda'),
            'type' => __('customers.nuevo.tipo'),
            'last_name' => __('customers.nuevo.apellido'),
            'first_name' => __('customers.nuevo.nombre'),
            'mail1' => __('customers.nuevo.mail1'),
            'mail2' => __('customers.nuevo.mail2'),
            'mail3' => __('customers.nuevo.mail_personal'),
            'occupation' => __('customers.nuevo.ocupacion'),
            'company_industry' => __('customers.nuevo.rubro'),
            'address_type' => __('customers.nuevo.tipo_direccion'),
            'birth_date' => __('customers.nuevo.nacimiento'),
            'zip' => __('customers.nuevo.zip'),
        ];
    }

    /**
     * Los inputs vacíos de un formulario llegan como '' y no como null. Hay que
     * normalizarlos: una cadena vacía en un campo de fecha o en un email revienta
     * la inserción.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(
            collect($this->all())
                ->map(fn ($value) => is_string($value) && trim($value) === '' ? null : $value)
                ->all()
        );
    }
}
