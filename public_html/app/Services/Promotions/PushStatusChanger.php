<?php

namespace App\Services\Promotions;

use App\Models\Promotions\PushFollowMaster;

/**
 * ajaxChangeStatus.php: 停止 (disp_flg 7), 起動 (1, con su próximo envío recalculado) y 解除 (9) de una
 * プッシュ programada / フォロー / 自動. Al parar o anular, next_send_date queda NULL, como el legacy.
 */
class PushStatusChanger
{
    /** Acción (texto que envía el JS del legacy) => disp_flg */
    public const ACTIONS = ['停止' => PushFollowMaster::PAUSED_FLAG, '起動' => '1', '解除' => '9'];

    public function __construct(private readonly PushSchedule $schedule) {}

    /** null si se cambió; si no, el mensaje de error del legacy */
    public function change(mixed $cd, mixed $action): ?string
    {
        if (! is_string($cd) || ! preg_match('/^[1-9][0-9]*$/', $cd) || ! is_string($action) || ! isset(self::ACTIONS[$action])) {
            return '不正パラメーター';
        }

        $follow = PushFollowMaster::query()->whereKey((int) $cd)->whereIn('disp_flg', ['1', PushFollowMaster::PAUSED_FLAG])->first();
        if (! $follow) {
            return 'データがありません';
        }

        $follow->disp_flg = self::ACTIONS[$action];
        $follow->next_send_date = $action === '起動'
            ? $this->schedule->nextSendDate((string) $follow->type_flg, (string) $follow->send_time, $follow->send_parameter)
            : null;
        // El legacy no tocaba update_date al cambiar el estado
        $follow->timestamps = false;
        $follow->save();

        return null;
    }
}
