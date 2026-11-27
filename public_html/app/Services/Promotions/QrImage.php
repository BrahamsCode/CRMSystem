<?php

namespace App\Services\Promotions;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Common\Version;
use BaconQrCode\Encoder\ByteMatrix;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Exception\WriterException;
use GdImage;
use InvalidArgumentException;

/**
 * QR de `system/category3/mail_mag/qr_img.php` (QRcode image PHP scripts 0.50g de Y.Swetake) con
 * bacon/bacon-qr-code + GD. Mismos parámetros GET que el legacy:
 *
 *   d = datos (se aplica rawurldecode() otra vez sobre lo ya decodificado, como el legacy)
 *   e = corrección L/M/Q/H (por defecto M; cualquier otro valor = M)
 *   s = tamaño del módulo en px (por defecto PNG 4, JPEG 8)
 *   v = versión 1〜40 (por defecto automática)
 *   t = J/j → JPEG; cualquier otro valor → PNG
 *
 * Imagen igual que el legacy: (módulos + 8) × s px, con 4 módulos de margen blanco, en paleta de 2 colores;
 * máximo 1480 px de lado. Los datos se codifican como bytes tal cual, sin ECI (el legacy usaba el modo 8 bits
 * sin declarar codificación); numérico y alfanumérico se eligen igual que el legacy.
 *
 * No se replica el "structured append" (n, m, p, o): ningún llamador del legacy lo usa.
 */
class QrImage
{
    /** Lado máximo de la imagen (qr_img.php: "Too large image size") */
    public const MAX_SIZE = 1480;

    /** Margen (módulos) a cada lado, como las imágenes base `image/qrv<N>.png` del legacy */
    public const QUIET_ZONE = 4;

    public const PNG = 'png';

    public const JPEG = 'jpeg';

    /**
     * Opciones normalizadas a partir de la query de qr_img.php.
     *
     * @param  array<string, mixed>  $query
     * @return array{data: string, ecc: string, size: int, version: int, type: string}
     */
    public static function optionsFromQuery(array $query): array
    {
        $type = in_array($query['t'] ?? null, ['J', 'j'], true) ? self::JPEG : self::PNG;
        $size = is_numeric($query['s'] ?? null) && (int) $query['s'] > 0 ? (int) $query['s'] : ($type === self::JPEG ? 8 : 4);
        $ecc = strtoupper(is_string($query['e'] ?? null) ? $query['e'] : '');
        $version = is_numeric($query['v'] ?? null) ? (int) $query['v'] : 0;

        return [
            'data' => rawurldecode(is_string($query['d'] ?? null) ? $query['d'] : ''),
            'ecc' => in_array($ecc, ['L', 'M', 'Q', 'H'], true) ? $ecc : 'M',
            'size' => $size,
            'version' => $version >= 1 && $version <= 40 ? $version : 0,
            'type' => $type,
        ];
    }

    /** Matriz del QR (sin margen). Con una versión forzada que no alcanza, se usa la automática */
    public function matrix(string $data, string $ecc = 'M', int $version = 0): ByteMatrix
    {
        if ($data === '') {
            throw new InvalidArgumentException('QRcode : Data do not exist.');
        }

        // Cada byte como un carácter ISO-8859-1: bacon los vuelve a convertir a los mismos bytes (modo 8 bits sin ECI)
        $content = mb_convert_encoding($data, 'UTF-8', 'ISO-8859-1');
        $level = ErrorCorrectionLevel::valueOf($ecc);
        try {
            $qr = Encoder::encode($content, $level, Encoder::DEFAULT_BYTE_MODE_ENCODING, $version ? Version::getVersionForNumber($version) : null, false);
        } catch (WriterException) {
            $qr = Encoder::encode($content, $level, Encoder::DEFAULT_BYTE_MODE_ENCODING, null, false);
        }

        return $qr->getMatrix();
    }

    /** Imagen GD de lado (módulos + 8) × $moduleSize */
    public function image(string $data, string $ecc = 'M', int $moduleSize = 4, int $version = 0): GdImage
    {
        $matrix = $this->matrix($data, $ecc, $version);
        $modules = $matrix->getWidth();
        $side = ($modules + 2 * self::QUIET_ZONE) * max(1, $moduleSize);
        if ($side > self::MAX_SIZE) {
            throw new InvalidArgumentException('QRcode : Too large image size');
        }

        $image = imagecreate($side, $side);
        imagecolorallocate($image, 255, 255, 255); // fondo (primer color de la paleta)
        $black = imagecolorallocate($image, 0, 0, 0);
        for ($y = 0; $y < $modules; $y++) {
            for ($x = 0; $x < $modules; $x++) {
                if ($matrix->get($x, $y) === 1) {
                    $x0 = ($x + self::QUIET_ZONE) * $moduleSize;
                    $y0 = ($y + self::QUIET_ZONE) * $moduleSize;
                    imagefilledrectangle($image, $x0, $y0, $x0 + $moduleSize - 1, $y0 + $moduleSize - 1, $black);
                }
            }
        }

        return $image;
    }

    /** Bytes de la imagen en PNG o JPEG (calidad por defecto de GD, como ImageJpeg() del legacy) */
    public function render(string $data, string $ecc = 'M', ?int $moduleSize = null, int $version = 0, string $type = self::PNG): string
    {
        $image = $this->image($data, $ecc, $moduleSize ?? ($type === self::JPEG ? 8 : 4), $version);

        ob_start();
        $type === self::JPEG ? imagejpeg($image) : imagepng($image);

        return (string) ob_get_clean();
    }

    /**
     * Imagen a partir de la query de qr_img.php.
     *
     * @param  array<string, mixed>  $query
     * @return array{0: string, 1: string} [bytes, content-type]
     */
    public function fromQuery(array $query): array
    {
        $o = self::optionsFromQuery($query);

        return [$this->render($o['data'], $o['ecc'], $o['size'], $o['version'], $o['type']), 'image/'.$o['type']];
    }

    /**
     * Query de una URL `…/qr_img.php?…` (la que guardan los `data-qr_code` de base*.php). Null si la URL no es de qr_img.php:
     * así QR印刷 genera la imagen aquí, sin pedir nada por HTTP.
     *
     * @return array<string, mixed>|null
     */
    public static function queryFromUrl(string $url): ?array
    {
        $path = (string) parse_url($url, PHP_URL_PATH);
        if (! str_ends_with($path, '/qr_img.php') && $path !== 'qr_img.php') {
            return null;
        }
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return $query;
    }
}
