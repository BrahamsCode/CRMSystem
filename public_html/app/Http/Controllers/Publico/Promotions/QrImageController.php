<?php

namespace App\Http\Controllers\Publico\Promotions;

use App\Http\Controllers\Controller;
use App\Services\Promotions\QrImage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

/**
 * system/category3/mail_mag/qr_img.php: imagen PNG/JPEG del QR con los parámetros d, e, s, v, t del legacy.
 * Sin login, como el legacy: la usan como <img src> appview (memberId.php, qr_code.php, sin sesión del personal),
 * las pantallas QRコード情報 y, por HTTP, card_signage y QR印刷 del legacy.
 */
class QrImageController extends Controller
{
    public function __invoke(Request $request, QrImage $qr): Response
    {
        try {
            [$bytes, $contentType] = $qr->fromQuery($request->query());
        } catch (InvalidArgumentException $e) {
            // El legacy terminaba con error fatal (500); aquí, petición incorrecta con el mismo mensaje en texto plano
            return response($e->getMessage(), 400, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        return response($bytes, 200, ['Content-Type' => $contentType]);
    }
}
