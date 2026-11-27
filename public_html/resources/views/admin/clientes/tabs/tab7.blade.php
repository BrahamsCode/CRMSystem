{{-- Legacy: system/category1/inc/detail_tab7.php (pestaña マイアルバム).
     Variables:
       $customer       modelo tb_customer
       $albumSelected  objeto|null: tb_photo_album (disp_flg = 1) de ?photoalbum_cd=, o la última del cliente
                       (ORDER BY disp_date DESC, cd DESC): cd, disp_date (epoch), pic_flg ('1<>0…'), comment
       $albumList      LengthAwarePaginator (kensu fijo 5) de tb_photo_album del cliente (disp_flg = 1,
                       ORDER BY disp_date DESC, cd DESC): cd, disp_date, pic_flg, comment
     Las imágenes siguen en la URL legacy /hp/uploads/c1/photoalbum/{cd}/{n}_l.jpg y 0_ss.jpg. --}}
@php
    // 画像枚数
    $picCount = 1;
    $dateDisp = fn ($v) => is_numeric($v) && $v > 0
        ? \Illuminate\Support\Carbon::createFromTimestamp($v, 'Asia/Tokyo')->format('Y/m/d')
        : '';
    // Parámetros que el legacy añadía en $addstr
    $addParams = array_filter(['tab' => request('tab'), 'kaiin_cd' => $customer->cd, 'from' => request('from')], fn ($v) => $v !== null && $v !== '');
    $cacheBuster = time();
@endphp
{{-- ************************************************************ フォトアルバム 開始 ************************************************************ --}}
{{-- 2011/03/10 マルティン 顧客フォトアルバムを追加 --}}

<!--フォトアルバム情報table start-->
<div class="tabBody">


{{-- 2011.09.14 追加ボタンを上部に移動 --}}
@if ($albumSelected)
<div class="btnBox">
<input type="button" class="btnStyleM" value="アルバムを追加する" onclick="document.add_record_form.submit();" />
<input type="button" class="btnStyleM" value="アルバムを修正する" onclick="document.update_record_form.submit();" />
<input type="button" class="btnStyleM" value="アルバムを削除する" onclick="document.delete_record_form.submit();" />
</div>
@endif

<div class="innerBody">
<div class="innerTabLeft" style="width: 60%;">

<p class="h2Title">■プレビュー</p>
@if ($albumSelected)
@php $picFlgArray = explode('<>', (string) $albumSelected->pic_flg); @endphp
<table class="tableBox">
	<tr>
		<th>年月日</th>
		<td>{{ $dateDisp($albumSelected->disp_date) }}</td>
	</tr>

@for ($x = 0; $x < $picCount; $x++)
	<tr>
		<th>画像</th>
		<td>@if (($picFlgArray[$x] ?? '') == '1')<img src="{{ url('hp/uploads/c1/photoalbum/' . $albumSelected->cd . '/' . $x . '_l.jpg') }}?t={{ $cacheBuster }}" alt="画像" class="mB10" />@endif</td>
	</tr>
@endfor

	<tr>
		<th>コメント</th>
		<td>{!! nl2br(e($albumSelected->comment)) !!}</td>
	</tr>
</table>
@else
<div class="center">アルバムデータがありません。</div>
@endif

<!-- / div.innerTabLeft END --></div>




<!--フォトアルバム情報table end-->
<div class="innerTabRight" style="width: 39%;">
<p class="h2Title">■マイアルバム一覧</p>
<div class="form">
@if ($albumList->total() > 0)

<p><span class="attention">{{ $albumList->firstItem() }}件～{{ $albumList->lastItem() }}件目/{{ $albumList->total() }}件中</span></p>

