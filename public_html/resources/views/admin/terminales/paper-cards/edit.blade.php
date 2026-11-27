{{--
    ペーパーサイネージ 詳細/編集 (legacy: card_signage/edit.php). Fragmento HTML (sin layout) que list.php carga
    con jQuery .load() en el diálogo #toolTip; sus botones los engancha el JS del listado.

    Contrato de datos: $card PaperCard (cd, id, title, url, hyouji_flg). url ya guardada con entidades → {!! !!}.
    Diferencia: el legacy no exigía login en edit.php; aquí está dentro del sistema del personal.
--}}
<div id="tooltipBody" style="padding: 10px;">

<div>NO.{{ $card->cd }}</div>

<div id="result"></div>

<div class="right">
稼働区分：
<select name="hyouji_flg" id="hyouji_flg">
<option value="1"{!! (string) $card->hyouji_flg === '1' ? ' selected="selected"' : '' !!}>運用</option>
<option value="9"{!! (string) $card->hyouji_flg === '9' ? ' selected="selected"' : '' !!}>停止</option>
</select>
<input type="button" id="hyouji_btn" value="実行" />
</div>

<form action="#" method="post" name="touroku_form">

<legend>編集</legend>

<table class="tableBox">
	<tr>
		<th>管理用名称</th>
		<td>
			<input type="text" name="title" id="title" value="{{ $card->title }}" />
		</td>
	</tr>
	<tr>
		<th>ID</th>
		<td>
			<input type="text" name="id" id="id" value="{{ $card->id }}" />
			<input type="button" name="check_url_btn" id="check_url_btn" value="確認" />
		</td>
	</tr>
	<tr>
		<th>URL</th>
		<td>
			<input type="text" name="url" id="url" class="WDFL" value="{!! $card->url !!}" />
		</td>
	</tr>
</table>


<input type="hidden" name="cd" id="cd" value="{{ $card->cd }}" />


<div class="center MT10 btnBox">
	<input type="button" id="touroku_btn" class="btn" value="登録" />
	<input type="button" id="delete_btn" class="btn" value="削除" />
</div>


</form>

</div>
