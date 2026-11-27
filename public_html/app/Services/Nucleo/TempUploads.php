<?php

namespace App\Services\Nucleo;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Subidas temporales comunes de imágenes (legacy `system/inc/ajax_picupload.php` → carpeta `system/uploads/`).
 * Las usan push (category3), las páginas de category6 (shop_hp, user_hp, digital_catalog) y `system/js/pic_upload.js`:
 * el JS sube `<temp>.<ext>` y después el formulario de cada módulo pasa el archivo a su carpeta definitiva.
 *
 * Disco `public`, ruta `system/uploads/<temp>.<jpg|gif>` + miniatura `<temp>th.<ext>` de 240 px, URL `/system/uploads/…`.
 * El legacy redimensionaba con ImageMagick (`convert`); aquí con GD (un GIF animado reducido pierde la animación).
 */
class TempUploads
{
    public const DIR = 'system/uploads';

    /** Nombre que acepta la URL /system/uploads/{file} */
    public const FILE_PATTERN = '/^[A-Za-z0-9_-]+\.(jpg|gif)$/';

    public const UPLOAD_ERROR = 'エラーが発生しました。もう一度アップロードしてください。';

    public static function uploadSizeError(string $limit): string
    {
        return "エラーが発生しました。\n{$limit}以上のファイルはアップロード出来ません。\nファイルを確認して、もう一度アップロードしてください。";
    }

    public static function tooBigError(string $limit): string
    {
        return "{$limit}以上のファイルはアップロード出来ません。";
    }

    /**
     * ajax_picupload.php?up: guarda `<temp>.jpg` (o `.gif`) y su miniatura `<temp>th.<ext>` de 240 px (solo reduce).
     * Devuelve el nombre guardado, o null si el nombre queda vacío o la imagen no se puede leer.
     */
    public function store(UploadedFile $file, string $tempName, string $ext): ?string
    {
        $tempName = (string) preg_replace('/[^A-Za-z0-9_-]/', '', $tempName);
        if ($tempName === '') {
            return null;
        }
        $suffix = strtolower($ext) === 'gif' ? 'gif' : 'jpg';
        $image = self::load((string) $file->getRealPath());
        if ($image === null) {
            return null;
        }

        $this->disk()->makeDirectory(self::DIR);
        $name = $tempName . '.' . $suffix;
        self::save($image, $this->disk()->path(self::DIR . '/' . $name), $suffix);
        self::save(self::shrink($image, 240), $this->disk()->path(self::DIR . '/' . $tempName . 'th.' . $suffix), $suffix);

        return $name;
    }

    /** Ruta en el disco `public` de una subida temporal, o null si el nombre no es válido o no existe */
    public function path(string $file): ?string
    {
        if (! preg_match(self::FILE_PATTERN, $file)) {
            return null;
        }
        $path = self::DIR . '/' . $file;

        return $this->disk()->exists($path) ? $path : null;
    }

    /** Ruta absoluta de una subida temporal (para cargarla con GD al pasarla a su carpeta definitiva) */
    public function absolutePath(string $file): ?string
    {
        $path = $this->path($file);

        return $path ? $this->disk()->path($path) : null;
    }

    /** ajax_picupload.php?f=<archivo>: nombre de la miniatura (`a.jpg` → `ath.jpg`) */
    public static function thumbName(string $file): string
    {
        [$name, $ext] = array_pad(explode('.', $file, 2), 2, '');

        return $name . 'th.' . $ext;
    }

    /** Borra los temporales de más de `$minutes` minutos (login.php lo hacía con `find -mmin +1440`) */
    public function purge(int $minutes = 1440): int
    {
        $limit = time() - $minutes * 60;
        $deleted = 0;
        foreach ($this->disk()->files(self::DIR) as $path) {
            if ($this->disk()->lastModified($path) < $limit && $this->disk()->delete($path)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    // ------------------------------------------------------------------ GD (públicos para los módulos)

    public static function load(string $path): ?\GdImage
    {
        $info = @getimagesize($path);
        if (! $info) {
            return null;
        }
        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_GIF => @imagecreatefromgif($path),
            IMAGETYPE_BMP => function_exists('imagecreatefrombmp') ? @imagecreatefrombmp($path) : false,
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };

        return $image ?: null;
    }

    /** `-resize 'Wx>'` (solo reduce) o `-resize Wx` (`$always`: también amplía) */
    public static function shrink(\GdImage $image, int $width, bool $always = false): \GdImage
    {
        $w = imagesx($image);
        if ($w === $width || (! $always && $w <= $width)) {
            return $image;
        }

        return imagescale($image, $width, max(1, (int) round($width * imagesy($image) / $w))) ?: $image;
    }

    public static function save(\GdImage $image, string $path, string $ext): void
    {
        match (strtolower($ext)) {
            'gif' => imagegif($image, $path),
            'png' => imagepng($image, $path),
            default => imagejpeg($image, $path, 90),
        };
    }

    private function disk()
    {
        return Storage::disk('public');
    }
}
