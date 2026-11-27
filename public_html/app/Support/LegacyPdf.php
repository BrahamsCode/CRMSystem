<?php

namespace App\Support;

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * PDF con texto japonés (mPDF 8.3, el mismo que el legacy en signage/pdf/invoice.php): HTML o vista Blade → PDF.
 *
 * Por defecto `mode => 'ja'` con autoScriptToLang/autoLangToFont: el japonés sale con la fuente CJK que trae mPDF
 * (Sun-ExtA, incrustada), sin instalar nada en el servidor. Fuentes propias (IPAex, Noto…): dejar los .ttf en
 * `config('crm.pdf.font_dir')` y declararlos en `config('crm.pdf.fonts')`; se registran solo si el archivo existe,
 * y `config('crm.pdf.default_font')` elige la de por defecto.
 *
 * Uso:
 *   return LegacyPdf::downloadView('invoice.pdf', 'admin.mypage.pdf.invoice', ['rows' => $rows]);
 *   $bytes = LegacyPdf::fromHtml('<p>請求書</p>', ['format' => 'A4-L']);
 *   $mpdf = LegacyPdf::make(['format' => [210, 297]]);   // para maquetar a mano (etiquetas DM: Write/SetXY…)
 */
final class LegacyPdf
{
    /** Instancia de mPDF configurada; `$config` pisa los valores por defecto (format, margin_*, orientation…) */
    public static function make(array $config = []): Mpdf
    {
        $base = [
            'mode' => 'ja',
            'format' => 'A4',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'tempDir' => self::tempDir(),
        ];

        $fonts = self::customFonts();
        if ($fonts) {
            $base['fontDir'] = array_merge((new ConfigVariables)->getDefaults()['fontDir'], [config('crm.pdf.font_dir')]);
            $base['fontdata'] = (new FontVariables)->getDefaults()['fontdata'] + $fonts;
            $default = (string) config('crm.pdf.default_font');
            if ($default !== '' && isset($fonts[$default])) {
                // con una fuente japonesa propia no hace falta que mPDF cambie de fuente por idioma
                // (mode 'ja' impondría Sun-ExtA por encima de default_font)
                $base['mode'] = 'utf-8';
                $base['default_font'] = $default;
                $base['autoLangToFont'] = false;
            }
        }

        return new Mpdf(array_replace($base, $config));
    }

    /** HTML → bytes del PDF */
    public static function fromHtml(string $html, array $config = []): string
    {
        $mpdf = self::make($config);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }

    /** Vista Blade → bytes del PDF */
    public static function fromView(string $view, array $data = [], array $config = []): string
    {
        return self::fromHtml(view($view, $data)->render(), $config);
    }

    /**
     * Descarga (o se muestra en el navegador con `$inline`), como Output($name, 'D'|'I') del legacy.
     * El PDF se genera al enviar la respuesta.
     */
    public static function download(string $filename, string $html, array $config = [], bool $inline = false): StreamedResponse
    {
        $response = response()->streamDownload(function () use ($html, $config) {
            echo self::fromHtml($html, $config);
        }, $filename, ['Content-Type' => 'application/pdf']);

        if ($inline) {
            $response->headers->set('Content-Disposition', str_replace('attachment', 'inline', (string) $response->headers->get('Content-Disposition')));
        }

        return $response;
    }

    public static function downloadView(string $filename, string $view, array $data = [], array $config = [], bool $inline = false): StreamedResponse
    {
        return self::download($filename, view($view, $data)->render(), $config, $inline);
    }

    /** Fuentes de config('crm.pdf.fonts') cuyos .ttf existen: [familia => ['R' => 'archivo.ttf', ...]] */
    private static function customFonts(): array
    {
        $dir = (string) config('crm.pdf.font_dir');

        return collect((array) config('crm.pdf.fonts', []))
            ->filter(fn ($files) => is_array($files) && $files && collect($files)->every(fn ($file) => is_file($dir . '/' . $file)))
            ->all();
    }

    private static function tempDir(): string
    {
        $dir = storage_path('framework/cache/mpdf');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir;
    }
}
