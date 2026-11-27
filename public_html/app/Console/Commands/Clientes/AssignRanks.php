<?php

namespace App\Console\Commands\Clientes;

use App\Services\Customers\RankAssigner;
use App\Services\Customers\RankScheduleException;
use App\Services\Customers\RankService;
use Illuminate\Console\Command;

/**
 * Reparto de rangos de cliente (legacy: category1/rank/setCustomerKingakuRank.php / setCustomerRaitenRank.php).
 * Como los scripts del legacy, solo reparte si hoy es uno de los días de `admin_master.<tipo>_rank_shori_date`.
 * ClientesServiceProvider lo programa a la hora de `<tipo>_rank_shori_time`.
 */
class AssignRanks extends Command
{
    protected $signature = 'clientes:asignar-rangos {--tipo= : kingaku (使用金額ランク) o raiten (来店回数ランク)}';

    protected $description = 'Reparte los rangos de cliente por importe o por visitas (顧客ランク振り分け)';

    public function handle(RankAssigner $assigner): int
    {
        $type = (string) $this->option('tipo');
        if (! array_key_exists($type, RankService::MODELS)) {
            $this->error('--tipo debe ser kingaku o raiten');

            return self::INVALID;
        }

        $today = now();

        try {
            if (! $assigner->isDueOn($type, $today)) {
                $this->line('Hoy no toca el reparto de rangos.');

                return self::SUCCESS;
            }

            foreach ($assigner->assign($type, $today) as $rank) {
                $this->line("Rango: {$rank['name']}  clientes: {$rank['count']}");
            }
        } catch (RankScheduleException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
