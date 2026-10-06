<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

/**
 * Busca una dirección japonesa a partir del código postal.
 *
 * Va por el servidor y no directo desde el navegador por tres motivos: la API
 * no envía cabeceras CORS, la respuesta se puede cachear para todos los usuarios,
 * y el kana llega en ancho medio (ｵｵｻｶｼ) mientras el sistema lo guarda en ancho
 * completo (オオサカシ).
 */
class PostalCodeController extends Controller
{
    private const API = 'https://zipcloud.ibsnet.co.jp/api/search';

    public function __invoke(string $zip): JsonResponse
    {
        $zip = preg_replace('/[^0-9]/', '', $zip);

        if (strlen($zip) !== 7) {
            return response()->json(['error' => 'El código postal debe tener 7 dígitos.'], 422);
        }

        $data = cache()->remember("zip:{$zip}", now()->addDays(30), function () use ($zip) {
            $respuesta = Http::timeout(5)->get(self::API, ['zipcode' => $zip]);

            return $respuesta->successful() ? ($respuesta->json('results')[0] ?? null) : null;
        });

        if (! $data) {
            return response()->json(['error' => 'No se encontró ninguna dirección con ese código postal.'], 404);
        }

        // Como el legacy, la lectura cubre 市区町村 y 町域 separados por un espacio
        // (オオサカシキタク ウメダ). El 町域 (梅田) va al inicio de la calle, para
        // que solo falte escribir el número (梅田1-2-3).
        return response()->json([
            'pref' => $data['address1'],
            'city' => $data['address2'],
            'street_address' => $data['address3'],
            'city_kana' => trim($this->anchoCompleto($data['kana2']) . ' ' . $this->anchoCompleto($data['kana3'])),
        ]);
    }

    /** Katakana de ancho medio a ancho completo: ｵｵｻｶｼ → オオサカシ */
    private function anchoCompleto(string $texto): string
    {
        return mb_convert_kana($texto, 'KVA', 'UTF-8');
    }
}
