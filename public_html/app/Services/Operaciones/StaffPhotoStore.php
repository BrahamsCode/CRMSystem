<?php

namespace App\Services\Operaciones;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Foto del personal (category5/staff/inc/upload.php + staff_entry_register.php).
 *
 * - Subida por AJAX: la imagen se corrige según la orientación EXIF, se reduce a 200 px de ancho como
 *   máximo (`convert -resize '200x>'`) y queda como JPEG temporal `<token>.jpg` (disco local, privado).
 *   El token son solo dígitos, como el `myTime` del JS del legacy.
 * - Al registrar, la temporal pasa a `hp/uploads/c5/staff/<cd>/<cd>.jpg` del disco public (la URL del
 *   legacy `/hp/uploads/...` la sirve el núcleo).
 */
class StaffPhotoStore
{
    private const TEMP_DIR = 'operaciones/staff-temp';

    private const MAX_WIDTH = 200;

    /** Guarda la subida como temporal y devuelve su token, o null si no es una imagen válida */
    public function storeTemp(UploadedFile $file): ?string
    {
        $path = $file->getRealPath();
        $image = $path ? @imagecreatefromstring((string) file_get_contents($path)) : false;
        if ($image === false) {
            return null;
        }

        $image = $this->resize($this->orient($image, $path));
        $token = (int) (microtime(true) * 1000).random_int(100, 999);

        ob_start();
        imagejpeg($image, null, 90);
        Storage::disk('local')->put($this->tempPath($token), (string) ob_get_clean());

        return $token;
    }

    public static function isToken(mixed $value): bool
    {
        return is_string($value) && preg_match('/^[0-9]{4,20}$/', $value) === 1;
    }

    public function tempExists(string $token): bool
    {
        return self::isToken($token) && Storage::disk('local')->exists($this->tempPath($token));
    }

    public function tempPath(string $token): string
    {
        return self::TEMP_DIR."/{$token}.jpg";
    }

    /** Pasa la temporal a la carpeta del empleado; false si la temporal ya no existe */
    public function moveToStaff(string $token, int $cd): bool
    {
        if (! $this->tempExists($token)) {
            return false;
        }

        Storage::disk('public')->put(self::staffPath($cd), (string) Storage::disk('local')->get($this->tempPath($token)));
        Storage::disk('local')->delete($this->tempPath($token));

        return true;
    }

    public static function staffPath(int $cd): string
    {
        return "hp/uploads/c5/staff/{$cd}/{$cd}.jpg";
    }

    /** Orientación EXIF 2..8 como los `convert -flop/-rotate` del legacy */
    private function orient(\GdImage $image, string $path): \GdImage
    {
        $orientation = (int) ((@exif_read_data($path) ?: [])['Orientation'] ?? 1);
        $flip = in_array($orientation, [2, 4, 5, 7], true);
        $angle = match ($orientation) {
            3, 4 => 180,
            5, 6 => 270, // imagerotate gira en sentido antihorario: 270 = 90 horario
            7, 8 => 90,
            default => 0,
        };

        if ($flip) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }

        return $angle ? (imagerotate($image, $angle, 0) ?: $image) : $image;
    }

    private function resize(\GdImage $image): \GdImage
    {
        $width = imagesx($image);
        if ($width <= self::MAX_WIDTH) {
            return $image;
        }

        return imagescale($image, self::MAX_WIDTH, (int) round(imagesy($image) * self::MAX_WIDTH / $width)) ?: $image;
    }
}
