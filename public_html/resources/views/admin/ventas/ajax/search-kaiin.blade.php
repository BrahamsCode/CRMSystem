{{--
    Filas del 会員検索 de 売上データ作成 (legacy: karute_menu/ajax_search_kaiin.php).
    Datos: $members (Collection<Customer>: cd, id, name), máx. 200
--}}
@forelse ($members as $member)
<tr id="kaiinData_{{ $member->cd }}">
	<td>【&nbsp;ID：&nbsp;{{ $member->id }}&nbsp;】</td>
	<td>{{ $member->name }}</td>
	<td>
	<input type="button" class="btnStyle addKaiin" id="kaiin_{{ $member->cd }}" value="　選択　" />
	<input type="button" class="btnStyle" onclick="javascript:window.open('/system/category1/inc/customer_detail.php?kaiin_cd={{ $member->cd }}&amp;wintype=no_headers', 'mywindow1', 'width=970, height=550, menubar=no, toolbar=no, scrollbars=yes');" value="　詳細　">
	</td>
</tr>

@empty
<tr><td class="noData">データが見つかりませんでした。</td></tr>
@endforelse
