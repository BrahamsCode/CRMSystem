<?php

namespace App\Support;

use Closure;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV de descarga como el legacy: Shift_JIS (`SJIS-win`, lo abren en Excel en Japón), `text/x-csv`, fin de línea
 * CRLF. Reemplaza los `header()` + `echo mb_convert_encoding(...)` de los `*csv*.php` con una `StreamedResponse`
 * que convierte y envía por bloques (no carga todo el archivo en memoria).
 *
 * Uso:
 *   return LegacyCsv::download('customer.csv', fn () => Customer::query()->cursor()->map(fn ($c) => [$c->id, $c->name]),
 *       header: ['顧客ID', '名前']);
 *
 * Opciones (`$options`): `quote` = 'all' (cada celda entre comillas, `"` → `""`; por defecto), 'minimal' (solo las
 * que llevan `,` `"` o salto de línea) o 'none' (tal cual, como los legacy que unían con comas);
 * `eol` = "\r\n"; `content_type` = 'text/x-csv'; `chunk` = filas por bloque (500).
 */
final class LegacyCsv
{
    public const ENCODING = 'SJIS-win';

    private const DEFAULTS = ['quote' => 'all', 'eol' => "\r\n", 'content_type' => 'text/x-csv', 'chunk' => 500];

    /**
     * @param  iterable<array<int|string, mixed>>|Closure(): iterable<array<int|string, mixed>>  $rows  filas (o un closure que las
     *         devuelve, para que la consulta corra al enviar); cada fila es una lista de celdas
     * @param  list<string>|null  $header  primera línea (opcional)
     */
    public static function download(string $filename, iterable|Closure $rows, ?array $header = null, array $options = []): StreamedResponse
    {
        $options += self::DEFAULTS;

        return response()->streamDownload(function () use ($rows, $header, $options) {
            $buffer = $header !== null ? self::line($header, $options) : '';
            $count = 0;
            foreach ($rows instanceof Closure ? $rows() : $rows as $row) {
                $buffer .= self::line((array) $row, $options);
                if (++$count % $options['chunk'] === 0) {
                    echo self::encode($buffer);
                    $buffer = '';
                    flush();
                }
            }
            echo self::encode($buffer);
        }, $filename, ['Content-Type' => $options['content_type']]);
    }

    /** Una línea en UTF-8 (sin convertir), con el fin de línea incluido */
    public static function line(array $cells, array $options = []): string
    {
        $options += self::DEFAULTS;

        return implode(',', array_map(fn ($cell) => self::cell((string) $cell, $options['quote']), array_values($cells))) . $options['eol'];
    }

    /** UTF-8 → SJIS-win (los caracteres sin equivalente quedan como `?`, igual que el legacy) */
    public static function encode(string $utf8): string
    {
        return mb_convert_encoding($utf8, self::ENCODING, 'UTF-8');
    }

    private static function cell(string $value, string $quote): string
    {
        $needs = match ($quote) {
            'none' => false,
            'minimal' => strpbrk($value, ",\"\r\n") !== false,
            default => true,
        };

        return $needs ? '"' . str_replace('"', '""', $value) . '"' : $value;
    }
}
