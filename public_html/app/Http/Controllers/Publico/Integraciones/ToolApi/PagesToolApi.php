<?php

namespace App\Http\Controllers\Publico\Integraciones\ToolApi;

use App\Services\Integraciones\ToolApi\ToolApiOutput as Out;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Paginación y respuesta de lista comunes a los endpoints de lista de tool/api */
trait PagesToolApi
{
    /** ' OFFSET n LIMIT m' como el legacy: limit 100 por defecto y como máximo (un negativo hace fallar la consulta: NO_DATA) */
    private function page(Request $request): string
    {
        $offset = Out::form2Db($request->input('offset'));
        $limit = Out::form2Db($request->input('limit'));

        return ($offset !== '' ? ' OFFSET '.(int) $offset : '')
            .' LIMIT '.($limit !== '' ? min((int) $limit, 100) : 100);
    }

    /** { "status":"SUCCESS", "max":"<n>", "result":[ {…},{…} ] } o NO_DATA */
    private function listResponse(Request $request, ?array $rows): Response
    {
        if (! $rows) {
            return Out::envelope($request, 'NO_DATA', 0, '');
        }

        return Out::envelope($request, 'SUCCESS', count($rows), implode(',', array_map(fn ($row) => Out::object($row), $rows)));
    }
}
