<?php

namespace App\Services\Mypage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Imágenes de la app マイコレ en el disco `public`, con la misma ruta relativa que el legacy:
 * `hp/uploads/c6/<carpeta>/<n>.<ext>` (y `<n>_s.<ext>` para la miniatura de la メインイメージ).
 *
 * Las subidas pendientes de guardar van a `hp/uploads/c6/apli_temp/` (el legacy usaba
 * system/category6/user_hp/uploads/temp/). Se limpian los archivos de más de una semana en cada
 * llamada al endpoint, como el legacy.
 */
class AppImageFiles
{
    public const BASE = 'hp/uploads/c6';

    public const TEMP_FOLDER = 'apli_temp';

    public const COVER = 'aplicover';

    public const BANNER = 'aplibanner';

    public const HOME_IMAGE = 'apli_home_image';

    public const HOME_THUMBNAIL = 'apli_home_thumbnail';

    /** Nombre de archivo temporal válido (lo genera storeTemp) */
    public const TEMP_NAME_PATTERN = '/^\d+_[A-Za-z0-9]+\.(jpg|gif)$/';

    public function disk(): Filesystem
    {
        return Storage::disk('public');
    }

    public function folderPath(string $folder): string
    {
        return self::BASE . '/' . $folder;
    }

    /**
     * Archivos numerados de la carpeta: [n => ruta relativa], ordenados por n.
     * Ignora los que no son `<n>.<ext>` (p. ej. `3_s.jpg`).
     */
    public function numbered(string $folder): array
    {
        $files = [];
        foreach ($this->disk()->files($this->folderPath($folder)) as $path) {
            $name = basename($path);
            $base = strstr($name, '.', true);
            if ($base !== false && ctype_digit($base)) {
                $files[(int) $base] = $path;
            }
        }
        ksort($files);

        return $files;
    }

    /** URL pública con `?t=<mtime>` como el legacy (rompe la caché del navegador) */
    public function url(string $path): string
    {
        return asset($path) . '?t=' . $this->disk()->lastModified($path);
    }

    public function extension(string $path): string
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    /** Borra `<n>.*` (y `<n>_s.*` si $withSmall) de la carpeta */
    public function deleteNumber(string $folder, int $number, bool $withSmall = false): void
    {
        foreach ($this->disk()->files($this->folderPath($folder)) as $path) {
            $base = strstr(basename($path), '.', true);
            if ($base === (string) $number || ($withSmall && $base === $number . '_s')) {
                $this->disk()->delete($path);
            }
        }
    }

    public function write(string $folder, string $name, string $contents): string
    {
        $path = $this->folderPath($folder) . '/' . $name;
        $this->disk()->put($path, $contents);

        return $path;
    }

    // --- Temporales -------------------------------------------------------------------------

    public function tempUrl(): string
    {
        return asset($this->folderPath(self::TEMP_FOLDER)) . '/';
    }

    /** Guarda una subida procesada y devuelve su nombre (`<epoch>_<aleatorio>.<ext>`) */
    public function storeTemp(string $contents, string $extension): string
    {
        $name = time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $this->write(self::TEMP_FOLDER, $name, $contents);

        return $name;
    }

    /** Contenido de un temporal, o null si el nombre no es válido o no existe */
    public function readTemp(string $name): ?string
    {
        if (! preg_match(self::TEMP_NAME_PATTERN, $name)) {
            return null;
        }
        $path = $this->folderPath(self::TEMP_FOLDER) . '/' . $name;

        return $this->disk()->exists($path) ? $this->disk()->get($path) : null;
    }

    /** Borra los temporales de más de una semana (el legacy lo hacía en cada llamada) */
    public function cleanTemp(): void
    {
        $limit = now()->subWeek()->getTimestamp();
        foreach ($this->disk()->files($this->folderPath(self::TEMP_FOLDER)) as $path) {
            if ($this->disk()->lastModified($path) < $limit) {
                $this->disk()->delete($path);
            }
        }
    }
}
