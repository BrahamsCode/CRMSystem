<?php

namespace App\Http\Controllers\Publico\Nucleo;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Archivos subidos del legacy en su URL original `/hp/uploads/<ruta>` (fotos, imágenes de mypage,
 * productos…): se guardan en el disco `public` de Storage con la misma ruta relativa `hp/uploads/<ruta>`.
 * Las rutas específicas de un módulo bajo /hp/uploads/ se registran antes y tienen prioridad.
 */
class HpUploadsController extends Controller
{
    public function __invoke(string $path): StreamedResponse
    {
        $relative = 'hp/uploads/' . $path;
        abort_if(str_contains($path, '..') || ! Storage::disk('public')->fileExists($relative), 404);

        return Storage::disk('public')->response($relative, null, ['Cache-Control' => 'public, max-age=3600']);
    }
}
