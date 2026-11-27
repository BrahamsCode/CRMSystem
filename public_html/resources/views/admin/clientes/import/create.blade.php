{{-- Legacy: system/category1/customer/csv_import.php (CSVインポート).
     Variables:
       $result  array|null: resultado del último POST (added, updated: int; error: string), null = sin importar
     Diferencias: el legacy pintaba el resultado en la respuesta del POST; aquí se redirige (PRG) y se pinta igual.
     El botón Flash (AdobeAIR) de la plantilla no se reproduce; el <title> del legacy no tenía nombre ($title vacío). --}}
@extends('layouts.legacy')

@section('menu', 'clientes')
@section('title', 'CSVインポート - ' . $siteName)

@section('css')
<style type="text/css">
<!--

.ranlListHeader
{
	overflow: hidden;
}

.resultTxt
{
	position: absolute;
	overflow: hidden;
	color: #FF0000;
}

.dispFlgTbl,
.rankListUl
{
	margin-top: 2.5em;
}

.rankListUl li:nth-child(odd) .rankTbl
{
	background: #FFFFFF;
}

.rankListUl li:nth-child(even) .rankTbl
{
	background: #EFEFEF;
}

.rankTbl
{
	width: 100%;
	margin-top: 2px;
}

.rankTbl th,
.rankTbl td
{
	padding: 10px;
	border: 1px solid #CCCCCC;
	font-weight: normal;
}

.rankTbl .btnStyle
{
	display: inline-block;
	padding: 3px 10px;
}

.ui-state-highlight
{
	height: 5px;
}

.ui-state-highlight,
.ui-widget-content .ui-state-highlight
{
	border: 1px solid #EFDC77;
	background: #FBF9EE;
}

-->
</style>
@endsection

@section('js')
<script type="text/javascript">
$(document).ready(function(){
	$(".ac1").accordion('option', 'active', 0);
});
</script>
@endsection

@section('content')
<div class="contents pB20" style="overflow:hidden;">

<p class="h2Title">■CSVインポート</p>

<div style="text-align:right;">

<form action="{{ route('legacy.clientes.csv-template') }}" method="post">
	@csrf
	<input type="submit" name="enter" id="enter" class="btn btnStyleS" value="テンプレートファイルをダウンロード" style="height: 32px;" />
</form>

</div>

<form action="{{ route('admin.clientes.import.store') }}" method="post" class="form" enctype="multipart/form-data">
	@csrf
	<table class="tableBox dispFlgTbl">
		<tr>
			<th>CSVファイル</th>
			<td>
				<input type="file" style="font-size: 26px;" name="csvFile">
			</td>
		</tr>
	</table>

	<div class="btnBox">
		<input type="submit" name="enter" id="reivse" class="btnStyleM" value="インポートを実行する" />
		<input type="hidden" name="act" id="act" value="import" />
	</div>
</form>

@if ($result)
<div style="margin:10px auto;text-align:center;">
@if ($result['added'] > 0)<span style="display:block;padding:5px;border:2px solid #0000FF;color:#0000FF;background-color:#FFFFFF;">{{ $result['added'] }}件 追加しました。</span>@endif
@if ($result['updated'] > 0)<span style="display:block;padding:5px;border:2px solid #0000FF;color:#0000FF;background-color:#FFFFFF;">{{ $result['updated'] }}件 更新しました。</span>@endif
@if ($result['error'] !== '')<span style="display:block;padding:5px;border:2px solid #FF0000;color:#FF0000;background-color:#FFFFFF;">{{ $result['error'] }}</span>@endif
</div>
@endif

<!-- / .contents --></div>
@endsection
