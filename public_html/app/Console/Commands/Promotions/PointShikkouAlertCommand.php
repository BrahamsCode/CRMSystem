<?php

namespace App\Console\Commands\Promotions;

use App\Services\Promotions\PointExpiryMailer;
use Illuminate\Console\Command;

/**
 * ポイント有効期限切れ前通知 (legacy category3/point/sendPointShikkouAlertMail.php y, con avisos de
 * point_shikkou_alert, sendPointShikkouAlertMailMultiple.php <cd>).
 * Sin opciones: el aviso de admin_master. Con --cd: ese aviso múltiple. Con --due: los avisos múltiples cuya hora es ahora
 * (así lo lanza el Scheduler cada minuto, en vez de una línea de crontab por aviso).
 */
class PointShikkouAlertCommand extends Command
{
    protected $signature = 'promocion:point-shikkou-alert {--cd= : point_shikkou_alert.cd} {--due : avisos múltiples de esta hora}';

    protected $description = 'ポイント有効期限切れ前通知メールとプッシュ通知を送る';

    public function handle(PointExpiryMailer $mailer): int
    {
        if ($this->option('due')) {
            $cds = $mailer->dueAlertCds(now());
        } elseif ($this->option('cd') !== null) {
            $cds = [(int) $this->option('cd')];
        } else {
            $result = $mailer->alert();
            $this->line(is_int($result) ? "事前通知: {$result}件" : $result);

            return self::SUCCESS;
        }

        foreach ($cds as $cd) {
            $result = $mailer->alertMultiple($cd);
            $this->line(is_int($result) ? "事前通知 {$cd}: {$result}件" : "事前通知 {$cd}: {$result}");
        }

        return self::SUCCESS;
    }
}
