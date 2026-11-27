<?php

namespace App\Http\Controllers\Publico\Senalizacion;

use App\Http\Controllers\Controller;
use App\Services\Senalizacion\PlayerPolls;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Sondeos del reproductor: devuelven un <script> o texto, no JSON (mismas respuestas que el legacy) */
class PollController extends Controller
{
    public function __construct(private PlayerPolls $polls) {}

    /** ajax_network_check.php */
    public function network(): Response
    {
        return $this->html(PlayerPolls::NETWORK_OK);
    }

    /** ajax_xml_check.php */
    public function xmlCheck(Request $request): Response
    {
        return $this->html($this->polls->xmlCheck($request));
    }

    /** ajax_xml_check_2.php */
    public function xmlCheck2(Request $request): Response
    {
        return $this->html($this->polls->xmlCheck2($request));
    }

    /** ajax_display_config_check.php y ajax_display_config_check_2.php */
    public function displayConfigCheck(Request $request): Response
    {
        return $this->html($this->polls->displayConfigCheck($request));
    }

    private function html(string $content): Response
    {
        return response($content, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
