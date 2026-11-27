<?php

namespace App\Http\Controllers\Admin\Nucleo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Búsqueda de dirección por código postal (los zip.php del legacy: category0/config, category0/shop,
 * category1/customer, category5/staff, inc). Tabla `zip`. Responde
 * {"zip_name": 都道府県, "town_name_kana": "市区町村カナ 町域カナ", "town_name": "市区町村 町域"}.
 */
class ZipController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $row = DB::table('zip')->where('zip', (string) $request->input('zip', ''))->first();

        $data = $row
            ? [
                'zip_name' => (string) $row->pref1,
                'town_name_kana' => mb_convert_kana((string) $row->city, 'KV') . ' ' . mb_convert_kana((string) $row->town, 'KV'),
                'town_name' => $row->city1 . ' ' . $row->town1,
            ]
            : ['zip_name' => '', 'town_name_kana' => '', 'town_name' => ''];

        return response(json_encode($data, JSON_UNESCAPED_UNICODE))->header('Content-Type', 'application/json; charset=UTF-8');
    }
}
