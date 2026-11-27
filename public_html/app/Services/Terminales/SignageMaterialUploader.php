<?php

namespace App\Services\Terminales;

use App\Models\Terminales\SignageMaterial;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Subida de archivos de 放映素材: reemplaza a category7/digital_signage/pic_up.php.
 *
 * - Huecos por tipo: 画像ファイル 0..4, FLVファイル 10..14, SWFファイル 20..24 (pic_max = 5).
 *   pic_flg y pic_extension se guardan con implode, así que el hueco 10 o 20 queda en la posición 0.
 * - Carpeta: hp/uploads/c7/screen_saver/<cd>/ en el disco public.
 *   Original «<x>_moto<ext>»; imagen: «<x><ext>» a 1280 px de ancho y miniatura «<x>s<ext>» de
 *   150x150 (encajada y centrada sobre blanco, como `convert -thumbnail 150x150 -gravity center -extent`).
 *   FLV/SWF: copia del original como «<hueco><ext>» (10.flv, 20.swf).
 * - Con GD nativo en lugar de /usr/bin/convert.
 *
 * Diferencias con el legacy: un error en un hueco ya no bloquea los siguientes ($pic_error no se
 * reiniciaba) ni desplaza las posiciones de pic_flg (el legacy dejaba el hueco sin valor y el implode
 * corría los demás): el hueco con error conserva lo que tenía.
 */
class SignageMaterialUploader
{
    public const FOLDER = 'hp/uploads/c7/screen_saver';

    public const PIC_MAX = 5;

    private const MAIN_WIDTH = 1280;

    private const THUMB_SIZE = 150;

    /** Tipos que acepta pic_up.php ($pic_types) */
    private const ALLOWED_TYPES = [
        'image/pjpeg', 'image/jpeg', 'image/gif', 'image/bmp', 'image/x-png', 'image/png',
        'application/x-shockwave-flash', 'video/x-flv', 'application/octet-stream',
    ];

    private const IMAGE_EXTENSIONS = [
        'image/pjpeg' => '.jpg', 'image/jpeg' => '.jpg', 'image/gif' => '.gif',
        'image/bmp' => '.bmp', 'image/x-png' => '.png', 'image/png' => '.png',
    ];

    private const ERROR_SIZE = '枚目の画像 ※2MB以上のファイルはアップロード出来ません。';

    private const ERROR_TYPE = '枚目の画像 ※この拡張子のファイルはアップロード出来ません。';

    public static function firstSlot(?string $filetype): int
    {
        return match ($filetype) {
            SignageMaterial::FILETYPE_FLV => 10,
            SignageMaterial::FILETYPE_SWF => 20,
            default => 0,
        };
    }

    /**
     * Guarda los archivos subidos y actualiza pic_flg / pic_extension del material.
     *
     * @param  array<int|string, string|null>  $postedFlags  pic_flg[x] enviados
     * @param  array<int|string, string|null>  $postedExtensions  pic_extension[x] enviados
     * @param  array<string, UploadedFile>  $files  photo<x>
     * @return list<string> mensajes de error por hueco («N枚目の画像 ※…»)
     */
    public function apply(SignageMaterial $material, array $postedFlags, array $postedExtensions, array $files): array
    {
        $filetype = (string) $material->filetype;
        $start = self::firstSlot($filetype);
        $dir = self::FOLDER.'/'.$material->cd;
        $errors = [];
        $flags = [];
        $extensions = [];

        for ($slot = $start; $slot < $start + self::PIC_MAX; $slot++) {
            $file = $files['photo'.$slot] ?? null;

            if ($file instanceof UploadedFile) {
                $extension = $this->extensionFor($filetype, $file, $slot, $errors);
                if ($extension !== null && $this->store($file, $dir, $slot, $start, $extension, $filetype)) {
                    $flags[] = '1';
                    $extensions[] = $extension;

                    continue;
                }
            }

            $flags[] = (string) ($postedFlags[$slot] ?? '');
            $extensions[] = (string) ($postedExtensions[$slot] ?? '');
        }

        $material->setLegacyList('pic_flg', $flags)->setLegacyList('pic_extension', $extensions);
        $material->save();

        return $errors;
    }

    /** Extensión según la ファイルタイプ elegida y el tipo enviado por el navegador ($_FILES['type']) */
    private function extensionFor(string $filetype, UploadedFile $file, int $slot, array &$errors): ?string
    {
        if (! $file->isValid()) {
            if (in_array($file->getError(), [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
                $errors[] = ($slot + 1).self::ERROR_SIZE;
            }

            return null;
        }

        $type = (string) $file->getClientMimeType();
        $extension = match (true) {
            ! in_array($type, self::ALLOWED_TYPES, true) => null,
            $filetype === SignageMaterial::FILETYPE_SWF && $type === 'application/x-shockwave-flash' => '.swf',
            $filetype === SignageMaterial::FILETYPE_FLV && in_array($type, ['video/x-flv', 'application/octet-stream'], true) => '.flv',
            $filetype === SignageMaterial::FILETYPE_IMAGE => self::IMAGE_EXTENSIONS[$type] ?? null,
            default => null,
        };

        if ($extension === null) {
            $errors[] = ($slot + 1).self::ERROR_TYPE;
        }

        return $extension;
    }

    private function store(UploadedFile $file, string $dir, int $slot, int $start, string $extension, string $filetype): bool
    {
        $disk = Storage::disk('public');
        $contents = (string) file_get_contents($file->getRealPath());

        if ($filetype !== SignageMaterial::FILETYPE_IMAGE) {
            $disk->put("{$dir}/{$slot}_moto{$extension}", $contents);
            $disk->put("{$dir}/{$start}{$extension}", $contents);

            return true;
        }

        $image = @imagecreatefromstring($contents);
        if (! $image instanceof GdImage) {
            return false;
        }

        $disk->put("{$dir}/{$slot}_moto{$extension}", $contents);
        $disk->put("{$dir}/{$slot}{$extension}", $this->encode($this->resizeToWidth($image, self::MAIN_WIDTH), $extension));
        $disk->put("{$dir}/{$slot}s{$extension}", $this->encode($this->thumbnail($image, self::THUMB_SIZE), $extension));

        return true;
    }

    /** `convert -resize 1280x`: ancho fijo, alto proporcional */
    private function resizeToWidth(GdImage $image, int $width): GdImage
    {
        $height = max(1, (int) round(imagesy($image) * $width / imagesx($image)));
        $resized = imagecreatetruecolor($width, $height);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));

        return $resized;
    }

    /** `convert -thumbnail NxN -gravity center -extent NxN`: encaja y centra sobre blanco */
    private function thumbnail(GdImage $image, int $size): GdImage
    {
        $scale = min($size / imagesx($image), $size / imagesy($image));
        $width = max(1, (int) round(imagesx($image) * $scale));
        $height = max(1, (int) round(imagesy($image) * $scale));

        $canvas = imagecreatetruecolor($size, $size);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $image, intdiv($size - $width, 2), intdiv($size - $height, 2), 0, 0, $width, $height, imagesx($image), imagesy($image));

        return $canvas;
    }

    private function encode(GdImage $image, string $extension): string
    {
        ob_start();
        match ($extension) {
            '.png' => imagepng($image),
            '.gif' => imagegif($image),
            '.bmp' => imagebmp($image),
            default => imagejpeg($image, null, 90),
        };

        return (string) ob_get_clean();
    }
}
