<?php

namespace App\Http\Controllers\Publico\Senalizacion;

use App\Http\Controllers\Controller;
use App\Services\Senalizacion\SignageLegacyView;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * /signage/upload/<w>x<h>/<archivo> (y upload/movie/…, upload/<nombre>): los archivos de signage/ del legacy,
 * ahora en el disco public bajo signage/upload/. Los leen el reproductor y las pantallas de gestión.
 */
class SignageUploadController extends Controller
{
    public function __invoke(string $path): StreamedResponse
    {
        $file = SignageLegacyView::ROOT.'/upload/'.$path;
        abort_if(str_contains($path, '..') || ! Storage::disk('public')->exists($file), 404);

        return Storage::disk('public')->response($file);
    }
}
