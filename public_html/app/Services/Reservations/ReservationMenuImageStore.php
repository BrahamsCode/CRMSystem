<?php

namespace App\Services\Reservations;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Imagen del 予約項目 (legacy: bloque 画像処理 de yoyaku_koumoku_register.php).
 * Mismos archivos y rutas que el legacy en el disco `public`: `hp/uploads/c9/yoyaku_koumoku/<cd>/`
 * con `1_moto.<ext>` (original), `1_m.jpg` (180x240) y `1_s.jpg` (90x120). La URL pública sigue siendo
 * `/hp/uploads/...` (redirección genérica a Storage de S1).
 * El legacy usaba ImageMagick (`convert`); aquí GD: orientación EXIF y reducción dentro de la caja.
 */
class ReservationMenuImageStore
{
    public const DIR = 'hp/uploads/c9/yoyaku_koumoku';

    private const EXTENSIONS = [
        'image/pjpeg' => 'jpg', 'image/jpeg' => 'jpg', 'image/gif' => 'gif',
        'image/bmp' => 'bmp', 'image/x-png' => 'png', 'image/png' => 'png',
    ];

    public function store(int $menuCd, UploadedFile $file): void
    {
        $extension = self::EXTENSIONS[$file->getMimeType()] ?? null;
        $source = $extension ? @imagecreatefromstring((string) file_get_contents($file->getRealPath())) : false;
        if (! $source) {
            return; // el legacy tampoco generaba miniaturas de un archivo que no fuera imagen
        }

        $disk = Storage::disk('public');
        $dir = self::DIR . '/' . $menuCd;
        $disk->putFileAs($dir, $file, '1_moto.' . $extension);

        $image = $this->applyExifOrientation($source, $file->getRealPath());
        $disk->put($dir . '/1_m.jpg', $this->resizedJpeg($image, 180, 240));
        $disk->put($dir . '/1_s.jpg', $this->resizedJpeg($image, 90, 120));
    }

    /** 画像を削除: borra las miniaturas (el original queda, como en el legacy) */
    public function delete(int $menuCd): void
    {
        $dir = self::DIR . '/' . $menuCd;
        Storage::disk('public')->delete([$dir . '/1_m.jpg', $dir . '/1_s.jpg']);
    }

    /** URL de `1_s.jpg` con `?q=` aleatorio (anti caché, como el legacy) o null si no existe */
    public function thumbnailUrl(int $menuCd): ?string
    {
        $path = self::DIR . '/' . $menuCd . '/1_s.jpg';

        return Storage::disk('public')->exists($path) ? '/' . $path . '?q=' . random_int(0, PHP_INT_MAX) : null;
    }

    private function applyExifOrientation(\GdImage $image, string $path): \GdImage
    {
        $orientation = function_exists('exif_read_data') ? (int) (@exif_read_data($path)['Orientation'] ?? 1) : 1;

        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }
        // `convert -rotate` gira en sentido horario; imagerotate en sentido antihorario
        $degrees = [3 => 180, 4 => 180, 5 => 270, 6 => 270, 7 => 90, 8 => 90][$orientation] ?? 0;

        return $degrees ? imagerotate($image, $degrees, 0) : $image;
    }

    /** `convert -resize WxH`: cabe en la caja conservando la proporción */
    private function resizedJpeg(\GdImage $image, int $maxWidth, int $maxHeight): string
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
