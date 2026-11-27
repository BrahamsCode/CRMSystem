<?php

namespace App\Services\Sales;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Imagen del 商品 (legacy: bloque de imagen de category2/item/edit.php). Mismos archivos y rutas que el legacy en el
 * disco `public`: `hp/uploads/c2/item/<cd>/` con `<cd>.jpg` (copia del original tal cual), `<cd>_big.jpg`
 * (dentro de 800×600) y `<cd>_small.jpg` (dentro de 320×240). La URL pública sigue siendo `/hp/uploads/...`
 * (redirección genérica a Storage de S1). El legacy usaba Imagick (resizeImage con bestfit, también amplía); aquí GD.
 */
class ProductImageStore
{
    public const DIR = 'hp/uploads/c2/item';

    /** Guarda las tres versiones; false si el archivo no es una imagen legible */
    public function store(int $cd, UploadedFile $file): bool
    {
        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if (! $source) {
            return false;
        }

        $disk = Storage::disk('public');
        $dir = $this->dir($cd);
        $disk->put($dir.'/'.$cd.'.jpg', (string) file_get_contents($file->getRealPath()));
        $disk->put($dir.'/'.$cd.'_big.jpg', $this->fitted($source, 800, 600));
        $disk->put($dir.'/'.$cd.'_small.jpg', $this->fitted($source, 320, 240));

        return true;
    }

    /** img_delete: borra la carpeta del producto */
    public function delete(int $cd): void
    {
        Storage::disk('public')->deleteDirectory($this->dir($cd));
    }

    /** Hay imagen (el legacy mira `<cd>.jpg`) */
    public function exists(int $cd): bool
    {
        return Storage::disk('public')->exists($this->dir($cd).'/'.$cd.'.jpg');
    }

    /** URL de la miniatura de la lista (`_small`) con ?t= anti caché, o null */
    public function smallUrl(int $cd): ?string
    {
        return $this->exists($cd) ? '/'.$this->dir($cd).'/'.$cd.'_small.jpg?t='.time() : null;
    }

    /** URL de la vista previa del formulario: `_big` si existe, si no `<cd>.jpg`; '' sin imagen */
    public function previewUrl(int $cd): string
    {
        if (! $this->exists($cd)) {
            return '';
        }
        $suffix = Storage::disk('public')->exists($this->dir($cd).'/'.$cd.'_big.jpg') ? '_big' : '';

        return '/'.$this->dir($cd).'/'.$cd.$suffix.'.jpg?t='.time();
    }

    private function dir(int $cd): string
    {
        return self::DIR.'/'.$cd;
    }

    /** Imagick::resizeImage($w, $h, LANCZOS, 1, bestfit = true): cabe en la caja conservando la proporción */
    private function fitted(\GdImage $image, int $maxWidth, int $maxHeight): string
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagefill($resized, 0, 0, imagecolorallocate($resized, 255, 255, 255));
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        ob_start();
        imagejpeg($resized, null, 90);

        return (string) ob_get_clean();
    }
}
