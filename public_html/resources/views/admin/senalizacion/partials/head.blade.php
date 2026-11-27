{{-- inc/head.php: CSS según el dominio (crm-tis.jp → signage.css, crm-s.net → signage_extlink.css) --}}
<meta charset="utf-8">
<meta name="robots" content="noindex">
<meta name="robots" content="nofollow">
<meta name="robot-control" content="deny-all">
<meta name="robots" content="none">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>管理画面</title>
<link rel="stylesheet" href="/signage/css/base.css">
<!-- <link rel="stylesheet" href="/signage/css/signage.css"> -->

@if (str_contains(request()->getHttpHost(), 'crm-tis.jp'))
	<link rel="stylesheet" href="/signage/css/signage.css">
@elseif (str_contains(request()->getHttpHost(), 'crm-s.net'))
	<link rel="stylesheet" href="/signage/css/signage_extlink.css">
@endif
