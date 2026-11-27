<?php

namespace App\Http\Controllers\Admin\Senalizacion;

use App\Models\Senalizacion\SignageWebCustomer;
use App\Support\LegacyPdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * signage/pdf/invoice.php y receipt.php: 請求書 / 領収書 de GoTel (PROVISIONAL: en pausa hasta que Japón confirme el uso).
 * Como el legacy: ?cd=<customer.cd>; con ?mode=… devuelve el HTML (vista previa con pdf.css enlazado) y sin `mode`
 * descarga `invoice_<YmdHis>.pdf` / `receipt_<YmdHis>.pdf` con LegacyPdf. El legacy se pedía el HTML a sí mismo por
 * HTTP (SIGNAGE_PDF_BASE_URL); aquí se pinta directamente. Los importes son los fijos de muestra del legacy
 * (`$_pay_data_array` vacío): sin cliente sale el documento sin nombre, igual que hoy.
 */
class BillingPdfController extends SignageController
{
    private const TITLES = ['invoice' => '請求書', 'receipt' => '領収書'];

    public function invoice(Request $request): Response|StreamedResponse
    {
        return $this->document($request, 'invoice');
    }

    public function receipt(Request $request): Response|StreamedResponse
    {
        return $this->document($request, 'receipt');
    }

    private function document(Request $request, string $type): Response|StreamedResponse
    {
        $edit = $request->query->has('mode');
        $html = view('admin.senalizacion.pdf.billing', [
            'title' => self::TITLES[$type],
            'cd' => $request->input('cd'),
            'customer' => SignageWebCustomer::query()->whereKey((int) $request->input('cd'))->first(),
            'edit' => $edit,
            'css' => $edit ? '' : (string) @file_get_contents(public_path('legacy/signage/css/pdf.css')),
        ])->render();

        return $edit ? response($html) : LegacyPdf::download($type.'_'.date('YmdHis').'.pdf', $html);
    }
}
