<?php

namespace App\Http\Controllers\Publico\Integraciones\Appview;

use App\Http\Controllers\Controller;
use App\Services\Integraciones\ToolApi\ToolApiOutput as Out;
use Illuminate\Http\Response;

/**
 * appview/anketo2.php (問診表): exige la sesión del login del sitio móvil hp/user (session_login.php), que solo
 * crea hp/ (fuera de alcance; hp/user/control.php lleva a hp/user/anketo2.php, no a esta). Sin esa sesión el legacy
 * redirige a «login.php?logerr=2» (relativo: /appview/login.php, que no existe): es lo único alcanzable y es lo que
 * se reproduce. anketo2_register.php es un include de anketo2.php (directo da un fatal): descartado.
 */
class SurveyController extends Controller
{
    public function show(): Response
    {
        return Out::text('', 302, ['Location' => 'login.php?logerr=2']);
    }
}
