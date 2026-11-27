<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Sirve los estáticos del legacy de public/legacy/ en sus URL absolutas originales
 * (/system/css/…, /common/…). En Apache lo hace antes public/.htaccess sin pasar por PHP;
 * esta ruta es el respaldo para `php artisan serve` y otros servidores (NUCLEO.md, "Estáticos").
 */
class LegacyAssetController extends Controller
{
    public const EXTENSIONS = 'css|js|gif|png|jpe?g|svg|ico|webp|bmp|swf|woff2?|ttf|otf|eot|htc|map|html?|xml|json|txt|pdf|mp3|mp4|wav';

    public function __invoke(string $path): BinaryFileResponse
    {
        $base = realpath(public_path('legacy'));
        $file = realpath(public_path('legacy/' . $path));

        abort_unless($base && $file && str_starts_with($file, $base . DIRECTORY_SEPARATOR) && is_file($file), 404);

        $headers = ['Cache-Control' => 'public, max-age=86400'];
        if ($type = self::TYPES[strtolower(pathinfo($file, PATHINFO_EXTENSION))] ?? null) {
            $headers['Content-Type'] = $type;
        }

        return response()->file($file, $headers);
    }

    /** Tipos que el detector por contenido adivina mal (CSS y JS salen como text/plain) */
    private const TYPES = [
        'css' => 'text/css; charset=utf-8',
        'js' => 'text/javascript; charset=utf-8',
        'map' => 'application/json',
        'json' => 'application/json',
        'svg' => 'image/svg+xml',
        'htc' => 'text/x-component',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'html' => 'text/html; charset=utf-8',
        'htm' => 'text/html; charset=utf-8',
    ];
}
