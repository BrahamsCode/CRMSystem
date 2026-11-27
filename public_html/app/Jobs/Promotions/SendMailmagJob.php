<?php

namespace App\Jobs\Promotions;

use App\Services\Promotions\MailSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Envío inmediato de una お知らせ (el legacy lanzaba mail_mag/mailsoushin.php en segundo plano con exec).
 */
class SendMailmagJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $mailmagCd) {}

    public function handle(MailSender $sender): void
    {
        $sender->send($this->mailmagCd);
    }
}
