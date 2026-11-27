<?php

namespace App\Services\Mypage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Imágenes temporales del AjaxUpload del legacy (inc/upload.php + inc/disp_pic.php de
 * category6/user_hp y category6/recruit). El JS manda `time` (milisegundos del navegador) y el
 * archivo queda como `<time>.<ext>`; al guardar el formulario llega como `pic_name`.
 *
 * Van al disco `public`, en `<carpeta>/temp/` (el legacy usaba uploads/temp/ dentro de system/).
 * El temporal se borra al guardarlo; los abandonados de más de un día se limpian en cada subida
 * (el legacy solo borraba el anterior de la misma sesión de subida).
 */
class LegacyTempImages
{
    public const NAME_PATTERN = '/^\d{1,20}$/';

    public function __construct(private readonly AppImageResizer $resizer) {}

    private function disk()
    {
        return Storage::disk('public');
    }

    public function validName(?string $name): bool
    {
        return $name !== null && preg_match(self::NAME_PATTERN, $name) === 1;
    }

    /**
     * Guarda la subida procesada. $profile:
     *  - 'recommend': `-resize 480x`; GIF se queda en GIF (solo el primer cuadro con GD).
     *  - 'recruit': siempre JPEG; más ancho que 240 → 240 de ancho; si no, `-resize 240x240`.
     */
    public function store(string $folder, string $time, ?string $lastTime, UploadedFile $file, string $profile): bool
    {
        if (! $this->validName($time)) {
            return false;
        }
        $this->cleanOld($folder);

        $extension = strtolower($file->getClientOriginalExtension());
        $contents = (string) file_get_contents($file->getRealPath());

        if ($profile === 'recommend') {
            $format = $extension === 'gif' ? 'gif' : 'jpg';
            $output = $this->resizer->scale($contents, $extension, 480, null, $format);
        } else {
            $format = 'jpg';
            $width = $this->resizer->width($contents);
            $output = $width !== null && $width > 240
                ? $this->resizer->scale($contents, $extension, 240, null, 'jpg')
                : $this->resizer->scale($contents, $extension, 240, 240, 'jpg');
        }
        if ($output === null) {
            return false;
        }

        $this->disk()->put($this->path($folder, $time, $format), $output);

        // Como el legacy: borra la subida anterior del mismo formulario
        if ($this->validName($lastTime) && $lastTime !== $time) {
            $this->disk()->delete([$this->path($folder, $lastTime, 'jpg'), $this->path($folder, $lastTime, 'gif')]);
        }

        return true;
    }

    /** Ruta del temporal existente (`.gif` tiene prioridad, como recommend_register.php) o null */
    public function find(string $folder, ?string $time): ?string
    {
        if (! $this->validName($time)) {
            return null;
        }
        foreach (['gif', 'jpg'] as $extension) {
            $path = $this->path($folder, $time, $extension);
            if ($this->disk()->exists($path)) {
                return $path;
            }
        }

        return null;
    }

    public function url(string $path): string
    {
        return asset($path);
    }

    public function path(string $folder, string $time, string $extension): string
    {
        return $folder . '/temp/' . $time . '.' . $extension;
    }

    private function cleanOld(string $folder): void
    {
        $limit = now()->subDay()->getTimestamp();
        foreach ($this->disk()->files($folder . '/temp') as $path) {
            if ($this->disk()->lastModified($path) < $limit) {
                $this->disk()->delete($path);
            }
        }
    }
}
