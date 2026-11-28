<?php

namespace App\Console\Commands\Promotions;

use App\Services\Promotions\PointExpiryMailer;
use Illuminate\Console\Command;

/**
 * ポイント失効 (legacy category3/point/sendPointShikkouMail.php): tb_point_log y puesta a cero de los puntos
 * caducados. El Scheduler lo lanza a admin_master.point_shikkou_time (PromocionFidelizacionServiceProvider).
 */
class PointShikkouCommand extends Command
{
    protected $signature = 'promocion:point-shikkou';

    protected $description = 'ポイント失効処理: 期限切れのポイントをリセットする';

    public function handle(PointExpiryMailer $mailer): int
    {
        $result = $mailer->expire();
        $this->line(is_int($result) ? "失効処理: {$result}件" : $result);

        return self::SUCCESS;
    }
}
