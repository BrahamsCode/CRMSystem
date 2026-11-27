<?php

namespace App\Services\Senalizacion;

use App\Models\Terminales\SignageDisplay;
use App\Models\Terminales\SignageProgram;
use App\Services\Terminales\SignageFeedBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Sondeos del reproductor (jQuery .load cada 60 s): devuelven un <script> que recarga la pantalla
 * si algo cambió, o nada. Mismo texto que el legacy, con h() en shop_cd y display_cd.
 *
 * Las comparaciones son las de PHP del legacy (`$columna > $_REQUEST[...]`, numéricas entre cadenas numéricas).
 */
class PlayerPolls
{
    public const NETWORK_OK = '確認できました';

    /**
     * ajax_xml_check.php (versión de index.php): fecha del archivo hp/uploads/c7/display/<display_cd>.xml.
     * Como el legacy usa el cd del DISPLAY (no el del programa) para el nombre del XML.
     */
    public function xmlCheck(Request $request): string
    {
        if (! $request->input('xml_mod')) {
            return '';
        }

        $path = SignageFeedBuilder::DIR.'/'.$request->input('display_cd').'.xml';
        $disk = Storage::disk('public');
        $xmlMod = $disk->exists($path) ? $disk->lastModified($path) : false;

        if ($xmlMod > $request->input('xml_mod')) {
            return '<script type="text/javascript">xmlMod = '.$xmlMod.';'.$this->reload($request).'</script>';
        }

        return '';
    }

    /** ajax_xml_check_2.php: update_date del programa del display */
    public function xmlCheck2(Request $request): string
    {
        if (! $request->input('xml_mod') || ! $request->input('display_cd')) {
            return '';
        }

        $programCd = (string) SignageDisplay::withDeleted()->whereKey((int) $request->input('display_cd'))->value('tb_c7_movie_program_cds');
        if (! ctype_digit($programCd)) {
            return '';
        }
        $updateDate = SignageProgram::withDeleted()->whereKey((int) $programCd)->value('update_date');

        if ($updateDate !== null && $updateDate > $request->input('xml_mod')) {
            return '<script type="text/javascript">xmlMod = '.$updateDate.';'.$this->reload($request).'</script>';
        }

        return '';
    }

    /** ajax_display_config_check.php y _2.php (idénticos): update_date de tb_display */
    public function displayConfigCheck(Request $request): string
    {
        if (! $request->input('display_check_time') || ! $request->input('display_cd')) {
            return '';
        }

        $updateDate = SignageDisplay::withDeleted()->whereKey((int) $request->input('display_cd'))->value('update_date');

        if ($updateDate !== null && $updateDate > $request->input('display_check_time')) {
            return '<script type="text/javascript">'.$this->reload($request).'</script>';
        }

        return '';
    }

    private function reload(Request $request): string
    {
        return 'location.href = "./?shop_cd='.LegacyHtml::h($request->input('shop_cd'))
            .'&display_cd='.LegacyHtml::h($request->input('display_cd')).'";';
    }
}
