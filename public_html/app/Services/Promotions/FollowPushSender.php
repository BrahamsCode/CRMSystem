<?php

namespace App\Services\Promotions;

use App\Models\Promotions\PushFollowMaster;
use App\Models\Promotions\PushMaster;
use App\Services\Nucleo\Hatuban;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * sendFollowPush.php (cron de 5 min): convierte en push_master las プッシュ programadas, フォロー y 自動 que tocan
 * (push_follow_master activo con next_send_date <= ahora) y avisa a g-api (`PushTrigger`) de cada una.
 *
 * Como el legacy:
 *   - la última pasada se guarda en contents_master (push_follow / last_push_date); la primera vez solo la anota;
 *   - una regla cuyo next_send_date es anterior a la última pasada (p. ej. el servidor estuvo parado) no se envía,
 *     solo se reprograma;
 *   - un programado queda disp_flg = 9 tras su pasada; フォロー y 自動 calculan su siguiente envío.
 * Corrección: el push generado copia `pic_link` (el legacy lo perdía y las imágenes salían sin enlace).
 */
class FollowPushSender
{
    /** int4 de push_master.point_expiration_date */
    private const MAX_EPOCH = 2147483647;

    public function __construct(
        private readonly PushSchedule $schedule,
        private readonly PushTrigger $trigger,
        private readonly Hatuban $hatuban,
    ) {}

    /** @return int|null push generados; null en la primera pasada (solo se anota la hora) */
    public function run(?Carbon $now = null): ?int
    {
        $now = ($now ?? Carbon::now())->copy()->setTimezone(LegacyTime::ZONE);
        $epoch = $now->getTimestamp();

        $last = DB::table('contents_master')->where('category', 'push_follow')->where('field', 'last_push_date')->first();
        if ($last) {
            DB::table('contents_master')->where('category', 'push_follow')->where('field', 'last_push_date')
                ->update(['int_data' => $epoch, 'update_date' => $epoch]);
        } else {
            DB::table('contents_master')->insert(['cd' => $epoch, 'category' => 'push_follow', 'field' => 'last_push_date',
                'int_data' => $epoch, 'disp_flg' => '1', 'touroku_date' => $epoch, 'update_date' => $epoch]);
        }
        $lastPushDate = (int) ($last->int_data ?? 0);
        if ($lastPushDate === 0) {
            return null;
        }

        $sent = 0;
        $rules = PushFollowMaster::query()->where('disp_flg', '1')->where('next_send_date', '<=', $epoch)->orderBy('cd')->get();
        foreach ($rules as $rule) {
            $cd = DB::transaction(function () use ($rule, $lastPushDate, $now) {
                $cd = null;
                if ((int) $rule->next_send_date > $lastPushDate) {
                    $cd = $this->createPush($rule, $now);
                }

                if ($rule->type_flg === 'scheduled') {
                    $rule->disp_flg = PushFollowMaster::DELETED_FLAG;
                } else {
                    $rule->next_send_date = $this->schedule->nextSendDate((string) $rule->type_flg, (string) $rule->send_time, $rule->send_parameter, $now);
                }
                $rule->timestamps = false;
                $rule->save();

                return $cd;
            });

            if ($cd !== null) {
                $this->trigger->trigger('push', $cd);
                $sent++;
            }
        }

        return $sent;
    }

    private function createPush(PushFollowMaster $rule, Carbon $now): int
    {
        $postPoint = (int) $rule->post_point;
        $expiration = null;
        if ($postPoint > 0) {
            if ($rule->type_flg === 'scheduled') {
                $date = PushSchedule::split($rule->send_parameter)['point_expiration_date'] ?? '';
                $expiration = PushSchedule::parseDate($date.' 23:59:59');
            } else {
                // Hasta el final del día «配信から N 日後»
                $expiration = $now->copy()->startOfDay()->addDays((int) $rule->point_expiration_days + 1)->getTimestamp() - 1;
            }
            $expiration = min($expiration ?? self::MAX_EPOCH, self::MAX_EPOCH);
        }

        $push = new PushMaster([
            'cd' => $this->hatuban->next('push_master'),
            'tb_shop_cd' => $rule->tb_shop_cd,
            'title' => $rule->title,
            'message' => $rule->message,
            'pic_link' => $rule->pic_link,
            'img_flg' => '1',
            'wherestr' => $rule->wherestr,
            'search_display' => $rule->search_display,
            'disp_flg' => '1',
            'version' => 2,
            'pos_search' => (string) $rule->pos_search,
            'pos_tb_shop_cd' => $rule->pos_tb_shop_cd ?: null,
            'post_type' => $postPoint > 0 ? PushMaster::POST_WITH_POINTS : PushMaster::POST_MESSAGE,
            'point_expiration_date' => $expiration,
            'post_point' => $postPoint > 0 ? $postPoint : null,
            'type_flg' => $rule->type_flg,
            'push_follow_master_cd' => $rule->cd,
        ]);
        $push->setCreatedAt($now->getTimestamp())->setUpdatedAt($now->getTimestamp());
        $push->timestamps = false;
        $push->save();

        return (int) $push->cd;
    }
}
