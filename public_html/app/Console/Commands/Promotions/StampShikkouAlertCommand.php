<?php

namespace App\Console\Commands\Promotions;

use App\Services\Promotions\StampExpiryMailer;
use Illuminate\Console\Command;

/**
 * スタンプ有効期限切れ前通知 (legacy category3/stamp/sendStampShikkouAlertMail.php). El Scheduler lo lanza a
 * admin_master.stamp_shikkou_alert_time (PromocionFidelizacionServiceProvider, F5).
 */
class StampShikkouAlertCommand extends Command
{
    protected $signature = 'promocion:stamp-shikkou-alert';

    protected $description = 'スタンプ有効期限切れ前通知メールを送る';

    public function handle(StampExpiryMailer $mailer): int
    {
        $result = $mailer->alert();
        $this->line(is_int($result) ? "事前通知: {$result}件" : $result);

        return self::SUCCESS;
    }
}
