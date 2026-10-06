<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subjectLine }}</title>
</head>
<body style="margin:0;padding:24px;background:#f5f6f8;font-family:Helvetica,Arial,sans-serif;color:#14171f;line-height:1.6">
    <div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:12px;padding:28px">
        {{-- Contenido HTML escrito por el administrador en el editor de decomail --}}
        {!! $htmlBody !!}
    </div>
</body>
</html>
