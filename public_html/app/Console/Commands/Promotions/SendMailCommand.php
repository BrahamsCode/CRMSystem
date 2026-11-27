<?php

namespace App\Console\Commands\Promotions;

use App\Models\Promotions\MailmagMaster;
use App\Services\Promotions\MailSender;
use App\Services\Promotions\SendSchedulePlanner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Envíos programados de お知らせ (legacy mail_mag/mail_sche_send.php, lanzado por el crontab que
 * reescribía cSendSchedule::createSchedule). Corre cada minuto:
 * - envío único: sale cuando su `配信時間` ya llegó; su fila de send_schedule se borra antes de enviar;
 * - follow/auto/recordatorios: salen en su HH:MM (auto, además, en sus meses/días o días de la semana).
 *
 * Con `cd` envía esa お知らせ ya (mail_mag/mailsoushin.php).
 */
class SendMailCommand extends Command
{
    protected $signature = 'promocion:send-mail {cd? : cd de mailmag_master para enviar ahora}';

    protected $description = 'Envía las お知らせ (メルマガ, デコメール, フォロー, 自動, リマインダ) que tocan en este minuto';

    public function handle(MailSender $sender, SendSchedulePlanner $planner): int
    {
        if ($this->argument('cd') !== null) {
            $sender->send((int) $this->argument('cd'));

            return self::SUCCESS;
        }

        $now = now();
        $schedules = DB::table('send_schedule as s')
            ->join('mailmag_master as m', 's.mag_cd', '=', 'm.cd')
            ->where('s.flg', '1')
            ->whereRaw("COALESCE(m.disp_flg, '') = ''")
            ->orderBy('m.配信日時')
            ->get(['s.cd', 's.mag_cd', 's.配信時間', 'm.type_flg', 'm.send_parameter', 'm.配信状況']);

        foreach ($schedules as $schedule) {
            $type = trim((string) $schedule->type_flg);
            if (! $planner->isDue($type, $schedule->{'配信時間'}, $schedule->send_parameter, $now)) {
                continue;
            }

            if (! in_array($type, MailmagMaster::RECURRING_TYPES, true)) {
                // Envío único: se reserva borrando su fila; si otro proceso ya la borró, no se repite
                if ($schedule->{'配信状況'} !== MailmagMaster::STATUS_PENDING
                    || DB::table('send_schedule')->where('cd', $schedule->cd)->delete() === 0) {
                    continue;
                }
            }

            try {
                $sender->send((int) $schedule->mag_cd, $now);
            } catch (\Throwable $e) {
                report($e);
                $this->error("mailmag_master {$schedule->mag_cd}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
