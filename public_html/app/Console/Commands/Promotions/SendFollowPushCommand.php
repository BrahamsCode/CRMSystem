<?php

namespace App\Console\Commands\Promotions;

use App\Services\Promotions\FollowPushSender;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * system/category3/push/sendFollowPush.php: プッシュ programadas, フォロー y 自動 (cada 5 minutos).
 * El legacy solo ponía la línea en el crontab con プッシュ通知 activo (cSendSchedule::getOtherSchedule,
 * tb_base_system_child.system_name = プッシュ通知 y system_flg = 1); aquí el Scheduler la lanza siempre
 * (PromocionServiceProvider::TASKS) y el comando no hace nada si el módulo está apagado.
 */
class SendFollowPushCommand extends Command
{
    protected $signature = 'promocion:send-follow-push';

    protected $description = 'Genera los push programados, de seguimiento y automáticos que tocan y avisa a g-api';

    public function handle(FollowPushSender $sender): int
    {
        if (! self::enabled()) {
            $this->info('プッシュ通知 desactivado: nada que hacer.');

            return self::SUCCESS;
        }

        $sent = $sender->run();
        $this->info($sent === null ? 'Primera pasada: solo se anota la hora (como 初回です del legacy).' : "Push generados: {$sent}");

        return self::SUCCESS;
    }

    /** ¿Está activa プッシュ通知? (condición de cSendSchedule para el cron) */
    public static function enabled(): bool
    {
        return DB::table('tb_base_system_child')->where('system_name', 'プッシュ通知')->where('system_flg', '1')->exists();
    }
}
