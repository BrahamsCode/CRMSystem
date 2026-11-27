<?php

namespace App\Http\Requests\Reservations;

use App\Services\Reservations\ReservationSettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** 機能設定 (yoyaku_kinou_config.php): 予約時間間隔 y 目安時間の表示 ('' = 表示する, '9' = 表示しない) */
class UpdateFeatureSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'time_interval' => ['required', Rule::in(ReservationSettingService::TIME_INTERVALS)],
            'meyasu_jikan_flg' => ['nullable', 'in:9'],
        ];
    }

    public function messages(): array
    {
        return [
            'time_interval.*' => '予約時間間隔を確認して下さい！',
            'meyasu_jikan_flg.*' => '目安時間の表示を確認して下さい！',
        ];
    }
}
