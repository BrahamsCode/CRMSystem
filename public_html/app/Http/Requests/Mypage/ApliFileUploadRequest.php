<?php

namespace App\Http\Requests\Mypage;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Peticiones del JS de マイコレ a ajax_apliFileUpload.php. Solo comprueba la forma de los datos:
 * extensión, nombres temporales y huecos los valida AppImageUploader para devolver los mensajes
 * del legacy ({status: ng, errMsg}).
 */
class ApliFileUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string'],
            'file' => ['nullable', 'file'],
            'pic' => ['nullable'],
            'num' => ['nullable'],
            'dir' => ['nullable', 'string'],
            'target_url' => ['nullable', 'array'],
            'target_url.*' => ['nullable', 'string'],
            'mypage_config' => ['nullable', 'array'],
        ];
    }

    /** El JS espera JSON siempre, también cuando la validación falla */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 'ng',
            'errMsg' => $validator->errors()->first(),
        ]));
    }
}
