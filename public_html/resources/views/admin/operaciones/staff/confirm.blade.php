{{-- Legacy: system/category5/staff/staff_entry_confirm.php sin errores: ficha + 書き直す / この内容で登録する.
     Los dos formularios reenvían los datos en crudo (hidden escapados).
     Variables: $data, $act, $staff, $isAdminAccount, $imageUrl, $shopNames, $groups, $hasHomepage --}}
@extends('layouts.legacy')

@php($pageTitle = $act === 'new' ? 'スタッフ新規登録' : 'スタッフ登録情報 修正')

@section('menu', 'operaciones')

@section('title', $pageTitle.' - '.($siteName ?? ''))

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
<div class="contents">

<p class="h2Title">■{{ $pageTitle }} 確認</p>

<div class="form">
@include('admin.operaciones.staff.partials.summary', ['groupNames' => $groups])

<div class="btnBox">
@foreach (['rewrite' => ['modoru', '書き直す'], 'store' => ['enter', 'この内容で登録する']] as $action => [$button, $label])
<form method="post" action="{{ route('admin.operaciones.staff.'.$action) }}">
@csrf
@foreach ($data as $name => $value)
<input type="hidden" name="{{ $name }}" value="{{ $value }}">
@endforeach
<input type="hidden" name="cd" value="{{ $staff?->cd }}">
<input type="hidden" name="act" value="{{ $act }}">
<input type="submit" name="{{ $button }}" value="{{ $label }}" class="existBtn btnStyleM" />
</form>
@endforeach
</div>

<!-- / .form --></div>

<!-- / .contents --></div>
@endsection
