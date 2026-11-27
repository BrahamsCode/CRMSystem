{{-- Legacy: system/category1/customer/form_config.php (表示・検索・CSV項目設定) + system/inc/check_select.php.
     Variables:
       $siteName           nombre del sitio (<title>)
       $koumokuItems       filas de tb_koumoku (tb_name = 'tb_customer', ORDER BY rank, cd): cd, tb_koumoku_name,
                           koumoku_disp (表示), koumoku_check (必須), mail_mag_select (検索), koumoku_select (CSV)
       $applicationUseFlg  bool: admin_master.application_use_flg = 1 (muestra マイコレ使用, ポイント失効日, 失効ポイント)
       $customCategories   colección de ['category' => CustomCategory, 'fields' => colección de CustomField]: categorías de
                           clientes visibles (hyouji_flg = '1') de un solo registro, con sus campos (disp_flg <> '9', ORDER BY rank)
     El HTML de las tablas es el del legacy, con sus <td> sin cerrar y los </th> sobrantes. --}}
@extends('layouts.legacy')

@section('menu', 'clientes')
@section('title', '表示・検索・CSV項目設定 - ' . $siteName)

@php
    // El legacy compara con == 1 (cadena numérica)
    $on = fn ($v) => is_numeric($v) && $v == 1;
    $fixedItems = ['顧客番号', '登録店舗', '情報変更日', '会員登録日', '顧客グループ', '年齢', '登録区分', '端末登録', '端末機ID',
        'スタンプ数', '不達配信数', 'ポイント', '金額ランク（今回）', '金額ランク（前回）', '来店ランク（今回）', '来店ランク（前回）'];
    $appItems = ['ポイント失効日', '失効ポイント', 'マイコレ使用'];
    $itemMax = count($koumokuItems);
    $displayNone = '';
    $searchFormNum = 0;
@endphp

@section('content')
<div class="contents">
<p class="h2Title">■表示・検索・CSV項目設定</p>


<form method="post" name="form1" id="form1" class="form" action="{{ route('admin.clientes.field-settings.update') }}">
@csrf

<fieldset>
<legend>顧客情報</legend>
<p><font color="#C2E4EF">■</font>…携帯登録時表示項目　<font color="#FF9F9F">■</font>…携帯登録時必須項目　<font color="#B3EFB3">■</font>…検索条件項目　<font color="#FFFFCC">■</font>…CSV出力項目<br />
<em>※</em>登録時表示項目を数多く設定すると、携帯機種によっては表示がおかしくなることがあるので、ご注意ください。</p>


<p class="h3Title">基本情報</p>
<table class="tableBox">
@foreach ($koumokuItems as $x => $item)
@php $no = $x + 1; $name = (string) $item->tb_koumoku_name; @endphp
@if ($no % 3 == 1)<tr>@endif
@if ($applicationUseFlg || $name != 'マイコレ使用')<th><small>{{ $name }}</small></th>@endif
@if ($name === 'メルマガ区分')
		<td bgcolor="#C2E4EF" class="textCenter"><input type="checkbox" name="item_cd1_{{ $no }}" id="item_cd1_{{ $no }}" value="1"@if ($on($item->koumoku_disp)) checked="checked"@endif />
		<td colspan="2" bgcolor="#C2E4EF" class="textCenter">必須<input type="hidden" name="item_cd2_{{ $no }}" id="item_cd2_{{ $no }}" value="1" /><input type="hidden" name="item_cd3_{{ $no }}" id="name_kana" value="1" /></td>
		<td bgcolor="#FFFFCC" class="textCenter"><input type="checkbox" name="item_cd4_{{ $no }}" id="item_cd4_{{ $no }}" value="1"@if ($on($item->koumoku_select)) checked="checked"@endif /><input type="hidden" name="item_cd_{{ $no }}" value="{{ $item->cd }}" /></td>
@elseif (in_array($name, $fixedItems, true) || in_array($name, $appItems, true))
@php
    if (in_array($name, $appItems, true) && ! $applicationUseFlg) {
        $displayNone = 'style="display: none;"';
    }
    $hide = in_array($name, $appItems, true) ? $displayNone : '';
@endphp
		<td bgcolor="#C2E4EF" class="textCenter" {!! $hide !!}><input type="hidden" name="item_cd1_{{ $no }}" id="item_cd1_{{ $no }}" value="" />
		<td bgcolor="#FF9F9F" class="textCenter" {!! $hide !!}><input type="hidden" name="item_cd2_{{ $no }}" id="item_cd2_{{ $no }}" value=""/></td>
		<td bgcolor="#B3EFB3" class="textCenter" {!! $hide !!}><input type="checkbox" name="item_cd3_{{ $no }}" id="item_cd3_{{ $no }}" value="1"@if ($on($item->mail_mag_select)) checked="checked"@endif /></td>
		<td bgcolor="#FFFFCC" class="textCenter" {!! $hide !!}><input type="checkbox" name="item_cd4_{{ $no }}" id="item_cd4_{{ $no }}" value="1"@if ($on($item->koumoku_select)) checked="checked"@endif /><input type="hidden" name="item_cd_{{ $no }}" value="{{ $item->cd }}" /></td>
