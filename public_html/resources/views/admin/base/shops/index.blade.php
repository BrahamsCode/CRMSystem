{{-- 店舗一覧: system/category0/shop/shop_list.php --}}
@extends('layouts.legacy')

@section('title', '店舗一覧 -総合業務管理システム-')

@section('menu', 'base')

@section('jquery', 'jquery-1.11.2.min.js')
@section('jquery_ui', 'jquery-ui-1.11.4.min.js')

@section('js')
<script type="text/javascript">
$(document).ready(function() {
	$(".accordionBlock").each(function(i, elm){
		$(elm).accordion({ active: 1 })
	});
	$(".ac2").accordion({ active: 0 });
});
</script>

<script type="text/javascript">
//表示件数の制御
function kensuSet(parts){
	document.form_search.kensu.value = parts.value;
	document.form_search.submit();
}
</script>
@endsection

@section('content')
	<div class="contents">
		<p class="h2Title">■店舗検索</p>

		<form method="get" name="form_search" id="form_search" class="form" action="">
			<table class="tableBox">
			@if ($isDevelop)
			<tr>
				<th><label for="name">検索対象</label></th>
				<td colspan="3">
					<ul class="textBox">
						<li>
							<input type="radio" name="disp_flg" id="disp_flg1" class="checkBtn" value="1" checked /><label for="disp_flg1">登録済み</label>
						</li>
						<li>
							<input type="radio" name="disp_flg" id="disp_flg2" class="checkBtn" value="9"@if (request('disp_flg') === '9') checked="checked"@endif /><label for="disp_flg2">削除済み</label>
						</li>
						<li>
							<input type="radio" name="disp_flg" id="disp_flg3" class="checkBtn" value="all"@if (request('disp_flg') === 'all') checked="checked"@endif /><label for="disp_flg3">全て</label>
						</li>
					</ul>
				</td>
			</tr>
			@endif
			<tr>
				<th><label for="name">会社名/屋号</label></th>
				<td><input type="text" name="kaishamei" id="kaishamei" class="writeArea" value="{{ request('kaishamei') }}" /></td>
			</tr>
			<tr>
				<th><label for="tantou">責任者</label></th>
				<td><input type="text" name="tantou" id="tantou" class="inputL" value="{{ request('tantou') }}" /></td>
			</tr>
			<tr>
				<th><label for="mail">メールアドレス</label></th>
				<td><input type="text" name="mail" id="mail" class="inputL" value="{{ request('mail') }}" /></td>
			</tr>
			<tr>
				<th><label for="area_cds">エリア</label></th>
				<td>
					<div style="height: 200px; overflow-y: auto;">
					{!! app(\App\Services\Nucleo\AreaTree::class)->checkUi($treeShops, $selected) !!}
					</div>

				</td>
			</tr>
		</table>


		<div class="btnBox">
			<input type="submit" name="act" id="act" class="btn" value="検索する" />
			<input type="hidden" name="se_flg" id="se_flg" value="1" />
			<input type="hidden" name="kensu" id="kensu" value="{{ $kensu }}" />
			<input type="hidden" name="page" id="page" value="1" />
		</div>
	</form>
<!-- / .contents --></div>


<div class="contents">
	<p class="h2Title">■店舗一覧</p>

	@if ($rows->isNotEmpty())
	<div id="form">
		<p class="maxListLength">
			表示件数
			<select name="kensu_act" id="kensu_act" class="selectBox" onchange="kensuSet(this)">{!! \App\Support\LegacyHtml::kensuOptions($kensu) !!}</select>
		</p>
	</div>

	<p><span class="attention">{{ $maxrows }}件</span>の該当結果がありました。</p>

	<form method="post" name="form1" id="form1" class="form" action="">

		<table class="tableBox2 elipsisTable">
			<tr>
				<th style="width:40px;">&nbsp;</th>
				<th>店舗グループ<br />会社名/屋号<br />責任者</th>
				<th>郵便番号<br />住所</th>
				<th>電話番号<br />メールアドレス</th>
				<th class="btn3" style="width:100px;">&nbsp;</th>
			</tr>

			@foreach ($rows as $i => $row)
			<tr class="{{ $i % 2 != 0 ? 'tableBg' : '' }}">
				<td style="text-align: center;">
				{{ $row->cd }}<br />
				@if ((string) $row->pos_domain !== '')<span style="color: #FF9900; border: 1px solid #FF9900;">POS</span>@else&emsp;@endif
				</td>
				<td>
					@if (isset($row->shop_category_cd, $categories[$row->shop_category_cd]))
						<span>{{ $categories[$row->shop_category_cd] }}</span>
					@else
						<span style="opacity:.8">未設定</span>
					@endif<br>
					{{ $row->kaishamei }}<br /><small><span title="{{ $row->tantou_kana }}">{{ $row->tantou }}</span></small>
				</td>
				<td>{{ $row->zip }}<br /><span title="{{ $row->add_kana }}">{{ $row->add }}</span></td>
				<td>@if ($row->tel){{ $row->tel }}@else---@endif<br />{{ explode('@', (string) $row->email)[0] }}@...</td>
				<td align="center">
					<ul>
						<li>
							<a href="shop_detail.php?shop_cd={{ $row->cd }}&page={{ $page }}&kensu={{ $kensu }}" class="btnStyle">詳細</a>
						</li>
					</ul>
				</td>
			</tr>
			@endforeach
		</table>


	@else
	検索条件に合った店舗は、見つかりませんでした。再度検索してください
	@endif

	</form>

	<ul class="btnBox2">
	@if ($maxrows){!! \App\Support\LegacyHtml::pager($pagerBase, $page, $kensu, $maxrows) !!}@endif
	</ul>


<!-- / .contents --></div>
@endsection
