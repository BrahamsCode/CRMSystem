<?php

namespace App\Http\Requests\Promotions;

use App\Support\LegacyText;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

/**
 * anketo_cate_new.php / anketo_cate_update.php con act=up: los datos limpios con get_form (tras form2Db) y las
 * comprobaciones de anketo_cate_errchk.php. Como el legacy, los errores no redirigen: el formulario se vuelve a
 * pintar con sus mensajes (`legacyErrors()`), por eso `rules()` no valida nada.
 */
class AnketoCateRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }

    /** $data_array del legacy */
    public function legacyData(): array
    {
        return collect(['name', 'comment', 'start_date', 'end_date'])
            ->mapWithKeys(fn ($k) => [$k => LegacyText::form(LegacyText::db($this->input($k)))])->all();
    }

    /**
     * anketo_cate_errchk.php: nombre obligatorio; con fechas (アンケート管理) ambas obligatorias, válidas, el fin no en
     * el pasado y no anterior al inicio. お客様アンケート no tiene fechas (el legacy las dejó comentadas).
     *
     * @return array<string, string> campo => HTML del mensaje (como $error_disp)
     */
    public function legacyErrors(bool $withDates): array
    {
        $data = $this->legacyData();
        $errors = [];
        if ($data['name'] === '') {
            $errors['name'] = '<p class="m0">※必須項目です。</p>';
        }
        if (! $withDates) {
            return $errors;
        }

        $start = $this->day($data['start_date'], '開始', '00:00:00', $errors, 'start_date');
        $end = $this->day($data['end_date'], '終了', '23:59:59', $errors, 'end_date');

        if ($end !== null && $end->lessThan(Carbon::now())) {
            $errors['end_date'] = '<br />※終了年月日：'.e($data['end_date']).'は過去の日付です。';
            $end = null;
        }
        if ($start !== null && $end !== null && $end->lessThan($start)) {
            $errors['end_date'] = '<br />※終了年月日は開始年月日より前の日付です。';
        }

        return $errors;
    }

    private function day(string $value, string $label, string $time, array &$errors, string $field): ?Carbon
    {
        if ($value === '') {
            $errors[$field] = '<br />※'.$label.'年月日は必須項目です。';

            return null;
        }
        $parts = explode('/', $value);
        if (count($parts) !== 3 || ! checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0])) {
            $errors[$field] = '<br />※'.$label.'年月日：'.e($value).'は不適切な形式です。';

            return null;
        }
        [$h, $i, $s] = array_map('intval', explode(':', $time));

        return Carbon::create((int) $parts[0], (int) $parts[1], (int) $parts[2], $h, $i, $s, \App\Services\Promotions\LegacyTime::ZONE);
    }
}
