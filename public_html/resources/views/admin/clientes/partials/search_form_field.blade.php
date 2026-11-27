{{-- titles/data de tb_search_form se guardan con get_form2 (entidades): se imprimen con {!! !!} (CONVENCIONES §4).
     Legacy: system/inc/function.php createform_search_form() (+ makeMenuTable() para 'メニュー項目').
     Campo del buscador para un項目 adicional (tb_search_form).
     Variables:
       $form         fila tb_search_form: 'cd', 'form_type', 'flds_row', 'titles' y 'data' (separados por '<>'), 'unit'
       $filters      array con los valores del buscador actual
       $yoyakuMenus  (solo 'メニュー項目') tb_yoyaku_koumoku_master + categoría: 'cd', 'name', 'category_name'
                     (disp_flg = '1'; si no es master, solo su tienda o -1; orden type_flg, rank de categoría, tb_shop_cd, rank)
--}}
@php
    $filters = $filters ?? [];
    $v = fn (string $key) => old($key, $filters[$key] ?? null);
    $type = $form['form_type'];
    $name = 'form' . $form['cd'];
    $titles = ($form['flds_row'] ?? 0) > 0 ? explode('<>', (string) $form['titles']) : [];
    $data = ($form['flds_row'] ?? 0) > 0 ? explode('<>', (string) $form['data']) : [];
    $year = now('Asia/Tokyo')->year;
@endphp
@switch($type)
	@case('テキスト')
	@case('テキストエリア')
	@case('テーブル')
<input type="text" name="{{ $name }}" class="inputM" value="{{ $v($name) }}" />
		@break

	@case('半角英数テキスト')
	@case('メールアドレス')
<input type="text" name="{{ $name }}" value="{{ $v($name) }}" class="inputL" style="ime-mode:disabled" />
		@break

	@case('数値テキスト')
<input type="text" name="{{ $name }}_from" id="{{ $name }}_from" value="{{ $v($name . '_from') }}" class="inputS" style="ime-mode:disabled" /> ～
			<input type="text" name="{{ $name }}_to" id="{{ $name }}_to" value="{{ $v($name . '_to') }}" class="inputS" style="ime-mode:disabled" />
		@break

	@case('画像とテキスト')
	@case('画像')
@php $picFlg = (string) $v('form_pic_flg' . $form['cd']); @endphp
<input type="radio" name="form_pic_flg{{ $form['cd'] }}" value=""@if ($picFlg !== '1' && $picFlg !== '0') checked=checked @endif /> 指定なし　<input type="radio" name="form_pic_flg{{ $form['cd'] }}" value="1"@if ($picFlg === '1') checked=checked @endif /> あり　<input type="radio" name="form_pic_flg{{ $form['cd'] }}" value="0"@if ($picFlg === '0') checked=checked @endif /> なし<br />@if ($type == '画像とテキスト')<input type="text" name="{{ $name }}" class="inputM" value="{{ $v($name) }}" />@endif
		@break

	{{-- 2012.05.18 and・or対応 --}}
	@case('チェックボックス')
@php
    $hasCheckBoxes = collect($titles)->take((int) $form['flds_row'])->contains(fn ($title) => $title != '');
@endphp
@if ($hasCheckBoxes)
<div style="border:1px dashed #555555;max-height: 150px; overflow-y: auto;">@for ($i = 0; $i < (int) $form['flds_row']; $i++)@if (($titles[$i] ?? '') != '')<label for="{{ $name }}_{{ $i }}" class="labelList"><input type="checkbox" id="{{ $name }}_{{ $i }}" name="{{ $name }}_{{ $i }}" value="{!! $data[$i] ?? '' !!}"@if (($data[$i] ?? null) == $v($name . '_' . $i)) checked=checked @endif /> {!! $titles[$i] !!}</label>@endif @endfor</div><p>検索方法：<input type="radio" name="{{ $name }}_andor_flg" value="and"@if ($v($name . '_andor_flg') != 'or') checked @endif />&nbsp;AND&emsp;<input type="radio" name="{{ $name }}_andor_flg" value="or"@if ($v($name . '_andor_flg') == 'or') checked @endif />&nbsp;OR<br/></p>
@endif
		@break

	{{-- 2009/12/14 ラジオの検索もselect --}}
	@case('セレクト')
	@case('ラジオボタン')
