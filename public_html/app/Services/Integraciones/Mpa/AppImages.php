<?php

namespace App\Services\Integraciones\Mpa;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Imágenes de la app マイページ en el disco public (`hp/uploads/c6/...`, misma ruta relativa que el legacy)
 * vistas como las veían los mpa_*: is_file, filemtime y glob('<dir>*') (orden alfabético, sin ocultos).
 */
class AppImages
{
    public function exists(string $path): bool
    {
        return Storage::disk('public')->fileExists($path);
    }

    /** filemtime() o '' si no existe (el `@filemtime` del legacy daba false) */
    public function mtime(string $path): string
    {
        return $this->exists($path) ? (string) Storage::disk('public')->lastModified($path) : '';
    }

    /** `http://<HTTP_HOST>` como el legacy ($_SERVER['HTTP_HOST'], con puerto si lo hay) */
    public function host(Request $request): string
    {
        return 'http://'.$request->server('HTTP_HOST');
    }

    /** URL absoluta con ?r=<mtime> de un archivo de hp/uploads */
    public function url(Request $request, string $path): string
    {
        return $this->host($request).'/'.$path.'?r='.$this->mtime($path);
    }

    /**
     * Entradas de la carpeta como glob('<dir>*'): archivos y subcarpetas, sin ocultos, en orden de bytes.
     *
     * @return list<array{name: string, file: bool}>
     */
    public function entries(string $dir): array
    {
        $disk = Storage::disk('public');
        $entries = [];
        foreach ($disk->files($dir) as $path) {
            $entries[basename($path)] = true;
        }
        foreach ($disk->directories($dir) as $path) {
            $entries[basename($path)] = false;
        }
        $entries = array_filter($entries, fn ($_, $name) => ! str_starts_with((string) $name, '.'), ARRAY_FILTER_USE_BOTH);
        uksort($entries, fn ($a, $b) => strcmp((string) $a, (string) $b));

        $list = [];
        foreach ($entries as $name => $isFile) {
            $list[] = ['name' => (string) $name, 'file' => $isFile];
        }

        return $list;
    }
}
