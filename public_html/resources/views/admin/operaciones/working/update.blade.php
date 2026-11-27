{{-- Legacy: system/category5/working/work_list_update.php (勤怠情報): guarda y vuelve a la lista del día con un Refresh de 1 s.
     Se quitan ajaxupload-3.5.js y ./js/staff_entry.js, que la página cargaba sin usarlos. Variables: $back (URL de la lista) --}}
@extends('layouts.legacy')

@section('menu', 'operaciones')

@section('title', '勤怠情報 - '.($siteName ?? ''))

@section('css')
<meta http-equiv="Refresh" content="1; URL={{ $back }}">
@endsection

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac2").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
<div class="contents">
<p class="h2Title">■勤怠情報</p>


<p class="center"><img src="/system/img/loading.gif" alt="更新中です。" />
<br />更新中です。</p>

<!-- / .contents --></div>
@endsection