<select name="{{ $name }}">
<option value="">選択して下さい</option>
@for ($i = 0; $i < (int) $form['flds_row']; $i++)
<option value="{!! $data[$i] ?? '' !!}"@if (($data[$i] ?? null) == $v($name)) selected=selected @endif>{!! $titles[$i] ?? '' !!}</option>
@endfor
</select>
		@break

	{{-- 2012.03.13 カレンダー用クラス setDate --}}
	@case('年')
	@case('年月')
	@case('年月日')
<input type="text" name="{{ $name }}_from" id="{{ $name }}_from" value="{{ $v($name . '_from') }}" class="inputS setDate" style="ime-mode:disabled" />  ～
			<input type="text" name="{{ $name }}_to" id="{{ $name }}_to" value="{{ $v($name . '_to') }}" class="inputS setDate" style="ime-mode:disabled" /> @if ($type == '年')<br />例){{ $year - 1 }}～{{ $year }}<br />@elseif ($type == '年月')<br />例){{ $year }}/01～{{ $year }}/12<br />@else<br />例){{ $year }}/01/01～{{ $year }}/12/31<br />@endif
		@break

	@case('起算日')
<input type="text" name="{{ $name }}_from" id="{{ $name }}_from" value="{{ $v($name . '_from') }}" class="inputM setDate" style="ime-mode:disabled" />{{ $form['unit'] ?? '' }}  ～
			<input type="text" name="{{ $name }}_to" id="{{ $name }}_to" value="{{ $v($name . '_to') }}" class="inputM setDate" style="ime-mode:disabled" />{{ $form['unit'] ?? '' }} <br />例){{ $year }}/01/01～{{ $year }}/12/31<br />
		@break

	{{-- 2011/02/17 月日 --}}
	@case('月日')
<select name="{{ $name }}_mmm">
<option value=""> </option>
@for ($m = 1; $m <= 12; $m++)
<option value="{{ sprintf('%02d', $m) }}"@if (sprintf('%02d', $m) == $v($name . '_mmm')) selected @endif>{{ sprintf('%02d', $m) }}月</option>
@endfor
</select>
<select name="{{ $name }}_ddd">
<option value=""> </option>
@for ($d = 1; $d <= 31; $d++)
<option value="{{ sprintf('%02d', $d) }}"@if (sprintf('%02d', $d) == $v($name . '_ddd')) selected @endif>{{ sprintf('%02d', $d) }}日</option>
@endfor
</select>
<br />（月/日）<br />
		@break

	{{-- 2011/09/27 メニュー項目 (makeMenuTable). El legacy cierra un </ul> que nunca abre: se conserva igual. --}}
	@case('メニュー項目')
<div style="border:1px dashed #555555;max-height: 150px; overflow-y: auto;">
@php $preCateName = ''; @endphp
@forelse ($yoyakuMenus ?? [] as $menu)
@if (($menu['category_name'] ?? '') != $preCateName)
<li class="echoKoumokuYoyakuKoMidashi">{{ ($menu['category_name'] ?? '') != '' ? $menu['category_name'] : 'カテゴリ無し' }}</li>
@endif
<li>
<input type="checkbox" name="yoyaku_menu_cd[{{ $menu['cd'] }}]" id="yoyaku_menu_cd_{{ $menu['cd'] }}" value="1"@if (isset($filters['yoyaku_menu_cd'][$menu['cd']])) checked="checked"@endif />
<label for="yoyaku_menu_cd_{{ $menu['cd'] }}">{{ $menu['name'] }}</label>
</li>
@if ($loop->last)
</ul>
@endif
@php $preCateName = $menu['category_name'] ?? ''; @endphp
@empty
<p>メニューデータはありません</p>
@endforelse
</div><p>検索方法：<input type="radio" name="yoyaku_andor_flg" value="and"@if ($v('yoyaku_andor_flg') != 'or') checked @endif />&nbsp;AND&emsp;<input type="radio" name="yoyaku_andor_flg" value="or"@if ($v('yoyaku_andor_flg') == 'or') checked @endif />&nbsp;OR<br/>来店履歴：<input type="radio" name="yoyaku_raiten_flg" value="全体"@if ($v('yoyaku_raiten_flg') != '直近') checked @endif />&nbsp;全来店&emsp;<input type="radio" name="yoyaku_raiten_flg" value="直近"@if ($v('yoyaku_raiten_flg') == '直近') checked @endif />&nbsp;直近来店</p>
		@break
@endswitch
