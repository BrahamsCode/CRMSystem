<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Http\Controllers\Controller;
use App\Services\Promotions\QrBaseInfo;
use App\Services\Promotions\QrImage;
use App\Services\Promotions\QrPrintPdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * メールマガジン基本情報 / QRコード情報 (category3/mail_mag/base*.php), descarga del QR y QR印刷 (qr_print_pdf.php).
 * Permisos como el legacy: solo la sesión del personal (las condiciones del menú no se comprueban en la pantalla).
 * La imagen (qr_img.php) es pública, como en el legacy: Publico\Promotions\QrImageController.
 */
class QrBaseController extends Controller
{
    /** URL del legacy → type */
    public const LEGACY_FILES = [
        'base' => 'base',
        'base_mailmaga' => 'mailmaga',
        'base_mycolle' => 'mycolle',
        'base_point' => 'point',
    ];

    public function __construct(private readonly QrImage $qr) {}

    /** base.php, base_mailmaga.php, base_mycolle.php, base_point.php (?type=) */
    public function show(Request $request, QrBaseInfo $info): View
    {
        $type = (string) $request->query('type', 'base');
        abort_unless(isset(QrBaseInfo::TYPES[$type]), 404);

        return view('admin.promocion.mail_mag.base', $info->forType($type, $request->getHost()) + ['info' => $info]);
    }

    /**
     * ダウンロード: el legacy guardaba el QR en hp/uploads/c3/shop_qr/ y lo servía con base.php?is_download=1&file_path=…
     * (cualquier archivo del servidor). Aquí se genera al momento con la misma query, en JPEG (el nombre es <tienda>.jpg).
     */
    public function download(Request $request): Response
    {
        parse_str((string) $request->query('qr', ''), $query);
        $query['t'] = 'J';
        try {
            [$bytes] = $this->qr->fromQuery($query);
        } catch (InvalidArgumentException $e) {
            return response($e->getMessage(), 400, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }
        $name = str_replace(['/', '\\', '"'], '_', (string) $request->query('name', 'qr')).'.jpg';
        $fallback = preg_match('/^[\x20-\x7E]+$/', $name) && ! str_contains($name, '%') ? $name : 'qr.jpg';

        return response($bytes, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $name, $fallback),
        ]);
    }

    /** qr_print_pdf.php: QR印刷 de los marcados (print_name[], qr_code[]) */
    public function print(Request $request, QrPrintPdf $pdf): Response|View
    {
        // Mismo índice en los dos arrays, como $_REQUEST["qr_code"][$key] del legacy
        $names = array_map('strval', array_filter((array) $request->input('print_name', []), 'is_scalar'));
        $urls = array_map(fn ($u) => is_scalar($u) ? (string) $u : null, (array) $request->input('qr_code', []));
        if ($names === []) {
            return view('admin.promocion.mail_mag.qr_print_empty');
        }

        return response($pdf->render($names, $urls), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="qr_print.pdf"',
        ]);
    }

    /** GET de las URL del legacy (menú left_navi3) → pantalla nueva con su type */
    public function legacy(Request $request, string $file): RedirectResponse
    {
        return redirect()->route('admin.promocion.base.show', ['type' => self::LEGACY_FILES[$file]]);
    }
}
