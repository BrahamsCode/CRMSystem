<?php

namespace App\Services\Promotions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Imagen adjunta de las お知らせメール de texto (mail_mag/inc/upload.php, disp_pic.php y el bloque
 * de imagen de inc/mail_confirm_frm.php). Vive en el disco `public` con la ruta del legacy:
 *   system/category3/<carpeta>/uploads/temp/<time>.<jpg|gif>     (subida previa)
 *   system/category3/<carpeta>/uploads/<tienda>/<tienda>-<epoch>[_s].<ext>  (al confirmar)
 * <carpeta>: mail_mag para `mail`, el tipo para follow/auto.
 *
 * El legacy redimensionaba con ImageMagick; aquí con GD (un GIF animado pierde la animación si se reduce).
 */
class MailImageStore
{
    private const IMAGE_TYPES = [1 => 'image/gif', 2 => 'image/jpeg', 3 => 'image/png'];

    public static function folder(string $mailType): string
    {
        return $mailType === 'mail' ? 'mail_mag' : $mailType;
    }

    private function disk()
    {
        return Storage::disk('public');
    }

    private function base(string $folder): string
    {
        return 'system/category3/'.$folder.'/uploads';
    }

    /** upload.php: guarda en temp/<time>.<jpg|gif>, borra la anterior y reduce a 480 px de ancho */
    public function storeTemp(string $folder, UploadedFile $file, string $ext, string $time, ?string $lastTime = null, ?string $lastExt = null): bool
    {
        $suffix = $ext === 'gif' ? 'gif' : 'jpg';
        if (! ctype_digit($time)) {
            return false;
        }
        $path = $this->base($folder).'/temp/'.$time.'.'.$suffix;
        $this->disk()->putFileAs(dirname($path), $file, basename($path));

        if ((string) $lastExt !== '' && $lastTime !== null && ctype_digit($lastTime)) {
            $this->disk()->delete($this->base($folder).'/temp/'.$lastTime.'.'.($lastExt === 'gif' ? 'gif' : 'jpg'));
        }

        $this->resizeToWidth($this->disk()->path($path), 480);

        return true;
    }

    /** URL de la imagen temporal (disp_pic.php); null si no existe */
    public function tempUrl(string $folder, string $time, string $ext): ?string
    {
        $path = $this->tempPath($folder, $time, $ext);

        return $path === null ? null : $this->disk()->url($path);
    }

    private function tempPath(string $folder, string $time, ?string $ext = null): ?string
    {
        if (! ctype_digit($time)) {
            return null;
        }
        $candidates = $ext === null ? ['jpg', 'gif'] : [$ext === 'gif' ? 'gif' : 'jpg'];
        foreach ($candidates as $suffix) {
            $path = $this->base($folder).'/temp/'.$time.'.'.$suffix;
            if ($this->disk()->exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Confirmación: copia la temporal a la carpeta de la tienda como grande (350 px si pasa de 600)
     * y pequeña (máx. 480 px). Devuelve los campos ocultos p_* del legacy y la URL de la pequeña.
     */
    public function promote(string $folder, string $time, string $shopCd, ?int $now = null): ?array
    {
        $temp = $this->tempPath($folder, $time);
        if ($temp === null) {
            return null;
        }

        return $this->publish($folder, $this->disk()->path($temp), $shopCd, $now);
    }

    /** Imagen de un cupón (coupon/uploads/coupon/<cd>.jpg) como adjunto */
    public function promoteCoupon(string $folder, int $couponCd, string $shopCd, ?int $now = null): ?array
    {
        $source = $this->disk()->path('system/category3/coupon/uploads/coupon/'.$couponCd.'.jpg');

        return is_file($source) ? $this->publish($folder, $source, $shopCd, $now, false) : null;
    }

    private function publish(string $folder, string $source, string $shopCd, ?int $now, bool $resize = true): ?array
    {
        $ext = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        $epoch = $now ?? time();
        $big = $shopCd.'-'.$epoch.'.'.$ext;
        $small = $shopCd.'-'.$epoch.'_s.'.$ext;
        $dir = $this->base($folder).'/'.$shopCd;
        $this->disk()->makeDirectory($dir);
        $bigPath = $this->disk()->path($dir.'/'.$big);
        $smallPath = $this->disk()->path($dir.'/'.$small);

        copy($source, $bigPath);
        if ($resize) {
            [$w] = getimagesize($bigPath) ?: [0];
            if ($w > 600) {
                $this->resizeToWidth($bigPath, 350);
            }
        }
        copy($bigPath, $smallPath);
        if ($resize) {
            $this->resizeToWidth($smallPath, 480);
        }

        $info = getimagesize($smallPath) ?: [0, 0, 0];

        return [
            'p_name_b' => $big,
            'p_name_s' => $small,
            'p_kakucyo' => $ext,
            'p_type' => self::IMAGE_TYPES[$info[2]] ?? '',
            'p_size_b' => (string) filesize($bigPath),
            'p_size_s' => (string) filesize($smallPath),
            'p_size_w' => (string) $info[0],
            'p_size_h' => (string) $info[1],
            'url' => $this->disk()->url($dir.'/'.$small),
        ];
    }

    /** URL de la imagen pequeña guardada de un envío */
    public function url(string $folder, string $shopCd, ?string $name): ?string
    {
        return $name ? $this->disk()->url($this->base($folder).'/'.$shopCd.'/'.$name) : null;
    }

    /** Borra las imágenes promovidas al volver a 文章を書き直す (como el legacy con back_flg) */
    public function discard(string $folder, string $shopCd, ?string $big, ?string $small): void
    {
        foreach ([$big, $small] as $name) {
            if ($name && basename($name) === $name) {
                $this->disk()->delete($this->base($folder).'/'.$shopCd.'/'.$name);
            }
        }
    }

    /** Reduce proporcionalmente si el ancho supera $width (GD) */
    private function resizeToWidth(string $path, int $width): void
    {
        $info = @getimagesize($path);
        if (! $info || $info[0] <= $width) {
            return;
        }
        $src = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_GIF => @imagecreatefromgif($path),
            default => false,
        };
        if (! $src) {
            return;
        }
        $dst = imagescale($src, $width, (int) round($width * $info[1] / $info[0]));
        match ($info[2]) {
            IMAGETYPE_PNG => imagepng($dst, $path),
            IMAGETYPE_GIF => imagegif($dst, $path),
            default => imagejpeg($dst, $path, 100),
        };
    }
}
