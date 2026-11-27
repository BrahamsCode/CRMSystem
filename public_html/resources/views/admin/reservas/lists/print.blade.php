{{--
    Legacy: system/category9/yoyaku/print/yoyaku_list_print.php (予約一覧出力: página suelta, sin head.php).
    Variables:
      $date    string 'Y/m/d' del listado (se imprime al principio, como el legacy).
      $shopCd  int    tienda listada.
      $d       ?string `d` de la petición (Ymd), para el formulario del CSV.
      $columns array  ['kana' => bool, 'tel' => bool, 'mail' => bool, 'free' => [N => etiqueta], 'shimei' => '指名' de manageWord].
      $rows    Collection filas: time, name, kana, tel, mail, free [N => texto], staff, menu.
    El botón Flash de AdobeAIR (csv_download/sample.swf) no se migra: siempre el formulario 「CSV出力する」.
--}}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ja" xml:lang="ja">
<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="robots" content="noindex" />
<meta name="robots" content="nofollow" />
<meta name="robot-control" content="deny-all" />
<meta name="robots" content="none" />
<meta http-equiv="content-style-type" content="text/css" />
<meta http-equiv="content-script-type" content="text/javascript" />

<style type="text/css"><!--

table {
	border-collapse: collapse;
}

th {
	border: 1px solid #999999;
}

td {
	border: 1px solid #999999;
}


--></style>


<title>予約一覧出力</title>
</head>
<body>


{{ $date }}
@if ($rows->isNotEmpty())
<table>
<tr>
<th>&nbsp;</th>
<th>名前</th>
@if ($columns['kana'])
<th>カナ</th>
@endif
@if ($columns['tel'])
<th>電話番号</th>
@endif
@if ($columns['mail'])
<th>メールアドレス</th>
@endif
@foreach ($columns['free'] as $label)
<th>{{ $label }}</th>
@endforeach
<th>{{ $columns['shimei'] }}</th>
<th>作業内容</th>
</tr>
@foreach ($rows as $row)
<tr>
<td>{{ $row['time'] }}</td>
<td>{{ $row['name'] }}</td>
@if ($columns['kana'])
<td>{{ $row['kana'] }}</td>
@endif
@if ($columns['tel'])
<td>{{ $row['tel'] }}</td>
@endif
@if ($columns['mail'])
<td>{{ $row['mail'] }}</td>
@endif
@foreach (array_keys($columns['free']) as $n)
<td>{{ $row['free'][$n] ?? '' }}</td>
@endforeach
<td>{{ $row['staff'] }}</td>
<td>{{ $row['menu'] }}</td>
</tr>
@endforeach
</table>


<form action="{{ route('admin.reservas.csv') }}" method="post">
@csrf
<input type="hidden" name="shop" value="{{ $shopCd }}">
<input type="hidden" name="d" value="{{ $d }}">
<input type="hidden" name="titleflg" value="1">
<input type="hidden" name="flg" value="upload">
<input type="hidden" name="exp" value="ＣＳＶダウンロード" />
<input type="submit" name="enter" id="enter" class="btn" value="CSV出力する" />
</form>
@else
<div>該当日の予約リストはありません。</div>
@endif

</body>
</html>
