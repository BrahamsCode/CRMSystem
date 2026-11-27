{{-- Legacy: system/inc/function.php → createform_request() (modo 'input') y createform_confirm() (modo 'confirm').
     Pinta UN campo personalizado (tb_search_form) según su form_type, como el legacy.
     Variables:
       $field      fila de tb_search_form (array o modelo): cd, form_type, style_class, flds_row, flds_col, titles, data, unit
       $values     array nombre-de-campo => valor (equivale a $_REQUEST del legacy: form{cd}, form{cd}_{n}, form{cd}yyy/mmm/ddd,
                   form{cd}_{col}_{row}, yoyaku_menu_cd[]). En alta/edición ya viene resuelto (old() o tb_customer_sub).
       $mode       'input' | 'confirm'
       $menuItems  (solo form_type 'メニュー項目') filas de tb_yoyaku_koumoku_master; ver partials/menu_table --}}
@php
    $cd = $field['cd'];
    $type = $field['form_type'];
    $cls = $field['style_class'] ?? '';
    $rows = (int) ($field['flds_row'] ?? 0);
    $titles = $rows > 0 ? explode('<>', (string) ($field['titles'] ?? '')) : [];
    $data = $rows > 0 ? explode('<>', (string) ($field['data'] ?? '')) : [];
    $units = $rows > 0 ? explode('<>', (string) ($field['unit'] ?? '')) : [];
    $val = fn (string $key) => $values[$key] ?? null;
    $f = 'form'.$cd;
    // creat_selectBox(): título "select[項目名]:a,b,c"
    $isSelectTitle = fn ($title) => preg_match('/^select\[.+\]\:/', (string) $title) === 1;
    $selectHeader = fn ($title) => preg_split('/[\[\]]/', (string) $title)[1] ?? '';
    $selectOptions = fn ($title) => explode(',', explode(':', (string) $title)[1] ?? '');
