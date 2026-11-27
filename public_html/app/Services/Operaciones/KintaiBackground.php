<?php

namespace App\Services\Operaciones;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * 勤怠打刻画面の背景画像 (kintai/config.php): el original `moto.<ext>`, la miniatura `thum.jpg` (150×150) y la del
 * terminal `0.<ext>` (1024×600) en `hp/uploads/c5/kintai/<tienda>/` del disco public; `pic_name = '0.<ext>'`.
 * El legacy usaba `convert -resize`, que encaja la imagen en la caja manteniendo la proporción (también amplía);
 * aquí se hace igual con GD.
 */
class KintaiBackground
{
    /** Tipos admitidos → extensión (el legacy miraba el tipo que declaraba el navegador) */
    private const TYPES = [
        'image/pjpeg' => 'jpg', 'image/jpeg' => 'jpg', 'image/gif' => 'gif',
        'image/bmp' => 'bmp', 'image/x-ms-bmp' => 'bmp', 'image/x-png' => 'png', 'image/png' => 'png',
    ];

    public static function dir(int $shopCd): string
    {
        return "hp/uploads/c5/kintai/{$shopCd}";
    }

    /** Extensión para el tipo de la subida, o null si no es jpg, bmp, gif ni png */
    public static function extension(UploadedFile $file): ?string
    {
        return self::TYPES[(string) $file->getMimeType()] ?? null;
    }

    /** Guarda las tres imágenes y devuelve el pic_name, o null si GD no puede leerla */
    public function store(int $shopCd, UploadedFile $file, string $ext): ?string
    {
        $bytes = (string) file_get_contents((string) $file->getRealPath());
        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            return null;
        }

        $disk = Storage::disk('public');
        $dir = self::dir($shopCd);
        $disk->put("{$dir}/moto.{$ext}", $bytes);
        $disk->put("{$dir}/thum.jpg", $this->encode($this->fit($image, 150, 150), 'jpg'));
        $disk->put("{$dir}/0.{$ext}", $this->encode($this->fit($image, 1024, 600), $ext));

        return "0.{$ext}";
    }

    /** Ancho y alto de la miniatura (para el <img> como getimagesize del legacy), o null si no existe */
    public static function thumbSize(int $shopCd): ?array
    {
        $disk = Storage::disk('public');
        $path = self::dir($shopCd).'/thum.jpg';
        if (! $disk->exists($path)) {
            return null;
        }
        $size = @getimagesizefromstring((string) $disk->get($path));

        return $size ? [$size[0], $size[1]] : null;
    }

    /** Encaja en la caja manteniendo la proporción (como `convert -resize WxH`) */
    private function fit(\GdImage $image, int $maxWidth, int $maxHeight): \GdImage
    {
        [$width, $height] = [imagesx($image), imagesy($image)];
        $scale = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $out = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagecopyresampled($out, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        return $out;
    }

    private function encode(\GdImage $image, string $ext): string
    {
        ob_start();
        match ($ext) {
            'gif' => imagegif($image),
            'png' => imagepng($image),
            'bmp' => imagebmp($image),
            default => imagejpeg($image, null, 90),
        };

        return (string) ob_get_clean();
    }
}
