{{-- Legacy: system/category5/staff/staff_entry_complete.php (スタッフ登録情報 tras registrar).
     Variables: $data (tb_staff des-escapado), $staff, $isAdminAccount, $imageUrl, $shopNames, $groupNames, $hasHomepage --}}
@extends('layouts.legacy')

@section('menu', 'operaciones')

@section('title', 'スタッフ登録情報 - '.($siteName ?? ''))

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".ac1").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
<div class="contents">

<p class="h2Title">■スタッフ登録情報</p>

@php($buttons = '<a href="'.e(route('admin.operaciones.staff.create')).'" class="btnStyleM">続けて新規登録する</a>'."\n"
    .'<a href="'.e(route('admin.operaciones.staff.index', ['act' => 'return'])).'" class="btnStyleM">スタッフ一覧から検索する</a>')
<div class="btnBox">
{!! $buttons !!}
</div>

<div class="form">
@include('admin.operaciones.staff.partials.summary')

<div class="btnBox">
{!! $buttons !!}
</div>

<!-- / .form --></div>

<!-- / .contents --></div>
@endsection
