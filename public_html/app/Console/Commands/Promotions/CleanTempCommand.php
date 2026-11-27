<?php

namespace App\Console\Commands\Promotions;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Borra las imágenes temporales de más de un día de las subidas de category3.
 * Legacy: system/category3/index.php ejecutaba `find …/uploads/temp -mtime +1 -exec rm` al abrir la portada.
 * Las subidas viven en el disco `public` con la misma ruta relativa que en el legacy.
 */
class CleanTempCommand extends Command
{
    protected $signature = 'promocion:clean-temp';

    protected $description = 'Borra los temporales de subida de category3 con más de un día (mail_mag, auto, follow, coupon)';

    public const DIRECTORIES = [
        'system/category3/mail_mag/uploads/temp',
        'system/category3/auto/uploads/temp',
        'system/category3/follow/uploads/temp',
        'system/category3/coupon/uploads/temp',
    ];

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $limit = now()->subDay()->getTimestamp();
        $deleted = 0;

        foreach (self::DIRECTORIES as $directory) {
            foreach ($disk->files($directory) as $file) {
                if ($disk->lastModified($file) < $limit) {
                    $disk->delete($file);
                    $deleted++;
                }
            }
        }

        $this->info("Temporales borrados: {$deleted}");

        return self::SUCCESS;
    }
}
