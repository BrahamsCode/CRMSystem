<?php

namespace App\Http\Requests\Admin;

use App\Enums\AddressType;
use App\Enums\CustomerType;
use App\Enums\Industry;
use App\Enums\MailMagazine;
use App\Enums\Occupation;
use App\Enums\Sex;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Alta de cliente (顧客新規登録).
 *
 * Reglas tomadas del formulario del legacy, probado con datos de prueba:
 * - Las lecturas (フリガナ) deben ser katakana de ancho completo. Aquí se
 *   convierten solas (hiragana y katakana de medio ancho) en vez de dar error.
 * - Teléfonos y código postal se guardan solo con dígitos: el legacy quita los
 *   guiones al confirmar (530-0001 → 5300001).
 * - Persona y empresa son excluyentes: a una persona se le piden datos
 *   personales y lugar de trabajo; a una empresa, sus datos de empresa.
 * - El legacy acepta fechas imposibles (1990/02/29, 2099/13/40); aquí no.
 */
class StoreCustomerRequest extends FormRequest
{
    /** Campos que el legacy solo pide a las personas */
    private const PERSON_ONLY = ['birth_date', 'blood_type', 'occupation', 'tel2', 'mail3'];

    /** Campos de customer_companies que solo tienen las empresas */
    private const COMPANY_ONLY = [
        'department', 'founded_on', 'capital',
        'representative_last_name', 'representative_first_name', 'representative_last_name_kana',
        'representative_first_name_kana', 'representative_birth_date', 'representative_sex',
        'contact_last_name', 'contact_first_name', 'contact_last_name_kana', 'contact_first_name_kana',
        'contact_tel1', 'contact_tel3', 'contact_mail',
    ];

    private const KANA = ['last_name_kana', 'first_name_kana', 'city_kana', 'building_kana', 'company.name_kana', 'company.representative_last_name_kana',
        'company.representative_first_name_kana', 'company.contact_last_name_kana', 'company.contact_first_name_kana'];

    private const DIGITS = ['zip', 'tel1', 'tel2', 'tel3', 'company.tel1', 'company.tel3', 'company.contact_tel1', 'company.contact_tel3'];

