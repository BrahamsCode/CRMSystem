<?php

namespace App\Http\Requests\Reservations\Concerns;

use App\Models\Reservations\ReservationConfig;
use App\Services\Reservations\ReservationSlotService;
use Illuminate\Validation\Validator;

/**
 * Validación común de 受付日設定 (un día y varios días): la misma que hace submitChk() en el JS del legacy,
 * con los mismos textos, repetida en el servidor.
 */
trait ValidatesSlotTimes
{
    /** Tienda cuyo horario (`tb_yoyaku_config`) limita la franja */
    abstract protected function hoursShopCd(): ?int;

    protected function slotTimeRules(): array
    {
        return [
            'start_hour' => ['required', 'regex:/^[0-9]{1,2}$/'],
            'start_min' => ['required', 'regex:/^[0-9]{1,2}$/'],
            'end_hour' => ['required', 'regex:/^[0-9]{1,2}$/'],
            'end_min' => ['required', 'regex:/^[0-9]{1,2}$/'],
            'people' => ['required', 'regex:/^[0-9]+$/', 'max:9'],
            'startBreakTime' => ['nullable', 'array', 'max:10'],
            'startBreakTime.*' => ['nullable', 'regex:/^[0-9]{0,2}:[0-9]{0,2}$/'],
            'endBreakTime' => ['nullable', 'array', 'max:10'],
            'endBreakTime.*' => ['nullable', 'regex:/^[0-9]{0,2}:[0-9]{0,2}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'people.required' => '「最大受付人数」が未入力です。',
            'people.regex' => '「最大受付人数」の入力内容が不適切です。',
            'people.max' => '「最大受付人数」の入力内容が不適切です。',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }
                // El JS compara 'HH:MM' como texto; con horas de dos cifras equivale a comparar segundos
                $start = sprintf('%02d:%02d', $this->input('start_hour'), $this->input('start_min'));
                $end = sprintf('%02d:%02d', $this->input('end_hour'), $this->input('end_min'));

                if (strcmp($start, $end) >= 0) {
                    $validator->errors()->add('end_hour', '開始時間と終了時間を確認してください。');
                }

                // Sin 予約設定 el JS del legacy no dejaba guardar nunca (comparaba con ''); aquí solo se limita si existe
                $config = ReservationConfig::query()->where('tb_shop_cd', $this->hoursShopCd())->first();
                if ($config && (strcmp($start, (string) $config->start_time) < 0 || strcmp($end, (string) $config->end_time) > 0)) {
                    $validator->errors()->add('start_hour', '出勤時間は' . $config->start_time . 'から' . $config->end_time . 'までの間にしてください。');
                }

                foreach ((array) $this->input('startBreakTime', []) as $key => $breakStart) {
                    $breakEnd = (string) ($this->input('endBreakTime')[$key] ?? '');
                    if ((string) $breakStart === '' && $breakEnd === '') {
                        continue;
                    }
                    if (strcmp((string) $breakStart, $breakEnd) >= 0) {
                        $validator->errors()->add('startBreakTime', '休憩・外出時間は異常です。(' . $breakStart . ' ～ ' . $breakEnd . ')');
                        break;
                    }
                }
            },
        ];
    }

    /**
     * Horas y descansos listos para guardar.
     *
     * @return array{start_time: int, end_time: int, people: int, start_break_time: string, end_break_time: string}
     */
    public function slotTimes(): array
    {
        [$breakStart, $breakEnd] = ReservationSlotService::breakTimes(
            (array) $this->input('startBreakTime', []),
            (array) $this->input('endBreakTime', []),
        );

        return [
            'start_time' => ReservationSlotService::toSeconds($this->input('start_hour'), $this->input('start_min')),
            'end_time' => ReservationSlotService::toSeconds($this->input('end_hour'), $this->input('end_min')),
            'people' => (int) $this->input('people'),
            'start_break_time' => $breakStart,
            'end_break_time' => $breakEnd,
        ];
    }
}
