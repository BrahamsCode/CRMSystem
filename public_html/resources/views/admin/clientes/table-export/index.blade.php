{{-- Legacy: system/category1/customer/csv_export.php (CSVエクスポート, sin enlace en el menú).
     Variables:
       $tables  array<string, string>: tabla => descripción (CustomerCsvExporter::DUMP_TABLES)
       $error   string: 「データがありません。」 o 「不正アクセス！！！」 del último POST ('' = ninguno)
     Diferencias: el legacy calculaba $error pero no lo pintaba; aquí sale bajo el título. Sin Flash
     (AC_RunActiveContent.js) y sin <title> con nombre ($title vacío en el legacy). --}}
@extends('layouts.legacy')

@section('menu', 'clientes')
@section('title', 'CSVエクスポート - ' . $siteName)

@section('content')
<div class="contents pB20" style="overflow:hidden;">

<p class="h2Title">■CSVエクスポート</p>

@if ($error !== '')
<p style="color:#FF0000;">{{ $error }}</p>
@endif

<table class="tableBox3">
@foreach ($tables as $tableName => $tableDesc)
	<tr>
		<td style="text-align:left;">{{ $tableName }}</td>
		<td style="text-align:left;">{{ $tableDesc }}</td>
		<td style="text-align:center;">
			<form method="POST" action="{{ route('admin.clientes.table-export.download') }}">
				@csrf
				<input type="hidden" name="act" value="export" />
				<input type="hidden" name="table" value="{{ $tableName }}" />
				<input type="submit" class="btn btnStyleS" value="エクスポート" />
			</form>
		</td>
	</tr>
@endforeach
</table>

<!-- / .contents --></div>
@endsection
