<?php

namespace App\Http\Controllers\Publico\Senalizacion;

use App\Http\Controllers\Controller;
use App\Services\Senalizacion\PlayerResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Reproductor digitalsignage/ (index.php → index2.php), ルーレット (roulette.php) y スロット (slot.php).
 * Lo abren los monitores de tienda en un navegador a pantalla completa: sin login ni CSRF.
 */
class PlayerController extends Controller
{
    public function __construct(private PlayerResolver $resolver) {}

    /** /digitalsignage/, /digitalsignage/index.php y /digitalsignage/index2.php (?shop_cd=&display_cd=) */
    public function show(Request $request): Response
    {
        $data = $this->resolver->resolve($request);

        return match ($data) {
            // Sin display_cd: el aviso 「ただ今システムがビジーです」 y nada más, como el legacy
            PlayerResolver::BUSY => $this->html(rtrim(view('publico.senalizacion.busy')->render())),
            // Display inexistente o sin programa: el legacy imprime «error2» / «error1» y termina
            PlayerResolver::ERROR_DISPLAY, PlayerResolver::ERROR_PROGRAM => $this->html($data),
            default => $this->html(view('publico.senalizacion.player', $data)->render()),
        };
    }

    public function roulette(Request $request): Response
    {
        return $this->html(view('publico.senalizacion.roulette', $this->requestCds($request))->render());
    }

    public function slot(Request $request): Response
    {
        return $this->html(view('publico.senalizacion.slot', $this->requestCds($request) + ['kanshiTime' => time()])->render());
    }

    /** shop_cd y display_cd tal como llegan (el legacy los reenvía con h() en las URL de vuelta) */
    private function requestCds(Request $request): array
    {
        return [
            'requestShopCd' => (string) $request->input('shop_cd', ''),
            'requestDisplayCd' => (string) $request->input('display_cd', ''),
        ];
    }

    private function html(string $content): Response
    {
        return response($content, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
