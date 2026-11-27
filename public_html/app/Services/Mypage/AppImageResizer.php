<?php

namespace App\Services\Mypage;

use GdImage;

/**
 * Redimensionado de las imágenes de マイコレ con GD (el legacy usaba Imagick):
 *
 * - crop(): escala y recorta al centro a W×H exactos (scaleImage + cropThumbnailImage), JPEG calidad 90.
 *   Corrige la orientación EXIF como change_exif_orientation().
 * - GIF: el legacy redimensionaba cada cuadro a W×H sin conservar proporción; GD solo lee el
 *   primer cuadro, así que la animación se pierde (anotado en el diseño).
 * - fit(): reduce/amplía para caber en W×H conservando proporción (miniatura `_s` de la メインイメージ).
 *
 * Devuelven el binario, o null si el archivo no es una imagen.
 */
class AppImageResizer
{
    public function crop(string $contents, string $extension, int $width, int $height): ?string
    {
        $source = $this->load($contents, $extension);
        if ($source === null) {
            return null;
        }

        if ($extension === 'gif') {
            $target = $this->canvas($width, $height, true);
            imagecopyresampled($target, $source, 0, 0, 0, 0, $width, $height, imagesx($source), imagesy($source));

            return $this->encode($target, 'gif');
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = max($width / $sourceWidth, $height / $sourceHeight);
        $cropWidth = (int) round($width / $scale);
        $cropHeight = (int) round($height / $scale);
        $x = (int) floor(($sourceWidth - $cropWidth) / 2);
        $y = (int) floor(($sourceHeight - $cropHeight) / 2);

        $target = $this->canvas($width, $height, false);
        imagecopyresampled($target, $source, 0, 0, $x, $y, $width, $height, $cropWidth, $cropHeight);

        return $this->encode($target, 'jpg');
    }

    public function fit(string $contents, string $extension, int $width, int $height): ?string
    {
        $source = $this->load($contents, $extension);
        if ($source === null) {
            return null;
        }

        $scale = min($width / imagesx($source), $height / imagesy($source));
        $targetWidth = max(1, (int) round(imagesx($source) * $scale));
        $targetHeight = max(1, (int) round(imagesy($source) * $scale));

        $target = $this->canvas($targetWidth, $targetHeight, $extension === 'gif');
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, imagesx($source), imagesy($source));

        return $this->encode($target, $extension === 'gif' ? 'gif' : 'jpg');
    }

    /**
     * Escala conservando proporción como `convert -resize`: con $height null, a $width de ancho
     * (`-resize 480x`); con $height, para caber en $width×$height (`-resize 240x240`). Amplía o reduce.
     * $format: 'jpg' o 'gif'.
     */
    public function scale(string $contents, string $extension, int $width, ?int $height, string $format): ?string
    {
        $source = $this->load($contents, $extension);
        if ($source === null) {
            return null;
        }

        $scale = $width / imagesx($source);
        if ($height !== null) {
            $scale = min($scale, $height / imagesy($source));
        }
        $targetWidth = max(1, (int) round(imagesx($source) * $scale));
        $targetHeight = max(1, (int) round(imagesy($source) * $scale));

        $target = $this->canvas($targetWidth, $targetHeight, $format === 'gif');
        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, imagesx($source), imagesy($source));

        return $this->encode($target, $format);
    }

    /** Ancho de la imagen, o null si no es una imagen */
    public function width(string $contents): ?int
    {
        $size = @getimagesizefromstring($contents);

        return $size ? (int) $size[0] : null;
    }

    private function load(string $contents, string $extension): ?GdImage
    {
        $image = @imagecreatefromstring($contents);
        if (! $image instanceof GdImage) {
            return null;
        }

        if (in_array($extension, ['jpg', 'jpeg'], true)) {
            $image = $this->orient($image, $contents);
        }

        return $image;
    }

    /** Gira según la etiqueta EXIF Orientation (3 = 180°, 6 = 90° horario, 8 = 90° antihorario) */
    private function orient(GdImage $image, string $contents): GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }
        $exif = @exif_read_data('data://image/jpeg;base64,' . base64_encode($contents));
        $angle = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        return $angle === 0 ? $image : (imagerotate($image, $angle, 0) ?: $image);
    }

    private function canvas(int $width, int $height, bool $transparent): GdImage
    {
        $image = imagecreatetruecolor($width, $height);
        if ($transparent) {
            $color = imagecolorallocatealpha($image, 0, 0, 0, 127);
            imagecolortransparent($image, $color);
            imagefill($image, 0, 0, $color);
        } else {
            // JPEG no tiene transparencia: fondo blanco para los PNG/GIF transparentes
            imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        }

        return $image;
    }

    private function encode(GdImage $image, string $format): string
    {
        ob_start();
        $format === 'gif' ? imagegif($image) : imagejpeg($image, null, 90);

        return (string) ob_get_clean();
    }
}
