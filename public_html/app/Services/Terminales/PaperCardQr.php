<?php

namespace App\Services\Terminales;

use App\Services\Promotions\QrImage;

/**
 * QR de ペーパーサイネージ. El legacy pedía por HTTP /system/category3/mail_mag/qr_img.php?d=…&t=J (JPEG,
 * corrección M, módulo de 8 px). Ahora usa el servicio común de qr_img.php (App\Services\Promotions\QrImage),
 * con los mismos valores por defecto que ese `t=J`.
 */
class PaperCardQr
{
    public function __construct(private readonly QrImage $qr) {}

    public function jpeg(string $data): string
    {
        if ($data === '') {
            return '';
        }

        return $this->qr->render($data, 'M', null, 0, QrImage::JPEG);
    }

    /** Lo que codifica el QR de una tarjeta (list.php: http://<SERVER_NAME>/ptag/<id>) */
    public static function ptagUrl(string $host, string $id): string
    {
        return 'http://'.$host.'/ptag/'.$id;
    }
}
