<?php

namespace App\Services\Integraciones\Appview;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Lo que las pantallas de cupones/sellos de appview tomaban de hp/inc_m (sitio móvil, fuera de alcance):
 * - kihon.php: colores de tb_hp_color ('shop': appview no está bajo /user/) y plantilla tb_mobile_template
 *   (admin_master.mobile_hp_template + patrón -1), $h2Color/$h2BGColor, $hrColor; filtro de parámetros *_cd,
 *   kensu, page y subject.
 * - cUserAgent: solo el TYPE (iOS/Android cambian las bandas).
 * - hr_border.php / hr_dot.php: bandas entre bloques.
 * - emoji/trans.php: emoji() devuelve '' para smartphones y PC (las únicas que abren el WebView de la app); los
 *   emoji de ガラケー (i-mode, EZweb, SoftBank) no se portan.
 */
class MobileTheme
{
    /** @var array<string, array{cd: mixed, color: mixed, background: mixed, pic_flg: mixed}> */
    public array $templateData = [];

    public ?string $fontColor = null;

    public ?string $bgColor = null;

    public ?string $hrColor = null;

    public ?string $h2Color = null;

    public ?string $h2BGColor = null;

    public string $uaType;

    public function __construct(Request $request)
    {
        $this->uaType = self::uaType((string) $request->userAgent());

        $color = DB::table('tb_hp_color')->where('contents', 'shop')->first();
        if ($color) {
            $this->bgColor = $color->bg_color;
            $this->fontColor = $color->font_color;
        }

        $pattern = DB::table('admin_master')->where('cd', 1)->value('mobile_hp_template');
        if ((string) $pattern !== '') {
            $rows = DB::table('tb_mobile_template')->whereIn('pattern', [(int) $pattern, -1])->get();
            foreach ($rows as $row) {
                $this->templateData[$row->use_case] = ['cd' => $row->cd, 'color' => $row->color, 'background' => $row->background, 'pic_flg' => $row->pic_flg];
            }
        }
        if (($this->templateData['body']['color'] ?? null) != '') {
            $this->fontColor = $this->templateData['body']['color'];
        }
        if (($this->templateData['body']['background'] ?? null) != '') {
            $this->bgColor = $this->templateData['body']['background'];
        }

        $this->hrColor = ($this->templateData['hr']['color'] ?? null) != '' ? $this->templateData['hr']['color'] : $this->fontColor;
        $this->h2Color = ($this->templateData['h2']['color'] ?? null) != '' ? $this->templateData['h2']['color'] : $this->fontColor;
        $this->h2BGColor = ($this->templateData['h2']['background'] ?? null) != '' ? $this->templateData['h2']['background'] : $this->bgColor;
    }

    /** cUserAgent::init()['TYPE'] */
    public static function uaType(string $ua): string
    {
        $parts = explode('/', $ua);

        return match (true) {
            str_contains($ua, 'DoCoMo') || str_contains($ua, 'FOMA') => 'DoCoMo',
            $parts[0] === 'UP.Browser' || str_contains($parts[0], 'KDDI') => 'AU',
            str_contains($ua, 'J-PHONE') || str_contains($ua, 'Vodafone') || str_contains($ua, 'SoftBank') => 'SoftBank',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') || str_contains($ua, 'iPod') => 'iOS',
            str_contains($ua, 'Windows Phone') => 'WindowsPhone',
            str_contains($ua, 'Win') => 'win',
            str_contains($ua, 'Mac') => 'mac',
            default => 'unknown',
        };
    }

    public function isSmartphone(): bool
    {
        return $this->uaType === 'iOS' || $this->uaType === 'Android';
    }

    /** emoji(): '' fuera de ガラケー */
    public function emoji(int $number): string
    {
        return '';
    }

    /** Imagen de banda de la plantilla (use_case) con pic_flg = '1', o null */
    private function bandImage(string $useCase): ?string
    {
        $config = $this->templateData[$useCase] ?? null;
        if (($config['pic_flg'] ?? null) != '1') {
            return null;
        }

        // alt usaba $hrDotConfig['use_case'], clave que el arreglo no tiene: sale solo «画像»
        return '<div align="center"><img src="/hp/uploads/c6/template/'.$config['cd'].'/0_m.jpg?t='.time().'" alt="画像" /></div>';
    }

    /** hr_border.php (banda 帯) */
    public function hrBorder(): string
    {
        if ($image = $this->bandImage('帯')) {
            return $image."\r\n";
        }
        if ($this->isSmartphone()) {
            return "<div class=\"hr_border\"></div>\r\n";
        }
        $c = $this->hrColor;

        return "<hr size=\"2\" color=\"#{$c}\" bgcolor=\"#{$c}\" style=\"width: 98%; height: 2px; margin: 4px 0; padding: 0; color: #{$c}; background: #{$c}; border: 2px solid #{$c};\" />\r\n";
    }

    /** hr_dot.php (banda 帯ドット; en smartphones nada) */
    public function hrDot(): string
    {
        if ($image = $this->bandImage('帯ドット')) {
            return $image."\r\n";
        }
        if ($this->isSmartphone()) {
            return '';
        }

        return '<div align="center" class="hr"><font color="#'.$this->hrColor.'">････････････････</font></div>'."\r\n";
    }

    /**
     * Filtro de hp/inc_m/kihon.php + get_form()/get_form2() de cada pantalla sobre $_REQUEST: kensu, page, subject
     * y *_cd que no sean un entero sin ceros a la izquierda pasan a intval(); luego se limpia cada valor escalar.
     *
     * @param  callable(string): string  $clean
     */
    public static function input(Request $request, callable $clean): array
    {
        $values = $request->all();
        foreach ($values as $key => $value) {
            if (preg_match('/^(kensu|page|subject)$/', (string) $key) || preg_match('/_cd$/', (string) $key)) {
                if (! is_string($value) || ! preg_match('/^\-?[1-9][0-9]*$/', $value)) {
                    $values[$key] = is_array($value) ? (empty($value) ? 0 : 1) : intval($value);
                }
            }
        }
        foreach ($values as $key => $value) {
            if (! is_array($value)) {
                $values[$key] = $clean((string) $value);
            }
        }

        return $values;
    }
}
