<?php

namespace App\Http\Requests\Promotions;

use App\Models\Promotions\PushFollowMaster;
use App\Services\Promotions\MailAudience;
use App\Services\Promotions\PushSchedule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * ajaxSavePush.php: comprobaciones del legacy. Como el legacy, los errores no son 422: se responde
 * `{"status": "error", "errorMsg": "…", "isFatal": …}` y push_new.php los muestra con jAlert (`legacyError()`),
 * por eso `rules()` no valida nada. El `sessId` del legacy lo sustituye el token CSRF.
 * Título y bloques se guardan tal cual, como el legacy (solo `pg_escape_string`); las pantallas los escapan.
 */
class PushSaveRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }

    /** null si todo está bien; si no, ['errorMsg' => …, 'isFatal' => bool] */
    public function legacyError(): ?array
    {
        if ((string) $this->input('act') === '') {
            return ['errorMsg' => '不正アクセス', 'isFatal' => false];
        }
        $id = '/^[1-9][0-9]*$/';
        if (($this->filled('testMemberCd') && ! preg_match($id, (string) $this->input('testMemberCd')))
            || ($this->filled('cd') && ! preg_match($id, (string) $this->input('cd')))
            || ($this->filled('type_flg') && ! in_array($this->input('type_flg'), PushSchedule::TYPES, true))
            || ! $this->scalars(['title', 'post_type', 'post_point', 'point_expiration_days', 'point_expiration_date', 'send_date',
                'send_hour', 'send_min', 'scheduled_flg', 'sendParameter', 'search_uniqid'])
            || ! $this->stringArrays(['contents', 'registeredFlg', 'pic_link'])) {
            return ['errorMsg' => '不正パラメーター', 'isFatal' => false];
        }

        $error = '';
        $fatal = false;
        $typeFlg = (string) $this->input('type_flg');

        if ($typeFlg !== '' && $this->filled('cd')
            && ! PushFollowMaster::query()->whereKey((int) $this->input('cd'))->whereIn('disp_flg', ['1', PushFollowMaster::PAUSED_FLAG])->exists()) {
            $error .= '通知データがありません。<br/>';
            $fatal = true;
        }
        // Alta sin búsqueda guardada (sesión caducada): el legacy registraba un push sin destinatarios (wherestr vacío)
        if (! $this->filled('testMemberCd') && ! $this->filled('cd') && ! app(MailAudience::class)->has((string) $this->input('search_uniqid'))) {
            $error .= '通知データがありません。<br/>';
            $fatal = true;
        }

        if ((string) $this->input('title') === '') {
            $error .= '通知タイトルを入力してください。<br/>';
        }

        $expiration = 0;
        if ((int) $this->input('post_type') === 2) {
            if ((int) $this->input('post_point') < 1) {
                $error .= '正しい付与ポイント数を入力してください。<br/>';
            }
            if (in_array($typeFlg, ['auto', 'follow'], true)) {
                if ($this->filled('point_expiration_days') && ! preg_match('/^[0-9]+$/', (string) $this->input('point_expiration_days'))) {
                    $error .= '正しいポイント獲得の有効期限を入力してください。<br/>';
                }
            } else {
                $expiration = (int) PushSchedule::parseDate($this->input('point_expiration_date').' 23:59:59');
                if ($expiration < time()) {
                    $error .= '正しいポイント獲得の有効期限を選択してください。<br/>';
                }
            }
        }

        if ($this->input('scheduled_flg') === '1' || $typeFlg === 'scheduled') {
            $sendTime = PushSchedule::parseDate($this->input('send_date').' '.$this->input('send_hour').':'.$this->input('send_min'));
            if ($sendTime === null || (string) $this->input('send_date') === '') {
                $error .= '正しい配信時間を選択してください';
            } elseif ($sendTime < time()) {
                $error .= '配信時間は過去です。';
            } elseif ($expiration && $expiration < $sendTime) {
                $error .= 'ポイント獲得の有効期限は配信時間より前です。';
            }
        }

        return $error === '' ? null : ['errorMsg' => $error, 'isFatal' => $fatal];
    }

    private function scalars(array $keys): bool
    {
        foreach ($keys as $key) {
            if ($this->has($key) && ! is_scalar($this->input($key)) && $this->input($key) !== null) {
                return false;
            }
        }

        return true;
    }

    private function stringArrays(array $keys): bool
    {
        foreach ($keys as $key) {
            $value = $this->input($key);
            if ($value === null) {
                continue;
            }
            if (! is_array($value)) {
                return false;
            }
            foreach ($value as $item) {
                if ($item !== null && ! is_scalar($item)) {
                    return false;
                }
            }
        }

        return true;
    }
}
