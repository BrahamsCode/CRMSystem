<?php

/*
 * Lectura de los PDF de mPDF (App\Support\LegacyPdf) en las pruebas: descomprime los streams y extrae los
 * textos `x y Td (…) Tj` (mPDF escribe UTF-16BE con fuentes CID Identity), con su posición en puntos.
 */

/** @return list<string> streams del PDF, descomprimidos si van con FlateDecode */
function pdfStreams(string $pdf): array
{
    preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $m);

    return array_map(fn ($raw) => @gzuncompress($raw) ?: $raw, $m[1]);
}

/** @return list<array{x: float, y: float, text: string}> textos del PDF (x, y en puntos desde abajo a la izquierda) */
function pdfTexts(string $pdf): array
{
    $out = [];
    foreach (pdfStreams($pdf) as $stream) {
        preg_match_all('/([\d.]+) ([\d.]+) Td\s*\(((?:\\\\.|[^\\\\)])*)\) Tj/s', $stream, $matches, PREG_SET_ORDER);
        foreach ($matches as $t) {
            $raw = preg_replace_callback('/\\\\([0-7]{1,3}|.)/s', fn ($e) => match ($e[1]) {
                'n' => "\n", 'r' => "\r", 't' => "\t", 'b' => "\x08", 'f' => "\f",
                default => ctype_digit($e[1]) ? chr(octdec($e[1])) : $e[1],
            }, $t[3]);
            $out[] = ['x' => (float) $t[1], 'y' => (float) $t[2], 'text' => mb_convert_encoding($raw, 'UTF-8', 'UTF-16BE')];
        }
    }

    return $out;
}

/** Solo los textos, en orden */
function pdfTextList(string $pdf): array
{
    return array_column(pdfTexts($pdf), 'text');
}
