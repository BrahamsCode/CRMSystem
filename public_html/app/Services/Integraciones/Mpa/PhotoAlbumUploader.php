<?php

namespace App\Services\Integraciones\Mpa;

use App\Services\Nucleo\Hatuban;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Foto subida desde la app al álbum del socio (api/mpa_albumUpdate.php): fila en tb_photo_album
 * (hatuban 'tb_photo_album') y archivos en hp/uploads/c1/photoalbum/<cd>/ del disco public:
 * 0_moto.<ext> (original; si no es jpg, también 0_moto.jpg) y 0_l / 0_m / 0_s / 0_ss.jpg con los tamaños
 * del `convert -resize` del legacy. Imagick en lugar de system('convert').
 */
class PhotoAlbumUploader
{
    /** A dónde vuelve la app (URL fija en el legacy) */
    public const RETURN_URL = 'http://b-develop.g-api.net/api/mpa/myAlbumPage';

    public const EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'heic', 'heif', 'tif', 'tiff'];

    /** Sufijo => geometría de ImageMagick ('480x>' = ancho máx. 480; '150x150' = cabe en la caja) */
    public const SIZES = ['l' => '480x>', 'm' => '240x>', 's' => '150x150', 'ss' => '75x75'];

    public function __construct(private readonly Hatuban $hatuban)
    {
    }

    public function accepts(UploadedFile $file): bool
    {
        $name = (string) $file->getClientOriginalName();

        return mb_strlen($name) > 0 && in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), self::EXTENSIONS, true);
    }

    /** Crea la fila y las imágenes; devuelve el cd de tb_photo_album */
    public function store(string $customerId, string $comment, UploadedFile $file): int
    {
        $cd = DB::transaction(function () use ($customerId, $comment) {
            // Sin contador: cd 1 y kazu = 2, como el legacy
            $cd = $this->hatuban->next('tb_photo_album');

            $now = time();
            DB::table('tb_photo_album')->insert([
                'cd' => $cd,
                'tb_customer_cd' => DB::table('tb_customer')->where('id', $customerId)->limit(1)->value('cd'),
                // htmlspecialchars(addslashes()) dentro de E'...': en la BD queda solo el htmlspecialchars
                'comment' => htmlspecialchars($comment, ENT_COMPAT | ENT_HTML401),
                'disp_date' => (string) strtotime(date('Y/m/d')),
                'pic_flg' => '1',
                'disp_flg' => '1',
                'hyouji_flg' => '1',
                'touroku_date' => $now,
                'update_date' => $now,
            ]);

            return $cd;
        });

        $this->storeImages($cd, $file);

        return $cd;
    }

    private function storeImages(int $cd, UploadedFile $file): void
    {
        $disk = Storage::disk('public');
        $dir = "hp/uploads/c1/photoalbum/{$cd}";
        $parts = explode('.', (string) $file->getClientOriginalName());
        $extension = '.'.end($parts);
        $original = (string) file_get_contents((string) $file->getRealPath());
        $disk->put("{$dir}/0_moto{$extension}", $original);

        try {
            $image = new \Imagick;
            $image->readImageBlob($original);
            $image->setImageFormat('jpeg');
            if ($extension !== '.jpg') {
                $disk->put("{$dir}/0_moto.jpg", $image->getImageBlob());
            }
            foreach (self::SIZES as $suffix => $geometry) {
                $copy = clone $image;
                [$width, $height] = array_map('intval', explode('x', rtrim($geometry, '>')));
                if (! str_ends_with($geometry, '>')) {
                    $copy->thumbnailImage($width, $height, true);
                } elseif ($copy->getImageWidth() > $width) {
                    $copy->thumbnailImage($width, 0);
                }
                $disk->put("{$dir}/0_{$suffix}.jpg", $copy->getImageBlob());
            }
        } catch (\ImagickException $e) {
            // El legacy seguía aunque convert fallara: queda el original
            Log::warning('mpa_albumUpdate: no se pudieron generar los tamaños', ['cd' => $cd, 'error' => $e->getMessage()]);
        }
    }
}
