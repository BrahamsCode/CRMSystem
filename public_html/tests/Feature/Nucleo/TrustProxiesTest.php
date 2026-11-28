<?php

use App\Providers\NucleoServiceProvider;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

/*
 * Proxies de confianza (TRUSTED_PROXIES → crm.trusted_proxies, DESPLIEGUE.md §11): detrás del balanceador
 * del hosting, request()->ip() debe ser la del visitante y la petición debe saberse HTTPS.
 */

$limpiar = function () {
    TrustProxies::flushState();
    SymfonyRequest::setTrustedProxies([], 0);
};
beforeEach($limpiar);
afterEach($limpiar);

/** IP y HTTPS que ve Laravel para una petición que llega por un proxy 10.0.0.5 */
function verTrasProxy(): array
{
    $request = Request::create('http://crm.test/system/login.php', 'GET', server: [
        'REMOTE_ADDR' => '10.0.0.5',
        'HTTP_X_FORWARDED_FOR' => '203.0.113.7',
        'HTTP_X_FORWARDED_PROTO' => 'https',
        'HTTP_X_FORWARDED_PORT' => '443',
    ]);

    $visto = [];
    app(TrustProxies::class)->handle($request, function (Request $r) use (&$visto) {
        $visto = [$r->ip(), $r->isSecure()];

        return response('ok');
    });

    return $visto;
}

it('sin TRUSTED_PROXIES usa REMOTE_ADDR, como el legacy', function () {
    NucleoServiceProvider::trustProxies('');

    expect(verTrasProxy())->toBe(['10.0.0.5', false]);
});

it('con * toma la IP y el HTTPS de las cabeceras del proxy', function () {
    NucleoServiceProvider::trustProxies('*');

    expect(verTrasProxy())->toBe(['203.0.113.7', true]);
});

it('con una lista solo confía en esos proxies', function () {
    NucleoServiceProvider::trustProxies(' 10.0.0.0/8 , 192.168.1.1 ');
    expect(verTrasProxy())->toBe(['203.0.113.7', true]);

    $limpiar = fn () => [TrustProxies::flushState(), SymfonyRequest::setTrustedProxies([], 0)];
    $limpiar();
    NucleoServiceProvider::trustProxies('192.168.1.1');
    expect(verTrasProxy())->toBe(['10.0.0.5', false]);
});

it('el valor por defecto de la configuración es vacío', function () {
    expect(config('crm.trusted_proxies'))->toBe('');
});
