<?php

namespace App\Http\Controllers\Admin\Reservations;

use App\Http\Controllers\Admin\Reservations\Concerns\SelectsLegacyShop;
use App\Http\Controllers\Controller;
use App\Services\Reservations\ReservationPrintService;
use Illuminate\Http\Response;
use Illuminate\Support\Js;
use Illuminate\View\View;

/**
 * 予約プリント (legacy category9/yoyaku/yoyakuPrint.php + ajaxYoyakuPrint.php): página (en un iframe del 予約表
 * cuando リアルタイム印刷 está activa) que cada 3 s pide la siguiente reserva pendiente y la manda a la impresora
 * OFSC local desde el navegador.
 */
class PrinterController extends Controller
{
    use SelectsLegacyShop;

    public function __construct(private readonly ReservationPrintService $printer) {}

    /** yoyakuPrint.php */
    public function index(): View
    {
        return view('admin.reservas.printer');
    }

    /**
     * ajaxYoyakuPrint.php: vacío si no hay nada pendiente; si no, el <script> que envía el XML (print_flg '1' → '2').
     * El legacy tomaba la pendiente de cualquier tienda; un no master solo toma las de su tienda.
     */
    public function poll(): Response
    {
        $xml = $this->printer->takeNext($this->isMasterUser() ? null : $this->sessionShopCd());
        if ($xml === null) {
            return response('');
        }

        return response("<script type=\"text/javascript\"><!--\n\nprintContent = " . Js::from($xml)->toHtml() . ";\n\nsendMessage(printContent,NORMAL_MODE);\n--></script>");
    }
}
