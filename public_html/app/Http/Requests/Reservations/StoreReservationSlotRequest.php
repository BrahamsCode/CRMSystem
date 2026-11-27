<?php

namespace App\Http\Requests\Reservations;

use App\Http\Requests\Reservations\Concerns\ValidatesSlotTimes;
use App\Models\Staff;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta/edición de la franja de un día (yoyaku_uketsuke_day.php → yoyaku_uketsuke_register.php).
 * `act` = 'new' (necesita staff + yoyaku_date 'Ymd…') o 'up' (necesita cd).
 */
class StoreReservationSlotRequest extends FormRequest
{
    use ValidatesSlotTimes;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'act' => ['required', 'in:new,up'],
            'cd' => ['required_if:act,up', 'nullable', 'integer'],
            'staff' => ['required', 'integer'],
            'yoyaku_date' => ['required_if:act,new', 'nullable', 'regex:/^[0-9]{8}/'],
            'kijun_time' => ['nullable', 'string'],
            'getData' => ['nullable', 'string'],
        ] + $this->slotTimeRules();
    }

    /** La tienda es la del empleado (`tb_staff.shop_cd`, sin mirar disp_flg como el legacy) */
    protected function hoursShopCd(): ?int
    {
        $shopCd = Staff::withDeleted()->whereKey((int) $this->input('staff'))->value('shop_cd');

        return $shopCd === null ? null : (int) $shopCd;
    }
}
