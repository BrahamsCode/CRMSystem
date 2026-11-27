<?php

namespace App\Http\Requests\Reservations;

use App\Http\Requests\Reservations\Concerns\ValidatesSlotTimes;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta de varios días (yoyaku_multi_uketsuke_day.php → yoyaku_multi_uketsuke_register.php).
 * `days[]` en 'Y/m/d' como los checkbox de time_table_staff.php.
 */
class BulkStoreReservationSlotRequest extends FormRequest
{
    use ValidatesSlotTimes;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shop' => ['required', 'integer'],
            'staff' => ['required', 'integer'],
            'days' => ['required', 'array'],
            'days.*' => ['required', 'date_format:Y/m/d'],
            'd' => ['nullable', 'string'],
            'bd' => ['nullable', 'string'],
        ] + $this->slotTimeRules();
    }

    protected function hoursShopCd(): ?int
    {
        return (int) $this->input('shop');
    }
}
