<?php

namespace App\Http\Controllers\Admin\Senalizacion;

use App\Models\Senalizacion\SignageWebTerminal;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** signage/index.php: ホーム (現在表示中のサイネージ: un iframe por terminal activo) */
class HomeController extends SignageController
{
    public function index(Request $request): Response
    {
        return $this->legacyView->screen($request, 'home', [
            'data' => SignageWebTerminal::query()->toBase()->where('flg', 1)->orderBy('cd')->get()->map(fn ($r) => (array) $r)->all(),
        ]);
    }
}
