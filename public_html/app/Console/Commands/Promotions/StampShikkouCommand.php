<?php

namespace App\Console\Commands\Promotions;

use App\Services\Promotions\StampExpiryMailer;
use Illuminate\Console\Command;

/**
 * スタンプ失効 (legacy category3/stamp/sendStampShikkouMail.php): aviso, tb_stamp_log y puesta a cero de los sellos
 * caducados. El Scheduler lo lanza a admin_master.stamp_shikkou_time (PromocionFidelizacionServiceProvider, F5).
 */
class StampShikkouCommand extends Command
{
    protected $signature = 'promocion:stamp-shikkou';

    protected $description = 'スタンプ失効処理: 失効通知メールを送り、期限切れのスタンプをリセットする';

    public function handle(StampExpiryMailer $mailer): int
    {
        $result = $mailer->expire();
        $this->line(is_int($result) ? "失効処理: {$result}件" : $result);

        return self::SUCCESS;
    }
}
