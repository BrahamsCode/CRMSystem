{{-- <head> común del legacy (inc/head.php → Base::createHead): metas, favicon y CSS/JS según la versión de UI --}}
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<meta http-equiv="content-style-type" content="text/css" />
<meta http-equiv="content-script-type" content="text/javascript" />

<meta name="robots" content="noindex" />
<meta name="robots" content="nofollow" />
<meta name="robot-control" content="deny-all" />
<meta name="robots" content="none" />
<meta name="csrf-token" content="{{ csrf_token() }}" />

<link href="/system/img/favicon.ico" rel="shortcut icon" />
<link href="/system/img/favicon.png" rel="icon" type="image/png" />

@php
    // Base::createHead(['jquery' => …, 'jquery-ui' => …]): algunas pantallas usan un jQuery más nuevo
    $jquery = trim($__env->yieldContent('jquery')) ?: 'jquery-1.3.2.min.js';
    $jqueryUi = trim($__env->yieldContent('jquery_ui')) ?: 'jquery-ui-1.7.2.custom.min.js';
@endphp
@if (app(\App\Services\Nucleo\LegacyUi::class)->uiVersion() === '2')
<link href="/system/css/mainStyle.css" rel="stylesheet" type="text/css" media="all" />
<link href="/system/css/vRippleEffect.css" rel="stylesheet" type="text/css" media="all" />
<link href="/system/css/jquery.alerts.css" rel="stylesheet" type="text/css" media="all" />

<script type="text/javascript" src="/system/js/default.js"></script>
<script type="text/javascript" src="/system/js/{{ $jquery }}"></script>
<script type="text/javascript" src="/system/js/{{ $jqueryUi }}"></script>
<script type="text/javascript" src="/system/js/jquery-ui-dialog-compat.js"></script>
<script type="text/javascript" src="/system/js/crm2.js"></script>
<script type="text/javascript" src="/system/js/vRippleEffect-min.js"></script>
<script type="text/javascript" src="/system/js/jquery.alerts.js?v2"></script>
@else
<link href="/system/css/import.css" rel="stylesheet" type="text/css" media="all" />

<script type="text/javascript" src="/system/js/scrollsmoothly.js"></script>
<script type="text/javascript" src="/system/js/iepngfix.js"></script>
<script type="text/javascript" src="/system/js/default.js"></script>

<script type="text/javascript" src="/system/js/{{ $jquery }}"></script>
<script type="text/javascript" src="/system/js/{{ $jqueryUi }}"></script>
<script type="text/javascript" src="/system/js/jquery-ui-dialog-compat.js"></script>
@endif

{{-- CSRF (NUCLEO.md): cabecera X-CSRF-TOKEN en todo jQuery (vale desde 1.3.2) y campo _token en todo <form method="post">.
     Los navegadores actuales ya pasan por Sec-Fetch-Site (Laravel 13); esto cubre los WebView viejos de las tablets. --}}
<script type="text/javascript">
(function () {
    var token = '{{ csrf_token() }}';
    if (window.jQuery) {
        jQuery.ajaxSetup({ beforeSend: function (xhr) { xhr.setRequestHeader('X-CSRF-TOKEN', token); } });
    }
    function addToken(form) {
        if (!form || String(form.method).toLowerCase() !== 'post' || form._token) { return; }
        var input = document.createElement('input');
        input.type = 'hidden'; input.name = '_token'; input.value = token;
        form.appendChild(input);
    }
    function addAll() { for (var i = 0; i < document.forms.length; i++) { addToken(document.forms[i]); } }
    if (document.addEventListener) {
        document.addEventListener('DOMContentLoaded', addAll, false);
        document.addEventListener('submit', function (e) { addToken(e.target); }, true);
    } else {
        window.attachEvent('onload', addAll);
    }
    // form.submit() desde JS no dispara "submit": se completa también aquí
    var nativeSubmit = HTMLFormElement.prototype.submit;
    HTMLFormElement.prototype.submit = function () { addToken(this); return nativeSubmit.apply(this, arguments); };
})();
</script>
