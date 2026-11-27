<?php

namespace App\Http\Requests\Reservations;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * 各店舗設定 (yoyaku_config.php). El legacy solo validaba en JS (submitChk): mismos textos aquí.
 */
class UpdateShopSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shop_cd' => ['required', 'integer'],
            'start_time1' => ['required', 'regex:/^([01]\d|2[0-3])$/'],
            'start_time2' => ['required', 'in:00,30'],
            'end_time1' => ['required', 'regex:/^([01]\d|2[0-9])$/'],
            'end_time2' => ['required', 'in:00,30'],
            'shimekiri' => ['nullable', 'integer', 'between:0,30'],
            'shimekiri_time' => ['nullable', 'integer', 'between:1,23'],
            'uketuke_type' => ['nullable', 'in:1'],
            'timeline_sort_type' => ['nullable', 'in:1'],
            'setsubi_capacity' => ['nullable', 'integer'],
            'automail' => ['nullable', 'integer', 'between:1,7'],
            'automail_time1' => ['nullable', 'regex:/^([01]\d|2[0-3])$/'],
            'automail_time2' => ['nullable', 'in:00,15,30,45'],
        ];
    }

    public function messages(): array
    {
        return [
            'shop_cd.*' => '店舗を選択して下さい！',
            'start_time1.*' => '開始時間を選択して下さい！',
            'start_time2.*' => '開始時間を選択して下さい！',
            'end_time1.*' => '終了時間を選択して下さい！',
            'end_time2.*' => '終了時間を選択して下さい！',
            'setsubi_capacity.*' => 'キャパを確認して下さい！',
            'shimekiri.*' => '〆切日を確認して下さい！',
            'shimekiri_time.*' => '〆切日を確認して下さい！',
            'uketuke_type.*' => '営業時間中の予約受付を確認して下さい！',
            'timeline_sort_type.*' => '予約タイムラインの表示方法を確認して下さい！',
            'automail.*' => 'リマインダーメール送信時間を選択して下さい！',
            'automail_time1.*' => 'リマインダーメール送信時間を選択して下さい！',
            'automail_time2.*' => 'リマインダーメール送信時間を選択して下さい！',
        ];
    }

    /** startTime >= endTime → 時間を確認して下さい！ (comparación de texto 'HHMM', como el JS del legacy) */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }
                $start = $this->input('start_time1') . $this->input('start_time2');
                $end = $this->input('end_time1') . $this->input('end_time2');
                if (strcmp($start, $end) >= 0) {
                    $validator->errors()->add('end_time1', '時間を確認して下さい！');
                }
            },
        ];
    }
}
