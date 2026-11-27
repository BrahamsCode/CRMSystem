<?php

namespace App\Services\Promotions;

use App\Support\LegacyPdf;

/**
 * 宛名ラベル A4×12 (dm/aterna_a4_12.php): PDF con 2 columnas × 6 filas de etiquetas por hoja,
 * con las mismas medidas (mm) y la misma letra de 6 pt que el legacy.
 *
 * El legacy usaba FPDF + `japanese.php` (`$pdf->Text(x, y, texto)` con la línea base en (x, y) mm). Aquí, mPDF
 * de S1 (App\Support\LegacyPdf, NUCLEO §11) sin márgenes y `WriteText()`, que coloca el texto igual que FPDF::Text().
 */
class DmLabelPdf
{
    // Medidas de aterna_a4_12.php (mm)
    public const X_MA = 19.3;

    public const YOKO = 83.8;

    public const SUKIMA = 3.8;

    public const YOKO_START = 10;

    public const Y_MA = 21.5;

    public const TATE = 42.3;

    public const TATE_START = 10;

    public const FONT_SIZE = 6;

    /** Filas de etiquetas por hoja (2 por fila = 12) */
    public const ROWS_PER_PAGE = 6;

    /** Desplazamiento (mm) de cada una de las 4 líneas respecto a la primera */
    public const LINE_OFFSETS = [0, 3, 5.5, 8.5];

    /**
     * Las 4 líneas de una etiqueta, como el legacy: 〒zip / pref+add+add_number / add_build / name.
     *
     * @return list<string>
     */
    public static function lines(object|array $customer): array
    {
        $c = (array) $customer;

        return [
            '〒'.($c['zip'] ?? ''),
            ($c['pref'] ?? '').($c['add'] ?? '').($c['add_number'] ?? ''),
            (string) ($c['add_build'] ?? ''),
            (string) ($c['name'] ?? ''),
        ];
    }

    /**
     * Textos de cada hoja con su posición (mm, línea base), en el orden de aterna_a4_12.php.
     * Siempre al menos una hoja, como el AddPage() inicial del legacy; los textos vacíos no se escriben.
     *
     * @param  iterable<object|array>  $customers  filas de tb_customer en el orden de impresión
     * @return list<list<array{x: float, y: float, text: string}>>
     */
    public static function layout(iterable $customers): array
    {
        $pages = [[]];
        $slot = 0;
        foreach ($customers as $customer) {
            if ($slot > 0 && $slot % (self::ROWS_PER_PAGE * 2) === 0) {
                $pages[] = [];
            }
            $j = intdiv($slot % (self::ROWS_PER_PAGE * 2), 2);
            $x = self::X_MA + self::YOKO_START + ($slot % 2 === 1 ? self::YOKO + self::SUKIMA : 0);
            $y = self::Y_MA + self::TATE_START + $j * self::TATE;
            foreach (self::lines($customer) as $n => $text) {
                if ($text !== '') {
                    $pages[array_key_last($pages)][] = ['x' => (float) $x, 'y' => (float) ($y + self::LINE_OFFSETS[$n]), 'text' => $text];
                }
            }
            $slot++;
        }

        return $pages;
    }

    /**
     * @param  iterable<object|array>  $customers  filas de tb_customer en el orden de impresión
     * @return string el PDF
     */
    public function render(iterable $customers): string
    {
        $mpdf = LegacyPdf::make(['format' => 'A4', 'margin_left' => 0, 'margin_right' => 0, 'margin_top' => 0, 'margin_bottom' => 0,
            'margin_header' => 0, 'margin_footer' => 0]);
        // WriteText() no cambia de fuente por idioma: la CJK de mPDF, o la fuente japonesa propia si S1 la configuró
        $font = $mpdf->autoLangToFont ? 'sun-exta' : $mpdf->default_font;

        foreach (self::layout($customers) as $texts) {
            $mpdf->AddPage();
            $mpdf->SetFont($font, '', self::FONT_SIZE);
            foreach ($texts as $t) {
                $mpdf->WriteText($t['x'], $t['y'], $t['text']);
            }
        }

        return $mpdf->Output('', 'S');
    }
}
