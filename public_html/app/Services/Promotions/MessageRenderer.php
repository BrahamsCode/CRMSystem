<?php

namespace App\Services\Promotions;

use App\Models\CouponCustomer;
use App\Models\Customer;
use App\Models\CustomerMessage;
use App\Models\Shop;
use Illuminate\Support\Facades\URL;

/**
 * Personaliza un mensaje para cada destinatario.
 *
 * Variables: {apellido}, {nombre}, {nombre_completo}, {num_socio}, {tienda},
 * {puntos}, {sellos}, {cupon}, {enlace_cupon}, {encuesta} y los campos de
 * información adicional marcados «Usar en newsletter» como {info:categoria.campo}.
 * {名前} del legacy equivale a {nombre_completo}.
 *
 * Los enlaces http(s) del cuerpo se cambian por un enlace de seguimiento firmado,
 * así se cuenta el clic sin abrir una redirección a cualquier sitio.
 */
class MessageRenderer
{
    /** Variables disponibles, para la ayuda del editor */
    public static function variables(): array
    {
        return [
            '{nombre_completo}' => 'Apellido y nombre',
            '{apellido}' => 'Apellido',
            '{nombre}' => 'Nombre',
            '{num_socio}' => 'Nº de socio',
            '{tienda}' => 'Tienda de registro',
            '{puntos}' => 'Saldo de puntos',
            '{sellos}' => 'Sellos acumulados',
            '{cupon}' => 'Título del cupón adjunto',
            '{enlace_cupon}' => 'Enlace al cupón adjunto',
            '{encuesta}' => 'Enlace a la encuesta enlazada',
        ];
    }

    /**
     * @return array{subject: string, text: string, html: ?string}
     */
    public function render(
        string $subject,
        string $body,
        bool $html,
        Customer $customer,
        ?CustomerMessage $recipient = null,
        ?CouponCustomer $coupon = null,
        ?string $surveyUid = null,
        ?Shop $footerShop = null,
    ): array {
        $vars = $this->values($customer, $recipient, $coupon, $surveyUid);

        $subject = $this->replace($subject, $vars);
        $body = $this->replace($body, $vars);

        if ($recipient) {
            $body = $this->trackLinks($body, $recipient);
        }

        $footer = $footerShop ? $this->footer($footerShop) : '';
        $text = trim($html ? $this->htmlToText($body) : $body) . ($footer ? "\n\n—\n" . $footer : '');

        $htmlBody = null;
        if ($html) {
            $htmlBody = $body
                . ($footer ? '<hr style="margin:24px 0;border:0;border-top:1px solid #e4e7ec"><p style="color:#6b7280;font-size:12px">' . nl2br(e($footer)) . '</p>' : '')
                . ($recipient ? '<img src="' . e(route('promotions.track.open', $recipient->uid)) . '" width="1" height="1" alt="">' : '');
        }

        return ['subject' => $subject, 'text' => $text, 'html' => $htmlBody];
    }

    private function values(Customer $customer, ?CustomerMessage $recipient, ?CouponCustomer $coupon, ?string $surveyUid): array
    {
        $vars = [
            '{nombre_completo}' => $customer->greetingName(),
            '{名前}' => $customer->greetingName(),
            '{apellido}' => (string) $customer->last_name,
            '{nombre}' => (string) $customer->first_name,
            '{num_socio}' => (string) $customer->code,
            '{tienda}' => (string) $customer->shop?->name,
            '{puntos}' => number_format((int) $customer->point_balance),
            '{sellos}' => (string) (int) $customer->stamp_balance,
            '{cupon}' => (string) $coupon?->coupon?->name,
            '{enlace_cupon}' => $coupon ? route('promotions.public.coupon', $coupon->uid) : '',
            '{encuesta}' => $surveyUid
                ? route('promotions.public.survey', ['survey' => $surveyUid] + ($recipient ? ['r' => $recipient->uid] : []))
                : '',
        ];

        foreach ((array) $customer->custom_data as $category => $fields) {
            foreach ((array) $fields as $field => $value) {
                $vars['{info:' . $category . '.' . $field . '}'] = is_array($value) ? implode(', ', $value) : (string) $value;
            }
        }

        return $vars;
    }

    private function replace(string $text, array $vars): string
    {
        $text = strtr($text, $vars);

        // Campos personalizados que el cliente no tiene: se quitan, no se dejan como {info:…}
        return preg_replace('/\{info:[^}]+\}/u', '', $text);
    }

    /** Cambia cada enlace por /p/c/{uid}?u=… firmado */
    private function trackLinks(string $body, CustomerMessage $recipient): string
    {
        return preg_replace_callback('~https?://[^\s"<>\']+~u', function ($m) use ($recipient) {
            $url = rtrim($m[0], '.,);');
            $tail = substr($m[0], strlen($url));

            // Los enlaces propios de seguimiento y del píxel no se vuelven a envolver
            if (str_contains($url, '/p/o/') || str_contains($url, '/p/c/')) {
                return $m[0];
            }

            return URL::signedRoute('promotions.track.click', ['recipient' => $recipient->uid, 'u' => $url]) . $tail;
        }, $body);
    }

    private function footer(Shop $shop): string
    {
        return collect([
            $shop->name,
            trim(implode(' ', array_filter([$shop->zip, $shop->pref, $shop->city, $shop->street_address]))),
            $shop->tel ? 'Tel. ' . $shop->tel : null,
        ])->filter()->join("\n");
    }

    private function htmlToText(string $html): string
    {
        $html = preg_replace('~<a [^>]*href="([^"]+)"[^>]*>(.*?)</a>~is', '$2 ($1)', $html);
        $html = preg_replace('~<(br|/p|/div|/h[1-6]|/li)[^>]*>~i', "\n", $html);

        return html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
