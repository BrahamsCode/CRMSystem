{{-- Vuelta del popup de ac.crm-s.jp (slim_href.php / slim_del_href.php): recarga la lista o muestra el error --}}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ja" xml:lang="ja" dir="ltr">

<head>
@include('layouts.legacy.head')

@if ($error !== '' && ! $reload)
<link href="/system/css/import.css" rel="stylesheet" type="text/css" media="all" />
@endif

<script type="text/javascript" src="/system/js/scrollsmoothly.js"></script>
<script type="text/javascript" src="/system/js/iepngfix.js"></script>
<script type="text/javascript" src="/system/category0/config/js/setPopWindow.js"></script>

<script type="text/javascript">
$(document).ready(function() {
@if ($reload)
	window.parent.location.reload();
@endif
});
</script>

<title>CRM端末管理 - {{ $siteName }}</title>
</head>

<body id="pTop" class="pageCategory0">
<br>
<p style="background:#fff;padding:20px">{{ $error }}</p>
</body>
</html>
