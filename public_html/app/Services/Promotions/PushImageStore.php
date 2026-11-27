<?php

namespace App\Services\Promotions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Imágenes de los bloques de un push (legacy /system/inc/ajax_picupload.php + el bloque de imágenes de
 * category3/push/ajaxSavePush.php). Disco `public` con las rutas del legacy:
 *   system/uploads/<temp>.<jpg|gif> y <temp>th.<ext> (240 px)       subida previa (URL /system/uploads/…)
 *   hp/uploads/c3/push/<push_master.cd>/<archivo> y <nombre>_th.<ext>  al registrar un push (URL /hp/uploads/…, la lee g-api)
 *   hp/uploads/c3/push_follow/<push_follow_master.cd>/…               programados, フォロー y 自動
 * El legacy redimensionaba con ImageMagick (`convert`); aquí con GD (un GIF animado reducido pierde la animación).
 */
class PushImageStore
{
    public const IMAGE_PATTERN = '/\.(gif|png|jpg|jpeg|bmp)$/i';

    private const TEMP_DIR = 'system/uploads';

    /** Mensajes de ajax_picupload.php (respuesta `error:<mensaje>`) */
    public const UPLOAD_ERROR = 'エラーが発生しました。もう一度アップロードしてください。';

    public static function uploadSizeError(string $limit): string
    {
        return "エラーが発生しました。\n{$limit}以上のファイルはアップロード出来ません。\nファイルを確認して、もう一度アップロードしてください。";
    }

    public static function tooBigError(string $limit): string
    {
        return "{$limit}以上のファイルはアップロード出来ません。";
    }

    private function disk()
    {
        return Storage::disk('public');
    }

    /** Carpeta de un push: push (envío directo o prueba) o push_follow (programado/フォロー/自動) */
    public static function folder(string $typeFlg): string
    {
        return $typeFlg !== '' ? 'push_follow' : 'push';
    }

    public static function dir(string $folder, int $cd): string
    {
        return 'hp/uploads/c3/'.$folder.'/'.$cd;
    }

    /**
     * ajax_picupload.php?up: guarda `<temp>.jpg` (o .gif) y su miniatura `<temp>th.<ext>` de 240 px.
     * Devuelve el nombre guardado o null si la imagen no se puede leer.
     */
    public function storeTemp(UploadedFile $file, string $tempName, string $ext): ?string
    {
        $tempName = preg_replace('/[^A-Za-z0-9_-]/', '', $tempName);
        if ($tempName === '') {
            return null;
        }
        $suffix = strtolower($ext) === 'gif' ? 'gif' : 'jpg';
        $image = $this->load((string) $file->getRealPath());
        if ($image === null) {
            return null;
        }

        $this->disk()->makeDirectory(self::TEMP_DIR);
        $name = $tempName.'.'.$suffix;
        $this->save($image, $this->disk()->path(self::TEMP_DIR.'/'.$name), $suffix);
        $this->save($this->shrink($image, 240), $this->disk()->path(self::TEMP_DIR.'/'.$tempName.'th.'.$suffix), $suffix);

        return $name;
    }

    /** Ruta en el disco de una subida temporal (o null si el nombre no es válido o no existe) */
    public function tempPath(string $file): ?string
    {
        if (! preg_match('/^[A-Za-z0-9_-]+\.(jpg|gif)$/', $file)) {
            return null;
        }
        $path = self::TEMP_DIR.'/'.$file;

        return $this->disk()->exists($path) ? $path : null;
    }

    /** ajax_picupload.php?f=<archivo>: nombre de la miniatura */
    public static function thumbName(string $file): string
    {
        [$name, $ext] = array_pad(explode('.', $file, 2), 2, '');

        return $name.'th.'.$ext;
    }

    /**
     * Copia una subida temporal a la carpeta del push como `<nombre>.<ext>` (máx. 500 px de ancho) y su
     * miniatura `<nombre>_th.<ext>` (75 px), como ajaxSavePush.php. false si la temporal no existe.
     */
    public function promote(string $file, string $folder, int $cd): bool
    {
        $temp = $this->tempPath($file);
        $image = $temp ? $this->load($this->disk()->path($temp)) : null;
        if ($image === null) {
            return false;
        }
        $dir = self::dir($folder, $cd);
        $this->disk()->makeDirectory($dir);
        [$name, $ext] = array_pad(explode('.', $file, 2), 2, 'jpg');
        $this->save($this->shrink($image, 75, true), $this->disk()->path($dir.'/'.$name.'_th.'.$ext), $ext);
        $this->save($this->shrink($image, 500), $this->disk()->path($dir.'/'.$file), $ext);

        return true;
    }

    /** Prueba de un push ya registrado: copia la imagen registrada a la carpeta del push de prueba */
    public function copyRegistered(string $file, string $fromFolder, int $fromCd, string $toFolder, int $toCd): void
    {
        if (basename($file) !== $file) {
            return;
        }
        $from = self::dir($fromFolder, $fromCd).'/'.$file;
        if ($this->disk()->exists($from)) {
            $this->disk()->copy($from, self::dir($toFolder, $toCd).'/'.$file);
        }
    }

    public function exists(string $folder, int $cd, string $file): bool
    {
        return basename($file) === $file && $this->disk()->exists(self::dir($folder, $cd).'/'.$file);
    }

    /** URL pública de una imagen registrada (`/hp/uploads/c3/<carpeta>/<cd>/<archivo>`) */
    public static function url(string $folder, int $cd, string $file): string
    {
        return '/'.self::dir($folder, $cd).'/'.$file;
    }

    // ------------------------------------------------------------------ GD

    private function load(string $path): ?\GdImage
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

    /** `-resize 'Wx>'` (solo reduce) o `-resize Wx` ($always: también amplía, como la miniatura de 75 px) */
    private function shrink(\GdImage $image, int $width, bool $always = false): \GdImage
    {
        $w = imagesx($image);
        if ($w === $width || (! $always && $w <= $width)) {
            return $image;
        }

        return imagescale($image, $width, max(1, (int) round($width * imagesy($image) / $w))) ?: $image;
    }

    private function save(\GdImage $image, string $path, string $ext): void
    {
        match (strtolower($ext)) {
            'gif' => imagegif($image, $path),
            'png' => imagepng($image, $path),
            default => imagejpeg($image, $path, 90),
        };
    }
}
