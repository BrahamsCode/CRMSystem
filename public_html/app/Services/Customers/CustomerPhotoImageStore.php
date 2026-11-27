<?php

namespace App\Services\Customers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Archivos de マイアルバム en el disco `public`, con las mismas rutas (y URL) que el legacy:
 *
 * - Temporales del AjaxUpload (`inc/ajax_picupload.php`): `system/category1/customer/uploads/<temp>.jpg`
 *   y la miniatura `<temp>th.jpg` (`-resize 240x>`). URL `/system/category1/customer/uploads/...`.
 * - Álbum (`photoalbum_register.php`): `hp/uploads/c1/photoalbum/<cd>/<n>_moto.jpg` y `<n>_l` (480x>),
 *   `_m` (240x>), `_s` (150x150), `_ss` (75x75) `.jpg`. URL `/hp/uploads/...` (redirección genérica de S1);
 *   es la misma carpeta que usa la app (`api/mpa_albumUpdate.php`, PhotoAlbumUploader de S5).
 *
 * El legacy usaba `system('convert')`; aquí GD, con la misma geometría de ImageMagick
 * ('480x>' = solo reduce al ancho; '150x150' = cabe en la caja, amplía o reduce).
 */
class CustomerPhotoImageStore
{
    public const TEMP_DIR = 'system/category1/customer/uploads';

    public const ALBUM_DIR = 'hp/uploads/c1/photoalbum';

    /** Sufijo => [ancho, alto|null, solo reducir] (geometrías del legacy: l '480x>', m '240x>', s '150x150', ss '75x75') */
    public const SIZES = ['l' => [480, null, true], 'm' => [240, null, true], 's' => [150, 150, false], 'ss' => [75, 75, false]];

    /** Extensiones que acepta ajax_picupload.php (el resto se guarda como jpg) */
    public const EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

    /** Nombre del temporal que devuelve la subida y llega como pic_name[]: `<temp>.jpg` */
    public const TEMP_NAME_PATTERN = '/^[A-Za-z0-9_-]{1,100}\.jpg$/';

    private function disk()
    {
        return Storage::disk('public');
    }

    /**
     * Guarda la subida como `<temp>.jpg` (convertida si no era JPEG) y su miniatura `<temp>th.jpg`.
     * Devuelve el nombre `<temp>.jpg`, o null si el archivo no es una imagen.
     */
    public function storeTemp(UploadedFile $file, string $tempName): ?string
    {
        $this->cleanOldTemps();

        $contents = (string) file_get_contents((string) $file->getRealPath());
        $image = @imagecreatefromstring($contents);
        if (! $image) {
            return null;
        }

        $info = @getimagesizefromstring($contents);
        $jpeg = ($info[2] ?? null) === IMAGETYPE_JPEG ? $contents : $this->jpeg($image);

        $this->disk()->put(self::TEMP_DIR . "/{$tempName}.jpg", $jpeg);
        $this->disk()->put(self::TEMP_DIR . "/{$tempName}th.jpg", $this->jpeg($this->resize($image, 240, null, true)));

        return "{$tempName}.jpg";
    }

    /** ¿Existe el temporal de pic_name[]? (nombre válido y archivo subido) */
    public function tempExists(?string $picName): bool
    {
        return $picName !== null && preg_match(self::TEMP_NAME_PATTERN, $picName) === 1
            && $this->disk()->exists(self::TEMP_DIR . '/' . $picName);
    }

    /** Copia el temporal al álbum (`<n>_moto.jpg` y los cuatro tamaños) y lo borra */
    public function moveToAlbum(string $picName, int $albumCd, int $index): void
    {
        $tempPath = self::TEMP_DIR . '/' . $picName;
        $contents = (string) $this->disk()->get($tempPath);
        $dir = self::ALBUM_DIR . '/' . $albumCd;

        $this->disk()->put("{$dir}/{$index}_moto.jpg", $contents);

        $image = @imagecreatefromstring($contents);
        if ($image) {
            foreach (self::SIZES as $suffix => [$width, $height, $shrinkOnly]) {
                $this->disk()->put("{$dir}/{$index}_{$suffix}.jpg", $this->jpeg($this->resize($image, $width, $height, $shrinkOnly)));
            }
        }

        // 一時画像ファイル削除 (el legacy dejaba la miniatura; se borra también)
        $this->disk()->delete([$tempPath, self::TEMP_DIR . '/' . substr($picName, 0, -4) . 'th.jpg']);
    }

    /** Ruta en el disco de un temporal pedido por URL, o null si el nombre no es válido */
    public function tempPath(string $file): ?string
    {
        return preg_match('/^[A-Za-z0-9_-]{1,110}\.(jpg|jpeg|png|gif|bmp|webp)$/', $file) === 1
            ? self::TEMP_DIR . '/' . $file
            : null;
    }

    /** `convert -resize`: con $height null escala al ancho; si no, cabe en la caja. $shrinkOnly = sufijo '>' */
    private function resize(\GdImage $image, int $width, ?int $height, bool $shrinkOnly): \GdImage
    {
        $sourceWidth = imagesx($image);
        $sourceHeight = imagesy($image);
        $scale = $width / $sourceWidth;
        if ($height !== null) {
            $scale = min($scale, $height / $sourceHeight);
        }
        if ($shrinkOnly && $scale >= 1) {
            return $image;
        }

        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
        imagecopyresampled($target, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);

        return $target;
    }

    private function jpeg(\GdImage $image): string
    {
        // Fondo blanco para PNG/GIF con transparencia
        $canvas = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopy($canvas, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        ob_start();
        imagejpeg($canvas, null, 90);

        return (string) ob_get_clean();
    }

    /** Temporales abandonados de más de un día (el legacy lo hacía con `find -mtime +1` en category1/index.php) */
    private function cleanOldTemps(): void
    {
        $limit = now()->subDay()->getTimestamp();
        foreach ($this->disk()->files(self::TEMP_DIR) as $path) {
            if ($this->disk()->lastModified($path) < $limit) {
                $this->disk()->delete($path);
            }
        }
    }
}
