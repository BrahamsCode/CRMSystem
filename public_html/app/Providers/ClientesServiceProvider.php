<?php

namespace App\Providers;

use App\Services\Customers\CustomerPhotoImageStore;
use App\Services\Customers\NullPosGateway;
use App\Services\Customers\NullVisitLoyalty;
use App\Services\Customers\PosGateway;
use App\Services\Customers\VisitLoyalty;
use App\Console\Commands\Clientes\AssignRanks;
use App\Models\ContentsMaster;
use App\Services\Customers\RankAssigner;
use App\Services\Customers\RankService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Módulos de S2 (clientes, ventas, reservas): enlaces de interfaces y, más adelante, tareas del Scheduler.
 */
class ClientesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // POS fuera de alcance (CONVENCIONES §4)
        $this->app->bindIf(PosGateway::class, NullPosGateway::class);

        // Sellos y puntos de la visita: S3 enlaza su implementación; hasta entonces, nula
        $this->app->bindIf(VisitLoyalty::class, NullVisitLoyalty::class);
    }

    public function boot(): void
    {
        // cUtility::isExtShopDomain(): ポイント管理/ポイント履歴 del menú de clientes
        Gate::define('clientes.ext-shop.enabled', fn ($staff = null) => ContentsMaster::text('mycolle', 'app_layout_mode') === '1');

        $this->scheduleRankAssignment();
        $this->scheduleTempCleanup();
    }

    /**
     * category1/index.php borraba en cada visita, con `find … -mtime +1 -exec rm`, los temporales de subida de más de
     * un día (uploads/ de la マイアルバム; uploads/temp/ era de la CSVインポート, que aquí ya no deja temporales).
     */
    private function scheduleTempCleanup(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->call(fn () => $this->app->make(CustomerPhotoImageStore::class)->cleanOldTemps())
                ->name('clientes:limpiar-temporales')
                ->hourly()
                ->withoutOverlapping();
        });
    }

    /**
     * 顧客ランク振り分け: el legacy escribía en el crontab `min hora * * * setCustomer<Tipo>Rank.php` con la hora de
     * admin_master.<tipo>_rank_shori_time (cSendSchedule). Aquí la tarea se evalúa cada minuto y solo corre a esa
     * hora (leída al ejecutar schedule:run, no al arrancar); el comando comprueba después el día, como el script.
     */
    private function scheduleRankAssignment(): void
    {
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            foreach (array_keys(RankService::MODELS) as $type) {
                $schedule->command(AssignRanks::class, ['--tipo' => $type])
                    ->everyMinute()
                    ->when(fn () => $this->app->make(RankAssigner::class)->isScheduledAt($type, now()))
                    ->withoutOverlapping();
            }
        });
    }
}
