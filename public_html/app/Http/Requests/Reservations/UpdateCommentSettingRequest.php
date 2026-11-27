<?php

namespace App\Http\Requests\Reservations;

use App\Services\Reservations\ReservationSettingService;
use Illuminate\Foundation\Http\FormRequest;

/** 注釈設定 (yoyaku_comment_config.php): textarea `comment_<cd>`; vacío permitido (2012.09.10) */
class UpdateCommentSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return collect(ReservationSettingService::COMMENT_FIELDS)
            ->keys()
            ->mapWithKeys(fn ($cd) => ['comment_' . $cd => ['nullable', 'string']])
            ->all();
    }
}
