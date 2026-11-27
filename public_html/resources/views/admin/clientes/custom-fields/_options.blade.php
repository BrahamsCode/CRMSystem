{{-- Legacy: bloque #optionDisp + #eachformData de search_form_new.php / search_form_update.php.
     Variables:
       $val          Closure(string $key): string con el valor YA escapado para HTML (form_type, flds_row, flds_col,
                     eachformN, eachsendN, eachunitN)
       $optionTypes  form_type con opciones (チェックボックス, セレクト, ラジオボタン)
     Parte del <td> de フォームタイプ (el <span id="optionDisp">) y el <div id="eachformData">; se incluye dos veces con $part. --}}
@php $formType = html_entity_decode($val('form_type'), ENT_COMPAT | ENT_HTML401, 'UTF-8'); @endphp
@if ($part === 'optionDisp')
<span id="optionDisp">@if (in_array($formType, $optionTypes, true))
オプション数:<input type="text" name="flds_row" id="flds_row" class="inputS" value="{!! $val('flds_row') !!}" />
<input type="button" onClick="addForms(document.getElementById('flds_row').value,'{!! $val('form_type') !!}')" value="オプション数をセット">
@elseif ($formType === 'テーブル')
行数:<input type="text" name="flds_col" id="flds_col" class="inputS" value="{!! $val('flds_col') !!}" />
列数:<input type="text" name="flds_row" id="flds_row" class="inputS" value="{!! $val('flds_row') !!}" />
<input type="button" onClick="addForms(document.getElementById('flds_row').value,'{!! $val('form_type') !!}')" value="オプション数をセット">
@endif</span>
@else
<div id="eachformData">
@if ((int) html_entity_decode($val('flds_row')) > 0 && (in_array($formType, $optionTypes, true) || $formType === 'テーブル'))
@for ($i = 0; $i < (int) html_entity_decode($val('flds_row')); $i++)
表示値：<input type="text" name="eachform{{ $i }}" id="eachform{{ $i }}" onchange="document.getElementById('eachsend{{ $i }}').value=this.value" class="inputM" value="{!! $val('eachform' . $i) !!}" />
@if ($formType === 'テーブル')
スタイル：<select name="eachsend{{ $i }}" id="eachsend{{ $i }}">
{{ \App\Services\Customers\CustomFieldService::styleOptions(html_entity_decode($val('eachsend' . $i))) }}
</select>
単位：<input type="text" name="eachunit{{ $i }}" id="eachunit{{ $i }}" class="inputS" value="{!! $val('eachunit' . $i) !!}" />
<br />
@else
送信値：<input type="text" name="eachsend{{ $i }}" id="eachsend{{ $i }}" class="inputM" value="{!! $val('eachsend' . $i) !!}" /><br />
@endif
@endfor
@endif
</div>
@endif