@endphp
@if ($mode === 'input')
@switch($type)
@case('テキスト')
<input type="text" name="{{ $f }}" id="{{ $f }}" class="{{ $cls }}" value="{{ $val($f) }}" />
@break
@case('半角英数テキスト')
@case('メールアドレス')
@case('数値テキスト')
<input type="text" name="{{ $f }}" id="{{ $f }}" value="{{ $val($f) }}" class="{{ $cls }}" style="ime-mode:disabled" />
@break
@case('テキストエリア')
<textarea name="{{ $f }}" id="{{ $f }}" class="{{ $cls }}" rows="5" >{{ $val($f) }}</textarea>
@break
@case('チェックボックス')
@for ($i = 0; $i < $rows; $i++)<label for="{{ $f }}_{{ $i }}" class="labelList"><input type="checkbox" name="{{ $f }}_{{ $i }}" id="{{ $f }}_{{ $i }}"  class="{{ $cls }}" value="{{ $data[$i] ?? '' }}"@if (($data[$i] ?? null) == $val($f.'_'.$i) && ($titles[$i] ?? '')) checked=checked @endif />{{ $titles[$i] ?? '' }}</label>@endfor
@break
@case('ラジオボタン')
@for ($i = 0; $i < $rows; $i++)<input type="radio" name="{{ $f }}" id="{{ $f }}{{ $i }}" class="{{ $cls }}" value="{{ $data[$i] ?? '' }}"@if (($data[$i] ?? null) == $val($f) && ($titles[$i] ?? '')) checked=checked @endif /><label for="{{ $f }}{{ $i }}">{{ $titles[$i] ?? '' }}</label><br />@endfor
@break
@case('セレクト')
<select name="{{ $f }}" id="{{ $f }}" class="{{ $cls }}">
<option value="">選択して下さい</option>
@for ($i = 0; $i < $rows; $i++)
<option value="{{ $data[$i] ?? '' }}"@if (($data[$i] ?? null) == $val($f)) selected=selected @endif>{{ $titles[$i] ?? '' }}</option>
@endfor
</select>
@break
@case('年')
{{-- El legacy repite el atributo class; se conserva --}}
<input type="text" name="{{ $f }}yyy" id="{{ $f }}yyy" class="{{ $cls }}" maxlength="4" value="{{ $val($f.'yyy') }}" class="{{ $cls }}" style="ime-mode:disabled" />年
@break
@case('年月')
<input type="text" name="{{ $f }}yyy" id="{{ $f }}yyy" maxlength="4" value="{{ $val($f.'yyy') }}" class="{{ $cls }}" style="ime-mode:disabled" />年<input type="text" name="{{ $f }}mmm" id="{{ $f }}mmm" maxlength="2" value="{{ $val($f.'mmm') }}" class="{{ $cls }}" style="ime-mode:disabled" />月
@break
@case('年月日')
@case('起算日')
<input type="text" name="{{ $f }}yyy" id="{{ $f }}yyy" maxlength="4" value="{{ $val($f.'yyy') }}" class="{{ $cls }}" style="ime-mode:disabled" />年<input type="text" name="{{ $f }}mmm" id="{{ $f }}mmm" maxlength="2" value="{{ $val($f.'mmm') }}" class="{{ $cls }}" style="ime-mode:disabled" />月<input type="text" name="{{ $f }}ddd" id="{{ $f }}ddd" maxlength="2" value="{{ $val($f.'ddd') }}" class="{{ $cls }}" style="ime-mode:disabled" />日
@break
@case('月日')
<input type="text" name="{{ $f }}mmm" id="{{ $f }}mmm" maxlength="2" value="{{ $val($f.'mmm') }}" class="{{ $cls }}" style="ime-mode:disabled" />月<input type="text" name="{{ $f }}ddd" id="{{ $f }}ddd" maxlength="2" value="{{ $val($f.'ddd') }}" class="{{ $cls }}" style="ime-mode:disabled" />日
@break
@case('メニュー項目')
@include('admin.clientes.partials.menu_table', ['menuItems' => $menuItems ?? [], 'selected' => $values['yoyaku_menu_cd'] ?? [], 'view' => false])
@break
@case('テーブル')
{{-- Como el legacy: las filas de datos no abren <tr>, solo lo cierran --}}
<table class="{{ $cls }}">
<tr>@for ($i = 0; $i < $rows; $i++)<th>{{ $isSelectTitle($titles[$i] ?? '') ? $selectHeader($titles[$i]) : ($titles[$i] ?? '') }}</th>@endfor
</tr>@for ($col = 0; $col < (int) ($field['flds_col'] ?? 0); $col++)@for ($i = 0; $i < $rows; $i++)@php $cell = $f.'_'.$col.'_'.$i; @endphp
@if ($isSelectTitle($titles[$i] ?? ''))<td><select name="{{ $cell }}" id="{{ $cell }}">
<option value=""></option>
@foreach ($selectOptions($titles[$i]) as $opt)
<option value="{{ $opt }}"@if ($val($cell) == $opt) selected="selected"@endif>{{ $opt }}</option>
@endforeach
</select></td>@else<td><input name="{{ $cell }}" id="{{ $cell }}" class="{{ $data[$i] ?? '' }}" value="{{ $val($cell) }}" />{{ $units[$i] ?? '' }}</td>@endif
@endfor
</tr>@endfor</table>
@break
@endswitch
@else
@switch($type)
@case('テキスト')
@case('半角英数テキスト')
@case('メールアドレス')
@case('数値テキスト')
@case('ラジオボタン')
@case('セレクト')
{{ $val($f) }}
@break
@case('テキストエリア')
{!! nl2br(e($val($f) ?? '')) !!}
@break
@case('チェックボックス')
@for ($i = 0; $i < $rows; $i++)@if (($data[$i] ?? null) == $val($f.'_'.$i)){{ $val($f.'_'.$i) }}<br />@endif @endfor
@break
@case('年')
@if ($val($f.'yyy') != ''){{ $val($f.'yyy') }}年@endif
@break
@case('年月')
@if ($val($f.'yyy') != ''){{ $val($f.'yyy') }}年@endif @if ($val($f.'mmm') != ''){{ $val($f.'mmm') }}月@endif
@break
@case('年月日')
@case('起算日')
@if ($val($f.'yyy') != ''){{ $val($f.'yyy') }}年@endif @if ($val($f.'mmm') != ''){{ $val($f.'mmm') }}月@endif @if ($val($f.'ddd') != ''){{ $val($f.'ddd') }}日@endif
@break
@case('月日')
@if ($val($f.'mmm') != ''){{ $val($f.'mmm') }}月@endif @if ($val($f.'ddd') != ''){{ $val($f.'ddd') }}日@endif
@break
@case('メニュー項目')
@if (isset($values['yoyaku_menu_cd']))
@php $selectedMenus = (array) $values['yoyaku_menu_cd']; @endphp
@include('admin.clientes.partials.menu_table', [
    'menuItems' => collect($menuItems ?? [])->filter(fn ($m) => isset($selectedMenus[$m['cd']]))->values(),
    'selected' => $selectedMenus,
    'view' => true,
])
@endif
@break
@case('テーブル')
<table class="{{ $cls }}">
<tr>@for ($i = 0; $i < $rows; $i++)<th>{{ $isSelectTitle($titles[$i] ?? '') ? $selectHeader($titles[$i]) : ($titles[$i] ?? '') }}</th>@endfor
</tr>@for ($col = 0; $col < (int) ($field['flds_col'] ?? 0); $col++)@for ($i = 0; $i < $rows; $i++)@php $cell = $f.'_'.$col.'_'.$i; @endphp<td>{{ $val($cell) }}{{ $val($cell) == '' ? '' : ($units[$i] ?? '') }}</td>@endfor
</tr>@endfor</table>
@break
@endswitch
@endif
