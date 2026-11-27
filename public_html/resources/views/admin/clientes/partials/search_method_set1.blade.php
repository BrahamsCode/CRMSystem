{{-- Legacy: system/inc/search_method_set1.php (検索設定テーブル, 2012.12.07).
     Variables: $filters, $hiddenSearchMenus, $cellTag (ver partials/search_set1)
--}}
@php
    $cellTag = $cellTag ?? 'th';
    $hiddenSearchMenus = $hiddenSearchMenus ?? [];
    $filters = $filters ?? [];
    $v = fn (string $key) => old($key, $filters[$key] ?? null);
@endphp
<fieldset class="MT10">
<legend class="searchLegend"><img src="{{ asset('legacy/system/img/' . (in_array('method', $hiddenSearchMenus) ? 'icon_plus' : 'icon_minus') . '.gif') }}" alt="" width="9" height="9" style="vertical-align: middle;" />検索設定</legend>
<div class="searchDiv" menu="method"@if (in_array('method', $hiddenSearchMenus)) style="display:none;"@endif>
<p>※検索項目「登録区分」は絞込み条件選択の対象外です。必ず検索条件に一致するもので検索されます。</p>
<table class="tableBox9">

	<tr>
		<{{ $cellTag }}>
			<span>検索方法</span><br />

			<input type="radio" name="search_method1_type" value="1" @checked($v('search_method1_type') != '2')/>AND検索&emsp;
			<input type="radio" name="search_method1_type" value="2" @checked($v('search_method1_type') == '2') />OR検索
		</{{ $cellTag }}>

		<{{ $cellTag }}>
			<span>絞込み条件</span><br />

			<input type="radio" name="search_method2_type" value="1" @checked($v('search_method2_type') != '2')/>検索条件に一致するもの&emsp;
			<input type="radio" name="search_method2_type" value="2" @checked($v('search_method2_type') == '2') />検索条件に一致しないもの
		</{{ $cellTag }}>

		<{{ $cellTag }}>
			&nbsp;
		</{{ $cellTag }}>
	</tr>

</table>
</div>
</fieldset>