@else
		<td bgcolor="#C2E4EF" class="textCenter"><input type="checkbox" name="item_cd1_{{ $no }}" id="item_cd1_{{ $no }}" value="1"@if ($on($item->koumoku_disp)) checked="checked"@endif />
		<td bgcolor="#FF9F9F" class="textCenter"><input type="checkbox" name="item_cd2_{{ $no }}" id="item_cd2_{{ $no }}" value="1"@if ($on($item->koumoku_check)) checked="checked"@endif /></td>
		<td bgcolor="#B3EFB3" class="textCenter"><input type="checkbox" name="item_cd3_{{ $no }}" id="item_cd3_{{ $no }}" value="1"@if ($on($item->mail_mag_select)) checked="checked"@endif /></td>
		<td bgcolor="#FFFFCC" class="textCenter"><input type="checkbox" name="item_cd4_{{ $no }}" id="item_cd4_{{ $no }}" value="1"@if ($on($item->koumoku_select)) checked="checked"@endif /><input type="hidden" name="item_cd_{{ $no }}" value="{{ $item->cd }}" /></td>
@endif
@if ($no % 3 == 0)
</th></tr>
@else
</th>
@if ($x == $itemMax - 1)
	<th colspan="{{ 5 * (3 - ($itemMax % 3)) }}"></th>
@endif
@endif
@endforeach

</table>

<p style="margin-top: 30px;"><font color="#77BBFF">■</font>…管理画面登録時表示項目　<font color="#FF9F9F">■</font>…管理画面登録時必須項目　<font color="#B3EFB3">■</font>…検索条件項目　<font color="#FFFFCC">■</font>…CSV出力項目<br />
<em>※</em>登録時表示項目を数多く設定すると、携帯機種によっては表示がおかしくなることがあるので、ご注意ください。</p>


@foreach ($customCategories as $block)
@php $fields = $block['fields']; $fieldMax = count($fields); @endphp

{{-- name guardado con get_form2() (entidades ya escapadas): se imprime tal cual, como el legacy --}}
<p class="h3Title">{!! $block['category']->name !!}</p>

<table class="tableBox">
@foreach ($fields as $row => $field)
@php $cnt = $row + 1; @endphp
@if ($cnt % 3 == 1)
	<tr>
	<th>
@else
	<th>
@endif
		<small>{!! $field->name !!}</small>
@if (in_array($field->form_type, ['画像とテキスト', '画像', 'テーブル'], true))
<br /><span style="color:red">PC版のみ編集可</span>
@endif
</th>


		<td bgcolor="#77BBFF" class="textCenter">
			<select name="tb_search_form_disp_{{ $searchFormNum }}" id="tb_search_form_disp_{{ $searchFormNum }}" style="max-width: 85px;">
				<option value="2"@if ($field->hyouji_flg == '2') selected @endif>管理画面のみ</option>
				<option value="1"@if ($field->hyouji_flg == '1') selected @endif>管理・携帯 両画面</option>
				<option value=""@if ($field->hyouji_flg == '') selected @endif>非表示</option>
			</select>
		</td>


		<td bgcolor="#FF9F9F" class="textCenter"><input type="checkbox" name="tb_search_form_hissu_{{ $searchFormNum }}" id="tb_search_form_hissu_{{ $searchFormNum }}" value="1"@if ($on($field->hissu)) checked="checked"@endif /></td>


		<td bgcolor="#B3EFB3" class="textCenter"><input type="checkbox" name="tb_search_form_search_{{ $searchFormNum }}" id="tb_search_form_search_{{ $searchFormNum }}" value="1"@if ($on($field->serach_flg)) checked="checked"@endif />

		<td bgcolor="#FFFFCC" class="textCenter"><input type="checkbox" name="tb_search_form_csv_{{ $searchFormNum }}" id="tb_search_form_csv_{{ $searchFormNum }}" value="1"@if ($on($field->csv_flg)) checked="checked"@endif />

<input type="hidden" name="tb_search_form_cd_{{ $searchFormNum }}" id="tb_search_form_cd_{{ $searchFormNum }}" value="{{ $field->cd }}" />
</td>


@if ($cnt % 3 == 0)
	</th>
	</tr>
@else
	</th>
@if ($row == $fieldMax - 1)
	<th colspan="{{ 5 * (3 - ($fieldMax % 3)) }}"></th>
@endif
@endif
@php $searchFormNum++; @endphp
@endforeach

</table>

@endforeach
<input type="hidden" name="send_search_form_max" id="send_search_form_max" value="{{ $searchFormNum }}" />

</fieldset>

<div class="btnBox">

<input type="hidden" name="item_max_no" value="{{ $itemMax }}">
<input type="hidden" name="act" value="up">
<input type="submit" name="enter" id="enter" class="btn btnStyleM" value="この内容で変更する">
</div>

</form>

<!-- / .contents -->
</div>
@endsection
