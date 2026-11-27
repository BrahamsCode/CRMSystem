<?php

namespace App\Services\Promotions;

use App\Services\Nucleo\TempUploads;
use Illuminate\Support\Facades\Storage;

/**
 * Imágenes de los bloques de un push (bloque de imágenes de category3/push/ajaxSavePush.php). La subida previa
 * (/system/inc/ajax_picupload.php → system/uploads/) es del núcleo (TempUploads, NUCLEO.md §12). Disco `public`:
 *   hp/uploads/c3/push/<push_master.cd>/<archivo> y <nombre>_th.<ext>  al registrar un push (URL /hp/uploads/…, la lee g-api)
 *   hp/uploads/c3/push_follow/<push_follow_master.cd>/…               programados, フォロー y 自動
 * El legacy redimensionaba con ImageMagick (`convert`); aquí con GD (un GIF animado reducido pierde la animación).
 */
class PushImageStore
{
    public const IMAGE_PATTERN = '/\.(gif|png|jpg|jpeg|bmp)$/i';

    public function __construct(private readonly TempUploads $temps) {}

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
     * Copia una subida temporal a la carpeta del push como `<nombre>.<ext>` (máx. 500 px de ancho) y su
     * miniatura `<nombre>_th.<ext>` (75 px), como ajaxSavePush.php. false si la temporal no existe.
     */
    public function promote(string $file, string $folder, int $cd): bool
    {
        $temp = $this->temps->absolutePath($file);
        $image = $temp ? TempUploads::load($temp) : null;
        if ($image === null) {
            return false;
        }
        $dir = self::dir($folder, $cd);
        $this->disk()->makeDirectory($dir);
        [$name, $ext] = array_pad(explode('.', $file, 2), 2, 'jpg');
        TempUploads::save(TempUploads::shrink($image, 75, true), $this->disk()->path($dir.'/'.$name.'_th.'.$ext), $ext);
        TempUploads::save(TempUploads::shrink($image, 500), $this->disk()->path($dir.'/'.$file), $ext);

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
}
