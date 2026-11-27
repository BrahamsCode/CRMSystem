<?php

namespace App\Services\Promotions;

use App\Support\LegacyPdf;
use InvalidArgumentException;

/**
 * QR印刷 (mail_mag/qr_print_pdf.php). El legacy, pese al nombre, devolvía un HTML para imprimir desde el
 * navegador: tabla de 4 columnas al 25 % con el nombre (`print_name[]`) en un <p> y debajo la imagen del QR
 * (`qr_code[]`, una URL de qr_img.php) al 100 % del ancho de la celda. Aquí ese mismo HTML se pasa a mPDF
 * (App\Support\LegacyPdf, NUCLEO §11) y sale un PDF A4.
 *
 * Las imágenes se generan aquí a partir de la query de cada URL (QrImage) y se pasan a mPDF como `var:`:
 * no se pide nada por HTTP; una URL que no sea de qr_img.php deja la celda solo con el nombre.
 */
class QrPrintPdf
{
    public const COLUMNS = 4;

    /** Márgenes laterales de la hoja (mm) */
    public const MARGIN = 10;

    /**
     * Ancho de la imagen (mm): el de la columna (A4 menos márgenes, entre 4) con 1 mm de hueco a cada lado.
     * El legacy ponía `style="width:100%"`, que mPDF no aplica dentro de una celda de tabla.
     */
    public const IMAGE_WIDTH = (210 - 2 * self::MARGIN) / self::COLUMNS - 2;

    public function __construct(private readonly QrImage $qr) {}

    /**
     * HTML de qr_print_pdf.php y las imágenes que referencia (`var:qrN`).
     *
     * @param  array<array-key, string>  $names  print_name[]
     * @param  array<array-key, string|null>  $urls  qr_code[] (mismo índice)
     * @return array{html: string, images: array<string, string>}
     */
    public function html(array $names, array $urls): array
    {
        $images = [];
        $cells = [];
        foreach ($names as $key => $name) {
            $img = '';
            $query = QrImage::queryFromUrl((string) ($urls[$key] ?? ''));
            if ($query !== null) {
                $o = QrImage::optionsFromQuery($query);
                try {
                    $var = 'qr'.count($images);
                    $images[$var] = $this->qr->render($o['data'], $o['ecc'], $o['size'], $o['version'], QrImage::PNG);
                    $img = "\n\t\t\t\t\t\t".'<img src="var:'.$var.'" style="width:'.self::IMAGE_WIDTH.'mm">';
                } catch (InvalidArgumentException) {
                    // Sin datos o demasiado grande: el legacy mostraba la imagen rota
                }
            }
            // width en cada <td>: mPDF no aplica los <col width> del legacy
            $cells[] = "\t\t\t\t\t<td width=\"25%\">\n\t\t\t\t\t\t<p>".e((string) $name).'</p>'.$img."\n\t\t\t\t\t</td>\n";
        }

        // Filas de 4 celdas; la última se completa con un <td colspan>, como el legacy
        $rows = '';
        foreach (array_chunk($cells, self::COLUMNS) as $row) {
            $missing = self::COLUMNS - count($row);
            $rows .= "\t\t\t<tr>\n".implode('', $row).($missing > 0 ? "\t\t\t\t\t<td colspan=\"$missing\"></td>\n" : '')."\t\t\t</tr>\n";
        }

        $html = <<<HTML
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<title>QR印刷</title>
<style>
table {
	width: 100%;
}
table td{
	text-align: center;
}
table td p {
	margin-bottom :0px;
}
</style>
</head>

<body>
	<table>
		<colgroup>
			<col width="25%">
			<col width="25%">
			<col width="25%">
			<col width="25%">
		</colgroup>
		<tbody>
$rows		</tbody>
	</table>
</body>
</html>
HTML;

        return ['html' => $html, 'images' => $images];
    }

    /**
     * @param  array<array-key, string>  $names  print_name[]
     * @param  array<array-key, string|null>  $urls  qr_code[] (mismo índice)
     * @return string el PDF
     */
    public function render(array $names, array $urls): string
    {
        ['html' => $html, 'images' => $images] = $this->html($names, $urls);

        $mpdf = LegacyPdf::make(['format' => 'A4', 'margin_left' => self::MARGIN, 'margin_right' => self::MARGIN]);
        foreach ($images as $var => $bytes) {
            $mpdf->imageVars[$var] = $bytes;
        }
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }
}
