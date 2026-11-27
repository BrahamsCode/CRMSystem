<?php

namespace App\Services\Promotions;

use App\Models\AdminMaster;
use App\Models\Promotions\PushFollowMaster;

/**
 * Datos de la pantalla プッシュ通知新規作成 (push_new.php): `$formValues` del legacy, los bloques ya guardados
 * (edición de un programado / フォロー / 自動) y las etiquetas de sustitución que se listan arriba.
 */
class PushForm
{
    public function __construct(
        private readonly PushSchedule $schedule,
        private readonly MailAudience $audience,
        private readonly MailReplaceTags $tags,
    ) {}

    /** Edición: valores de push_follow_master */
    public function fromFollow(PushFollowMaster $follow): array
    {
        [$hour, $min] = array_pad(explode(':', (string) $follow->send_time), 2, null);
        $scheduled = $follow->type_flg === 'scheduled';
        $params = PushSchedule::split($follow->send_parameter);
        $message = @unserialize((string) $follow->message, ['allowed_classes' => false]);

        return [
            'searchDisplay' => (string) $follow->search_display,
            'sendHour' => $hour,
            'sendMin' => $min,
            'postType' => (int) $follow->post_point > 0 ? 2 : 1,
            'postPoint' => $follow->post_point,
            'sendDate' => $scheduled ? ($params['send_date'] ?? '') : '',
            'pointExpirationDate' => $scheduled ? ($params['point_expiration_date'] ?? '') : '',
            'pointExpirationDays' => $scheduled ? '' : $follow->point_expiration_days,
            'sendParameter' => $scheduled ? '' : (string) $follow->send_parameter,
            'title' => (string) $follow->title,
            'message' => is_array($message) ? $message : null,
        ];
    }

    /**
     * Alta: lo que llega del paso anterior. Desde フォロー/自動 (follow_new_confirm, auto mail_customer_select con
     * kind = followPush/autoPush) llegan step1/step2_* y la hora step2_jikan/step2_fun, que forman el `send_parameter`.
     */
    public function fromRequest(array $in): array
    {
        $type = (string) ($in['type_flg'] ?? '');
        if (array_key_exists('sendParameter', $in)) {
            $sendParameter = (string) $in['sendParameter'];
        } elseif ($type === 'follow') {
            $sendParameter = ($in['shop_cd'] ?? '').'<>'.($in['step1'] ?? '').'<>'.($in['step2_1'] ?? '');
        } elseif ($type === 'auto') {
            $sendParameter = ($in['step1'] ?? '').'<>'.(($in['step1'] ?? '') === '1'
                ? ($in['step2_1'] ?? '').'<>'.($in['step2_2'] ?? '')
                : ($in['step2_3'] ?? ''));
        } else {
            $sendParameter = '';
        }
        $uniqid = (string) ($in['search_uniqid'] ?? '');

        return [
            'searchDisplay' => $this->audience->has($uniqid) ? $this->audience->display($uniqid) : '',
            'sendDate' => $in['send_date'] ?? '',
            'sendHour' => ($in['step2_jikan'] ?? '') !== '' ? $in['step2_jikan'] : ($in['send_hour'] ?? ''),
            'sendMin' => ($in['step2_fun'] ?? '') !== '' ? $in['step2_fun'] : ($in['send_min'] ?? ''),
            'postType' => $in['post_type'] ?? '',
            'pointExpirationDays' => $in['point_expiration_days'] ?? '',
            'pointExpirationDate' => $in['point_expiration_date'] ?? '',
            'postPoint' => $in['post_point'] ?? '',
            'title' => $in['title'] ?? '',
            'message' => is_array($in['contents'] ?? null) ? array_values(array_filter($in['contents'], 'is_string')) : null,
            'sendParameter' => $sendParameter,
        ];
    }

    /**
     * Bloques para el JS de push_new.js: [['img', nombre, '1'|''], ['text', texto]]. Una imagen se marca
     * «registrada» (en hp/uploads) salvo que la pantalla venga de un envío del propio formulario.
     */
    public function blocks(?array $message, array $in = []): array
    {
        $blocks = [];
        foreach ((array) $message as $key => $value) {
            $value = (string) $value;
            if (preg_match(PushImageStore::IMAGE_PATTERN, $value)) {
                $registered = ! isset($in['send_push']) || (($in['registeredFlg'][$key] ?? '') === '1');
                $blocks[] = ['img', $value, $registered ? '1' : ''];
            } else {
                $blocks[] = ['text', $value];
            }
        }

        return $blocks;
    }

    /**
     * Etiquetas de push_new.php: las de mail_frm sin las de rango (comentadas en el legacy) ni las palabras
     * añadidas (`$wordList` no se cargaba en push_new.php), más {日後:●●} y {アンケートURL[n]} en la vista.
     */
    public function tags(): array
    {
        $form = $this->tags->forMailForm('push', $this->toolName());

        return [
            'tags' => array_values(array_filter($form['tags'], fn ($t) => ! in_array($t['word'], ['使用金額ランク', '来店回数ランク'], true))),
            'survey' => $form['survey'],
        ];
    }

    /** $tool_name['mail_tool_name']: お知らせ con la app activa, si no メール */
    public function toolName(): string
    {
        return (int) AdminMaster::current()?->application_use_flg === 1 ? 'お知らせ' : 'メール';
    }

    public function describeAuto(?string $sendParameter): string
    {
        return $this->schedule->describe($sendParameter);
    }
}
