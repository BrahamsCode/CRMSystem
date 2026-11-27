{{--
	回答内容詳細 (popup) — legacy: category3/anketo2/anketo_answer_detail.php
	Variables: $family, $items (ítems vivos de la encuesta, por rank), $answer (form cd => text_data)
--}}
@extends('layouts.legacy-popup')

@section('title', '')

@section('content')
<div class="contents">
<p class="h2Title">■回答内容詳細</p>

<div class="form">

<table class="tableBox2">
  <tr>
    <th width="25%">項目名</th>
    <th>回答内容</th>
  </tr>
@foreach ($items as $row => $item)
  <tr @if ($row % 2 == 0) class="tableBg" @endif>
	<td>{{ $item->name }}</td>
	<td>{{ str_replace('<>', ',', $answer[$item->cd] ?? '') }}</td>
  </tr>
@endforeach
</table>

</div>

<!-- / .contents --></div>
@endsection