<table class="tableBox2" style="table-layout:fixed;">
<tr>
<th style="width: 80px;">サムネイル</th>
<th>内容</th>
<th style="width: 55px;">&nbsp;</th>
</tr>
@foreach ($albumList as $photoalbum)
@php $picFlgArray = explode('<>', (string) $photoalbum->pic_flg); @endphp
<tr @if ($albumSelected && $photoalbum->cd == $albumSelected->cd) style="background-color: #FFEEAA"@endif>
<td>@if (($picFlgArray[0] ?? '') == '1')<img src="{{ url('hp/uploads/c1/photoalbum/' . $photoalbum->cd . '/0_ss.jpg') }}?t={{ $cacheBuster }}" alt="画像" class="mB10" />@endif</td>
<td>
	<div style="font-weight: bold;">{{ $dateDisp($photoalbum->disp_date) }}</div>
	<div style="max-height:85px;overflow-y:auto;">{!! nl2br(e($photoalbum->comment)) !!}</div>
</td>
<td style="text-align:center;">
@if ($albumSelected && $photoalbum->cd == $albumSelected->cd)
<span style="padding: 2px; border: 1px solid #FF9955; color: #EE6622;">選択中</span>
@else
<a class="btnStyle" href="{{ route('admin.clientes.show', array_merge([$customer->cd, 'page' => request('page'), 'kensu' => $albumList->perPage(), 'photoalbum_cd' => $photoalbum->cd], $addParams)) }}">詳細</a>
@endif
</td>
</tr>
@endforeach


</table>


<ul class="btnBox2">
@include('admin.clientes.partials.page-control', ['paginator' => $albumList, 'params' => $addParams])
</ul>


@else
<div class="center">アルバムデータがありません。</div>
@endif
<!-- / div.form END --></div>
<!-- / div.innerTabRight END --></div>
<!-- / div.innerBody END --></div>

<div class="btnBox" style="clear: both;">

<form method="get" name="add_record_form" id="add_record_form" class="form" action="{{ route('admin.clientes.photos.create', $customer->cd) }}">
<input type="submit" name="add_record" id="add_record" class="btn btnStyleM" value="アルバムを追加する" />
<input type="hidden" name="kaiin_cd" id="kaiin_cd" class="btn" value="{{ $customer->cd }}">
<input type="hidden" name="tab" id="tab" value="{{ request('tab') }}">
<input type="hidden" name="page" id="page" value="{{ request('page') }}" />
</form>
@if ($albumSelected)
<form method="get" name="update_record_form" id="add_record_form" class="form" action="{{ route('admin.clientes.photos.edit', [$customer->cd, $albumSelected->cd]) }}">
<input type="submit" name="add_record" id="add_record" class="btn btnStyleM" value="アルバムを修正する" />
<input type="hidden" name="kaiin_cd" id="kaiin_cd" class="btn" value="{{ $customer->cd }}">
<input type="hidden" name="tab" id="tab" value="{{ request('tab') }}">
<input type="hidden" name="photoalbum_cd" id="photoalbum_cd" class="btn" value="{{ $albumSelected->cd }}">
<input type="hidden" name="page" id="page" value="{{ request('page') }}" />
</form>
<form method="post" name="delete_record_form" id="delete_record_form" class="form" action="{{ route('admin.clientes.photos.destroy', [$customer->cd, $albumSelected->cd]) }}">
@csrf
<input type="button" name="add_record" id="add_record" class="btn btnStyleM" value="アルバムを削除する" onclick="submitChk(document.delete_record_form);" />
<input type="hidden" name="kaiin_cd" id="kaiin_cd" class="btn" value="{{ $customer->cd }}">
<input type="hidden" name="tab" id="tab" value="{{ request('tab') }}">
<input type="hidden" name="photoalbum_cd" id="photoalbum_cd" class="btn" value="{{ $albumSelected->cd }}">
<input type="hidden" name="page" id="page" value="{{ request('page') }}" />
</form>
@endif

<!-- / div.btnBox END --></div>
<!-- / div.tabBody END --></div>

{{-- ************************************************************ フォトアルバム 終了 ************************************************************ --}}
