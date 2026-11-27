<?php

namespace App\Services\Promotions;

/**
 * 宛名ラベル A4×12 (dm/aterna_a4_12.php): PDF con 2 columnas × 6 filas de etiquetas por hoja,
 * con las mismas medidas (mm) y la misma letra de 6 pt que el legacy.
 *
 * El legacy usaba FPDF + `japanese.php` (fuente CID `KozMinPro-Regular-Acro` sin incrustar, texto en Shift_JIS).
 * S1 aún no fijó paquete PDF (CONVENCIONES §4, NUCLEO §9), así que este escritor mínimo genera el mismo
 * tipo de PDF: fuente CID japonesa estándar sin incrustar (`KozMinPro-Regular`, Adobe-Japan1, la que
 * sustituye cualquier visor) con codificación `UniJIS-UTF16-H`, así que no se pierden caracteres fuera de
 * Shift_JIS. Cuando S1 fije el paquete, basta con reimplementar render() con él.
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

    /** A4 en puntos */
    private const PAGE_W = 595.28;

    private const PAGE_H = 841.89;

    /** Anchos de la letra proporcional (CID 1〜95 = ASCII 32〜126), de `$SJIS_widths` de japanese.php */
    private const ASCII_WIDTHS = [278, 299, 353, 614, 614, 721, 735, 216, 323, 323, 449, 529, 219, 306, 219, 453, 614, 614, 614, 614,
        614, 614, 614, 614, 614, 614, 219, 219, 529, 529, 529, 486, 744, 646, 604, 617, 681, 567, 537, 647, 738, 320, 433, 637, 566,
        904, 710, 716, 605, 716, 623, 517, 601, 690, 668, 990, 681, 634, 578, 316, 614, 316, 529, 500, 387, 509, 566, 478, 565, 503,
        337, 549, 580, 275, 266, 544, 276, 854, 579, 550, 578, 566, 410, 444, 340, 575, 512, 760, 503, 529, 453, 326, 380, 326, 387];

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
     * @param  iterable<object|array>  $customers  filas de tb_customer en el orden de impresión
     * @return string el PDF
     */
    public function render(iterable $customers): string
    {
        $pages = [];
        $ops = '';
        $slot = 0;
        foreach ($customers as $customer) {
            if ($slot > 0 && $slot % (self::ROWS_PER_PAGE * 2) === 0) {
                $pages[] = $ops;
                $ops = '';
            }
            $j = intdiv($slot % (self::ROWS_PER_PAGE * 2), 2);
            $x = self::X_MA + self::YOKO_START + ($slot % 2 === 1 ? self::YOKO + self::SUKIMA : 0);
            $y = self::Y_MA + self::TATE_START + $j * self::TATE;
            foreach (self::lines($customer) as $n => $text) {
                $ops .= $this->text($x, $y + [0, 3, 5.5, 8.5][$n], $text);
            }
            $slot++;
        }
        $pages[] = $ops; // siempre al menos una hoja, como el AddPage() inicial del legacy

        return $this->document($pages);
    }

    /** Texto con la línea base en (x, y) mm desde la esquina superior izquierda, como FPDF::Text() */
    private function text(float $x, float $y, string $text): string
    {
        if ($text === '') {
            return '';
        }
        $k = 72 / 25.4;
        $hex = strtoupper(bin2hex(mb_convert_encoding($text, 'UTF-16BE', 'UTF-8')));

        return sprintf("BT /F1 %d Tf %.2F %.2F Td <%s> Tj ET\n", self::FONT_SIZE, $x * $k, self::PAGE_H - $y * $k, $hex);
    }

    /** @param list<string> $pages contenido de cada hoja */
    private function document(array $pages): string
    {
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        // 2 = Pages (al final, cuando se conocen las hojas)
        $objects[3] = '<< /Type /Font /Subtype /Type0 /BaseFont /KozMinPro-Regular-UniJIS-UTF16-H /Encoding /UniJIS-UTF16-H /DescendantFonts [4 0 R] >>';
        $objects[4] = '<< /Type /Font /Subtype /CIDFontType0 /BaseFont /KozMinPro-Regular'
            .' /CIDSystemInfo << /Registry (Adobe) /Ordering (Japan1) /Supplement 5 >> /FontDescriptor 5 0 R'
            .' /DW 1000 /W [1 ['.implode(' ', self::ASCII_WIDTHS).'] 231 325 500 631 [500] 326 389 500] >>';
        $objects[5] = '<< /Type /FontDescriptor /FontName /KozMinPro-Regular /Flags 6 /FontBBox [0 -200 1000 900]'
            .' /ItalicAngle 0 /Ascent 800 /Descent -200 /CapHeight 800 /StemV 60 >>';

        $kids = [];
        $n = 6;
        foreach ($pages as $content) {
            $kids[] = "$n 0 R";
            $objects[$n] = sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 3 0 R >> >> /Contents %d 0 R >>',
                self::PAGE_W, self::PAGE_H, $n + 1);
            $objects[$n + 1] = '<< /Length '.strlen($content)." >>\nstream\n".$content.'endstream';
            $n += 2;
        }
        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.count($pages).' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objects as $i => $body) {
            $offsets[$i] = strlen($pdf);
            $pdf .= "$i 0 obj\n$body\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= 'xref'."\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= 'trailer'."\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n$xref\n%%EOF\n";

        return $pdf;
    }
}
