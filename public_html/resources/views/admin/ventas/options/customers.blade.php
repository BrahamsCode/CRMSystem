{{--
    Legacy: system/category1/karute_menu/ajax_search_kaiin.php (filas del 会員検索 de karute_new_frm.php).
    Variables: $customers (Collection<object> cd, id, name; máx. 200).
    Sin la rama de tablet (roid.popUp) del legacy: el 詳細 abre siempre la ventana emergente.
--}}
@forelse ($customers as $customer)
<tr id="kaiinData_{{ $customer->cd }}">
	<td>【&nbsp;ID：&nbsp;{{ $customer->id }}&nbsp;】</td>
	<td>{{ $customer->name }}</td>
	<td>
	<input type="button" class="btnStyle addKaiin" id="kaiin_{{ $customer->cd }}" value="　選択　" />
	<input type="button" class="btnStyle" onclick="javascript:window.open('/system/category1/inc/customer_detail.php?kaiin_cd={{ $customer->cd }}&amp;wintype=no_headers', 'mywindow1', 'width=970, height=550, menubar=no, toolbar=no, scrollbars=yes');" value="　詳細　">
	</td>
</tr>

@empty
<tr><td class="noData">データが見つかりませんでした。</td></tr>
@endforelse
