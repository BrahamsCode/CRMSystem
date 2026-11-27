<?php

namespace App\Services\Terminales;

use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;
use GdImage;

/**
 * QR de ペーパーサイネージ. El legacy pedía por HTTP /system/category3/mail_mag/qr_img.php?d=…&t=J (JPEG,
 * corrección M). Ese script (S3) todavía no existe en Laravel: se genera aquí con bacon/bacon-qr-code
 * (ya en composer.json) y GD, en JPEG como `t=J`. Pedido anotado: un servicio común de QR para qr_img.php.
 */
class PaperCardQr
{
    private const SIZE = 280;

    public function jpeg(string $data): string
    {
        $png = (new Writer(new GDLibRenderer(self::SIZE)))->writeString($data);

        $image = imagecreatefromstring($png);
        if (! $image instanceof GdImage) {
            return '';
        }

        ob_start();
        imagejpeg($image, null, 90);

        return (string) ob_get_clean();
    }

    /** Lo que codifica el QR de una tarjeta (list.php: http://<SERVER_NAME>/ptag/<id>) */
    public static function ptagUrl(string $host, string $id): string
    {
        return 'http://'.$host.'/ptag/'.$id;
    }
}
