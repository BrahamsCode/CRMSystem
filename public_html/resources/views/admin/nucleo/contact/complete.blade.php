{{-- 要望・お問い合わせ [送信]: system/contact/contact_complete.php --}}
@extends('layouts.legacy-popup')

@section('title', '要望・お問い合わせ - ' . $siteName)

@section('content')
<div class="contents">
<p class="h2Title">■要望・お問い合わせ</p>

<p style="padding: 25px 10px; border: 1px solid #CCCCCC; text-align: center;">
{!! $failed ? $message : e($message) !!}
</p>

@if ($failed)
<div style="padding-bottom: 10px; text-align: center;">
<form method="post" action="/system/contact/index.php">
@csrf
@include('admin.nucleo.contact._hidden')
<input type="submit" name="modoru" value="戻る" class="existBtn btnStyleM" />
</form>
</div>
@endif

@include('admin.nucleo.contact._close')

<!-- / .contents --></div>
@endsection
