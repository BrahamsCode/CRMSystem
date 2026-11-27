<?php

namespace App\Services\Promotions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Archivo de 新着情報 (category3/digital_signage). Disco `public`, mismas rutas que el legacy:
 *   system/category3/digital_signage/uploads/temp/<time>.swf   (inc/upload.php; el legacy guardaba
 *                                                                cualquier jpg/png/gif/swf con extensión .swf)
 *   hp/uploads/c3/digital_signage/<cd>/<cd>.swf                (digital_signage_register.php; enlace プレビュー)
 * Ningún reproductor lee estos archivos ni tb_digital_signage (la señalización de category7 usa sus propias tablas).
 */
class DigitalSignageNewsStore
{
    public const EXTENSIONS = ['jpg', 'png', 'jpeg', 'gif', 'swf'];

    private const TEMP_DIR = 'system/category3/digital_signage/uploads/temp';

    private const BASE = 'hp/uploads/c3/digital_signage';

    private function disk()
    {
        return Storage::disk('public');
    }

    /** inc/upload.php: guarda temp/<time>.swf y borra la subida anterior (lastTime) */
    public function storeTemp(UploadedFile $file, string $ext, string $time, ?string $lastTime = null, ?string $lastExt = null): bool
    {
        if (! ctype_digit($time) || ! in_array(strtolower($ext), self::EXTENSIONS, true)) {
            return false;
        }
        if ($this->disk()->putFileAs(self::TEMP_DIR, $file, $time.'.swf') === false) {
            return false;
        }
        if ((string) $lastExt !== '' && $lastTime !== null && ctype_digit($lastTime) && $lastTime !== $time) {
            $this->disk()->delete(self::TEMP_DIR.'/'.$lastTime.'.swf');
        }

        return true;
    }

    /** URL de la subida temporal (inc/disp_pic.php); null si no existe */
    public function tempUrl(string $time): ?string
    {
        $path = self::TEMP_DIR.'/'.$time.'.swf';

        return ctype_digit($time) && $this->disk()->exists($path) ? asset($path) : null;
    }

    /** register: copia la temporal a hp/uploads/c3/digital_signage/<cd>/<cd>.swf y la borra */
    public function publish(string $time, int $cd): bool
    {
        $temp = self::TEMP_DIR.'/'.$time.'.swf';
        if (! ctype_digit($time) || ! $this->disk()->exists($temp)) {
            return false;
        }
        $this->disk()->put($this->path($cd), (string) $this->disk()->get($temp));
        $this->disk()->delete($temp);

        return true;
    }

    /** Enlace プレビュー del listado (solo si el archivo existe) */
    public function previewUrl(int $cd): ?string
    {
        return $this->disk()->exists($this->path($cd)) ? asset($this->path($cd)) : null;
    }

    public function path(int $cd): string
    {
        return self::BASE.'/'.$cd.'/'.$cd.'.swf';
    }
}
