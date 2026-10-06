<?php

namespace App\Console\Commands;

use App\Services\Promotions\RuleRunner;
use Illuminate\Console\Command;

/** Ejecuta los envíos automáticos a los que les toca su hora */
class RunMessageRules extends Command
{
    protected $signature = 'promotions:run-rules';

    protected $description = 'Ejecuta los envíos automáticos (seguimiento, programados y recordatorios)';

    public function handle(RuleRunner $runner): int
    {
        $this->line($runner->runDue() . ' reglas ejecutadas');

        return self::SUCCESS;
    }
}
