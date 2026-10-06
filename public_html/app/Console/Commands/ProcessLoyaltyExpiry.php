<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\CustomerPoint;
use App\Models\CustomerStamp;
use App\Models\PointSetting;
use App\Models\StampSetting;
use App\Services\Promotions\LoyaltyService;
use App\Services\Promotions\MessageDispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Vence sellos y puntos caducados y envía los avisos previos al vencimiento.
 *
 * El vencimiento es idempotente (ver LoyaltyService::expire), así que puede
 * correr con frecuencia. Los avisos se envían en la franja de 30 minutos que
 * empieza a la hora configurada y se marcan para no repetirse en el día.
 */
class ProcessLoyaltyExpiry extends Command
{
    protected $signature = 'promotions:loyalty {--now= : Fecha y hora a simular (pruebas)}';

    protected $description = 'Vence sellos y puntos caducados y avisa antes de que venzan';

    public function handle(LoyaltyService $loyalty, MessageDispatcher $dispatcher): int
    {
        $now = $this->option('now') ? Carbon::parse($this->option('now')) : now();

        $this->line($loyalty->expire($now) . ' clientes con vencimientos');

        $sent = 0;

        foreach (PointSetting::active()->where('expiry_flg', 1)->get() as $setting) {
            foreach ($setting->notices ?? [] as $i => $notice) {
                if ($this->inWindow($now, (int) ($notice['hour'] ?? 10), (int) ($notice['minute'] ?? 0), "points:{$setting->id}:{$i}")) {
                    $sent += $this->notify(CustomerPoint::class, 'points', $setting->shop_id, $now->copy()->addDays((int) $notice['days']), $dispatcher,
                        'Tus puntos vencen pronto', "Hola {nombre_completo}, tienes {puntos} puntos y parte vence el %s. ¡Aprovéchalos!");
                }
            }
        }

        foreach (StampSetting::active()->where('expiry_flg', 1)->where('notice_flg', 1)->whereNotNull('notice_days')->get() as $setting) {
            if ($this->inWindow($now, $setting->notice_hour, $setting->notice_minute, "stamps:{$setting->id}")) {
                $sent += $this->notify(CustomerStamp::class, 'quantity', $setting->shop_id, $now->copy()->addDays($setting->notice_days), $dispatcher,
                    'Tus sellos vencen pronto', "Hola {nombre_completo}, tienes {sellos} sellos y parte vence el %s.");
            }
        }

        $this->line("{$sent} avisos de vencimiento");

        return self::SUCCESS;
    }

    private function inWindow(Carbon $now, int $hour, int $minute, string $key): bool
    {
        $start = $now->copy()->setTime($hour, $minute);

        if ($now->lessThan($start) || $now->greaterThanOrEqualTo($start->copy()->addMinutes(30))) {
            return false;
        }

        // add() solo escribe si la clave no existe: un aviso por día
        return Cache::add("loyalty-notice:{$key}:" . $now->toDateString(), true, now()->addDay());
    }

    private function notify(string $model, string $column, int $shopId, Carbon $day, MessageDispatcher $dispatcher, string $subject, string $body): int
    {
        $ids = $model::where('shop_id', $shopId)->where($column, '>', 0)
            ->whereDate('expires_at', $day)
            ->distinct()->pluck('customer_id');

        $n = 0;
        foreach (Customer::whereIn('id', $ids)->get() as $customer) {
            if ($dispatcher->notify($customer, $subject, sprintf($body, $day->format('d/m/Y')))) {
                $n++;
            }
        }

        return $n;
    }
}
