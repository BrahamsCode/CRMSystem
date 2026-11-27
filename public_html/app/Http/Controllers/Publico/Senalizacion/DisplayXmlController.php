<?php

namespace App\Http\Controllers\Publico\Senalizacion;

use App\Http\Controllers\Controller;
use App\Services\Terminales\SignageFeedBuilder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * Sirve los XML que lee el reproductor de señalización en la misma URL que el
 * legacy: /hp/uploads/c7/display/<cd>.xml (programa) y /hp/uploads/c7/display/floormap.xml.
 *
 * El legacy los escribía al guardar (xml_up.php / xml_info_up.php) y el servidor
 * web los entregaba como archivo estático. Aquí se entrega el archivo guardado
 * en el disco public; si todavía no existe (por ejemplo, un programa importado
 * que nadie volvió a guardar) se genera desde la BD y se guarda.
 */
class DisplayXmlController extends Controller
{
    public function __invoke(SignageFeedBuilder $builder, string $file): Response
    {
        $path = SignageFeedBuilder::DIR.'/'.$file;
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            if ($file === 'floormap.xml') {
                $builder->writeFloormap();
            } else {
                $builder->writeProgram((int) basename($file, '.xml'));
            }
        }

        // Un programa inexistente da un archivo vacío, igual que xml_up.php sin datos
        return response($disk->get($path) ?? '', 200, [
            'Content-Type' => 'text/xml',
            'Last-Modified' => gmdate('D, d M Y H:i:s', $disk->lastModified($path)).' GMT',
        ]);
    }
}