    public function rules(): array
    {
        // Katakana de ancho completo, la marca de vocal larga y espacios
        $kana = ['nullable', 'string', 'max:255', 'regex:/^[\x{30A0}-\x{30FF}\x{3000} ]+$/u'];
        $tel = ['nullable', 'string', 'regex:/^\d{6,15}$/'];
        $empresa = $this->input('type') == CustomerType::Company->value;

        return [
            'shop_id' => ['required', 'exists:shops,id'],
            'type' => ['required', new Enum(CustomerType::class)],
            'management_no' => ['nullable', 'string', 'max:50'],

            // Datos de persona (en una empresa, la persona registrada)
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name_kana' => $kana,
            'first_name_kana' => $kana,
            'birth_date' => ['nullable', 'date', 'before_or_equal:today', 'after:1900-01-01'],
            'sex' => ['required', new Enum(Sex::class)],
            'blood_type' => ['nullable', Rule::in(['A', 'B', 'O', 'AB'])],
            'occupation' => ['nullable', new Enum(Occupation::class)],

            // Dirección
            'zip' => ['nullable', 'string', 'regex:/^\d{3,10}$/'],
            'pref' => ['nullable', Rule::in(config('crm.prefectures'))],
            'city' => ['nullable', 'string', 'max:255'],
            'city_kana' => $kana,
            'street_address' => ['nullable', 'string', 'max:255'],
            'building' => ['nullable', 'string', 'max:255'],
            // El nombre del edificio puede llevar números y guiones (メゾン２１－Ａ)
            'building_kana' => ['nullable', 'string', 'max:255', 'regex:/^[\x{30A0}-\x{30FF}\x{3000}\x{FF10}-\x{FF19}\x{FF21}-\x{FF3A}0-9A-Za-z\-－ ]+$/u'],

            // Contacto
            'tel1' => $tel,
            'tel2' => $tel,
            'tel3' => $tel,
            'mail1' => ['nullable', 'email', 'max:255'],
            'mail2' => ['nullable', 'email', 'max:255'],
            'mail3' => ['nullable', 'email', 'max:255'],

            // Lugar de trabajo (persona) o empresa
            'company' => ['nullable', 'array'],
            'company.name' => ['nullable', 'string', 'max:255'],
            'company.name_kana' => $kana,
            'company.industry' => ['nullable', new Enum(Industry::class)],
            'company.tel1' => $tel,
            'company.tel3' => $tel,
            'company.department' => ['nullable', 'string', 'max:255'],
            'company.founded_on' => ['nullable', 'date', 'before_or_equal:today'],
            'company.capital' => ['nullable', 'integer', 'min:0'],
            'company.representative_last_name' => ['nullable', 'string', 'max:255'],
            'company.representative_first_name' => ['nullable', 'string', 'max:255'],
            'company.representative_last_name_kana' => $kana,
            'company.representative_first_name_kana' => $kana,
            'company.representative_birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'company.representative_sex' => ['nullable', Rule::in([Sex::Unknown->value, Sex::Male->value, Sex::Female->value])],
            'company.contact_last_name' => ['nullable', 'string', 'max:255'],
            'company.contact_first_name' => ['nullable', 'string', 'max:255'],
            'company.contact_last_name_kana' => $kana,
            'company.contact_first_name_kana' => $kana,
            'company.contact_tel1' => $tel,
            'company.contact_tel3' => $tel,
            'company.contact_mail' => ['nullable', 'email', 'max:255'],

            // Promoción
            'mail_magazine' => ['nullable', new Enum(MailMagazine::class)],
            'reservation_reminder_flg' => ['nullable', 'integer', 'in:0,1'],
            'address_type' => ['nullable', new Enum(AddressType::class)],
            'customer_group_id' => ['nullable', 'exists:customer_groups,id'],
            'visit_motive_id' => ['nullable', 'exists:visit_motives,id'],
            'note' => ['nullable', 'string'],

            // Información familiar
            'spouse_flg' => ['nullable', 'integer', 'in:0,1'],
            'wedding_date' => ['nullable', 'date', 'before_or_equal:today'],

            // Acceso a Mi página: el legacy la exige
            'password' => ['required', 'string', 'min:4', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'regex' => 'El campo :attribute no tiene un formato válido.',
            '*_kana.regex' => 'El campo :attribute debe escribirse en katakana (フリガナ).',
            'building_kana.regex' => 'El campo :attribute debe escribirse en katakana (フリガナ); se admiten números y letras.',
            'company.*_kana.regex' => 'El campo :attribute debe escribirse en katakana (フリガナ).',
            'zip.regex' => 'El código postal debe tener solo números (ej. 5300001).',
        ];
    }

    public function attributes(): array
    {
        // Reutiliza las etiquetas de la pantalla para no mantener dos listas
        $t = fn ($k) => __("customers.nuevo.{$k}");

        return [
            'shop_id' => $t('tienda'), 'type' => $t('tipo'),
            'last_name' => $t('apellido'), 'first_name' => $t('nombre'),
            'last_name_kana' => $t('apellido_kana'), 'first_name_kana' => $t('nombre_kana'),
            'mail1' => $t('mail1'), 'mail2' => $t('mail2'), 'mail3' => $t('mail_personal'),
            'tel1' => $t('tel'), 'tel2' => $t('movil'), 'tel3' => $t('fax'),
            'occupation' => $t('ocupacion'), 'address_type' => $t('tipo_direccion'),
            'birth_date' => $t('nacimiento'), 'zip' => $t('zip'), 'pref' => $t('pref'),
            'city_kana' => $t('ciudad_kana'), 'building_kana' => $t('edificio_kana'), 'password' => $t('password'),
            'company.name' => $t('trabajo_nombre'), 'company.name_kana' => $t('trabajo_nombre_kana'),
            'company.industry' => $t('rubro'), 'company.tel1' => $t('trabajo_tel'), 'company.tel3' => $t('trabajo_fax'),
            'company.founded_on' => $t('empresa_fundacion'), 'company.capital' => $t('empresa_capital'),
            'company.representative_last_name_kana' => $t('empresa_representante_kana'),
            'company.representative_first_name_kana' => $t('empresa_representante_kana'),
            'company.contact_last_name_kana' => $t('empresa_contacto_kana'),
            'company.contact_first_name_kana' => $t('empresa_contacto_kana'),
            'company.contact_tel1' => $t('empresa_contacto_tel'), 'company.contact_tel3' => $t('empresa_contacto_fax'),
            'company.contact_mail' => $t('empresa_contacto_mail'),
        ];
    }

    /** Datos de customers, sin la parte de empresa */
    public function customerData(): array
    {
        return collect($this->validated())->except('company')->all();
    }

    /** Datos de customer_companies, o null si no se rellenó nada */
    public function companyData(): ?array
    {
        $data = array_filter((array) ($this->validated()['company'] ?? []), fn ($v) => $v !== null && $v !== '');

        return $data === [] ? null : $data;
    }

    /**
     * Normaliza antes de validar:
     * - '' → null (una cadena vacía en una fecha o un email rompe la inserción).
     * - Lecturas a katakana de ancho completo; teléfonos y código postal a dígitos.
     * - Quita lo que no corresponde al tipo de cliente.
     */
    protected function prepareForValidation(): void
    {
        $data = $this->emptyToNull($this->all());

        foreach (self::KANA as $key) {
            if (is_string($v = data_get($data, $key))) {
                // K: katakana de medio ancho → ancho completo, V: une los dakuten, C: hiragana → katakana
                data_set($data, $key, trim(preg_replace('/[ \x{3000}]+/u', ' ', mb_convert_kana($v, 'KVC'))));
            }
        }

        foreach (self::DIGITS as $key) {
            if (is_string($v = data_get($data, $key))) {
                // Dígitos de ancho completo a normales y fuera guiones, espacios y paréntesis
                data_set($data, $key, preg_replace('/[\s\-‐－ー−()（）]/u', '', mb_convert_kana($v, 'n')));
            }
        }

        if ((int) ($data['type'] ?? 0) === CustomerType::Company->value) {
            foreach (self::PERSON_ONLY as $key) {
                $data[$key] = null;
            }
            $data['sex'] = Sex::NotApplicable->value;
        } else {
            $data['sex'] ??= Sex::Unknown->value;
            foreach (self::COMPANY_ONLY as $key) {
                unset($data['company'][$key]);
            }
        }

        $this->replace($data);
    }

    private function emptyToNull(array $data): array
    {
        return array_map(fn ($v) => is_array($v) ? $this->emptyToNull($v) : (is_string($v) && trim($v) === '' ? null : $v), $data);
    }
}
