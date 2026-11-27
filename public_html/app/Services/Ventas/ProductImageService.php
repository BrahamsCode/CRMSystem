<?php

namespace App\Services\Ventas;

use App\Services\Mypage\AppImageResizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Imagen del producto, en el disco public de Storage con la ruta del legacy `hp/uploads/c2/item/<cd>/`:
 * - `<cd>.jpg`: el archivo subido tal cual (el legacy hacía copy());
 * - `<cd>_big.jpg`: cabe en 800×600; `<cd>_small.jpg`: cabe en 320×240 (Imagick resizeImage(…, bestfit) del legacy,
 *   aquí con GD vía AppImageResizer::fit(): conserva proporción, amplía o reduce, JPEG).
 */
class ProductImageService
{
    public function __construct(private AppImageResizer $resizer) {}

    public function directory(int $cd): string
    {
        return 'hp/uploads/c2/item/' . $cd;
    }

    /** Guarda las tres versiones; devuelve el error del legacy o '' */
    public function store(int $cd, UploadedFile $file): string
    {
        if (! $file->isValid()) {
            return '画像が見つかりません。';
        }

        $contents = (string) file_get_contents($file->getRealPath());
        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
        $big = $this->resizer->fit($contents, $extension, 800, 600);
        $small = $this->resizer->fit($contents, $extension, 320, 240);
        if ($big === null || $small === null) {
            return 'アップロードに失敗しました。';
        }

        $disk = Storage::disk('public');
        $dir = $this->directory($cd);
        $disk->put("{$dir}/{$cd}.jpg", $contents);
        $disk->put("{$dir}/{$cd}_big.jpg", $big);
        $disk->put("{$dir}/{$cd}_small.jpg", $small);

        return '';
    }

    /** img_delete: borra la carpeta entera del producto */
    public function delete(int $cd): void
    {
        Storage::disk('public')->deleteDirectory($this->directory($cd));
    }

    /** El legacy decide si hay imagen mirando `<cd>.jpg` */
    public function exists(int $cd): bool
    {
        return Storage::disk('public')->exists($this->directory($cd) . "/{$cd}.jpg");
    }

    /** URL pública con `?t=` para no usar la caché, como el legacy; null si no hay imagen */
    public function url(int $cd, string $suffix = ''): ?string
    {
        return $this->exists($cd) ? '/' . $this->directory($cd) . "/{$cd}{$suffix}.jpg?t=" . time() : null;
    }

    /** edit.php: la grande si existe, si no la original */
    public function previewUrl(int $cd): ?string
    {
        $suffix = Storage::disk('public')->exists($this->directory($cd) . "/{$cd}_big.jpg") ? '_big' : '';

        return $this->url($cd, $suffix);
    }
}
