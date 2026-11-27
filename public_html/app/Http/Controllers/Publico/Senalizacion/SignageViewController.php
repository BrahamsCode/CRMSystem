<?php

namespace App\Http\Controllers\Publico\Senalizacion;

use App\Http\Controllers\Controller;
use App\Services\Senalizacion\SignageLegacyView;
use App\Services\Senalizacion\SignagePlayer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Reproductor de la señalización web: signage/view/index.php?cd=<terminal> y view/ajax.php (sondeo).
 * Público como en el legacy (inc/files.php dejaba /view/ sin login).
 */
class SignageViewController extends Controller
{
    public function __construct(
        private readonly SignagePlayer $player,
        private readonly SignageLegacyView $legacyView,
    ) {}

    /** Pantalla del diseño del paquete del terminal (view/<display_type>.php) */
    public function show(Request $request): Response
    {
        $data = $this->player->forTerminal((int) $request->input('cd'));
        if (is_string($data)) {
            return $this->html($data);
        }
        $view = $this->legacyView->playerView($data['_packages_data']['display_type'] ?? null);

        return $this->html($view ? $this->legacyView->render($view, $this->legacyView->common($request) + $data) : '現在、準備中です。');
    }

    /** JSON de `update_date` de los ciclos visibles ahora (json_encode del legacy, en text/html como allí) */
    public function poll(Request $request): Response
    {
        return $this->html(json_encode($this->player->pollRows((int) $request->input('packages_cd'))));
    }

    private function html(string $body): Response
    {
        return response($body, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
