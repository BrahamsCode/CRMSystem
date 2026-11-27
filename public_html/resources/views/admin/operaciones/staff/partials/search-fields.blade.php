{{-- Legacy: system/category5/staff/inc/search_set1.php. Tres campos por fila; cada uno con su hidden
     <campo>_type que interpreta App\Services\Operaciones\StaffSearch.
     Diferencia con el legacy: los campos de texto conservan el valor buscado (el legacy los dejaba vacíos
     por una variable sin asignar).
     Variables: $fields, $words, $shops, $belongposts --}}
@php
    $word = fn (string $key) => (string) ($words[$key] ?? '');
    $year = (int) date('Y');
    $numOptions = function (int $min, int $max): string {
        $html = '';
        for ($i = $min; $i <= $max; $i++) {
            $num = $i < 10 ? '0'.$i : (string) $i;
            $html .= "<option value=\"{$num}\">{$num}</option>\n";
        }

        return $html;
    };
    $selectOptions = fn (string $key, array $values) => collect($values)
        ->map(fn ($v) => '<option'.($word($key) === $v ? ' selected' : '').'>'.e($v).'</option>')->implode("\n");
@endphp
<fieldset>
<legend>スタッフ情報項目</legend>

<table class="tableBox">
@foreach ($fields->chunk(3) as $row)
	<tr>
@foreach ($row as $field)
@php($key = $field->tb_koumoku)
	<th>
@switch($field->tb_koumoku_name)
@case('登録店舗')
@if ($shops->count() > 1)
		<small>{{ $field->tb_koumoku_name }}</small><br />
@can('nucleo.master')
		<select size="1" name="{{ $key }}">
@if ($word($key) !== '' && ($saved = $shops->firstWhere('cd', (int) $word($key))))
		<option value="{{ $saved->cd }}">{{ $saved->kaishamei }}</option>
@endif
		<option value="">選択して下さい</option>
@foreach ($shops as $shop)
		<option value="{{ $shop->cd }}">{{ $shop->kaishamei }}</option>
@endforeach
		</select>
@else
		{{ auth('admin')->user()->shop?->kaishamei }}
		<input type="hidden" name="{{ $key }}" id="{{ $key }}" value="{{ session('shop_cd') }}" />
@endcan
@endif
<input type="hidden" name="{{ $key }}_type" value="xxx">
@break

@case('年齢')
		<small>{{ $field->tb_koumoku_name }}</small><br />
			<input type="text" size="5" name="{{ $key }}" id="{{ $key }}" class="input3S" value="{{ $word($key) }}" />歳 ～
			<input type="text" size="5" name="{{ $key }}2" id="{{ $key }}2" class="input3S" value="{{ $word($key.'2') }}" />歳
<input type="hidden" name="{{ $key }}_type" value="between">
@break

@case('都道府県')
		<small>{{ $field->tb_koumoku_name }}</small><br />
		<select size="1" name="{{ $key }}" class="selectBox">
@if ($word($key) !== '')
		<option value="{{ $word($key) }}">{{ $word($key) }}</option>
@endif
		<option value=""></option>
@foreach (config('crm.prefectures') as $pref)
		<option value="{{ $pref }}">{{ $pref }}</option>
@endforeach
		</select>
<input type="hidden" name="{{ $key }}_type" value="xxx">
@break

@case('個人・法人')
		<small>{{ $field->tb_koumoku_name }}</small><br />
		<select name="{{ $key }}">
		<option value="">選択して下さい</option>
		{!! $selectOptions($key, ['個人', '法人']) !!}
		</select>
<input type="hidden" name="{{ $key }}_type" value="xxx">
@break

@case('性別')
		<small>{{ $field->tb_koumoku_name }}</small><br />
		<select name="{{ $key }}">
		<option value="">選択して下さい</option>
		{!! $selectOptions($key, ['男性', '女性']) !!}
		</select>
<input type="hidden" name="{{ $key }}_type" value="xxx">
@break

@case('生年月日')
<small>{{ $field->tb_koumoku_name }}(生まれ月)</small><br />
		<select name="{{ $key }}">
		<option value="">選択して下さい</option>
@for ($m = 1; $m <= 12; $m++)
@php($month = sprintf('%02d', $m))
		<option @selected($word($key) === "/{$month}/") value="/{{ $month }}/">{{ $month }}月</option>
@endfor
		</select>
<input type="hidden" name="{{ $key }}_type" value="xxx">
@break

@case('血液型')
		<small>{{ $field->tb_koumoku_name }}</small><br />
		<select name="{{ $key }}">
		<option value="">選択して下さい</option>
		{!! $selectOptions($key, ['A', 'B', 'O', 'AB']) !!}
		</select>
<input type="hidden" name="{{ $key }}_type" value="xxx">
@break

@case('所属部署')
		<small>{{ $field->tb_koumoku_name }}</small><br />
		<select name="{{ $key }}" id="{{ $key }}" class="selectBox">
		@include('admin.operaciones.partials.options', ['placeholder' => '▼所属部署選択', 'options' => $belongposts, 'selected' => $word($key)])
		</select>
<input type="hidden" name="{{ $key }}_type" value="xxx">
@break

@case('登録区分')
		<small>{{ $field->tb_koumoku_name }}</small><br />
		<select name="{{ $key }}">
		<option value="">選択して下さい</option>
		<option @selected(in_array($word($key), ['1', ''], true)) value="1">登録済</option>
		<option @selected($word($key) === '9') value="9">削除済</option>
		</select>
<input type="hidden" name="{{ $key }}_type" value="xxx">
@break

@case('入社日')
@case('退社日')
		<small>{{ $field->tb_koumoku_name }}</small><br />
<input type="radio" name="{{ $key }}" value="0" @checked($word($key) !== '1')> 指定しない&nbsp;&nbsp;
<input type="radio" name="{{ $key }}" value="1" @checked($word($key) === '1')> 指定する<br />
@foreach (['' => '', '_to' => '<br />'."\n".'～'."\n".'<br />'."\n"] as $suffix => $before)
{!! $before !!}
@foreach ([['_year', $year - 60, $year, '年'], ['_month', 1, 12, '月'], ['_day', 1, 31, '日']] as [$part, $min, $max, $unit])
<select name="{{ $key.$part.$suffix }}">
@if ($word($key.$part.$suffix) !== '')<option>{{ $word($key.$part.$suffix) }}</option>@endif
<option value=""></option>
{!! $numOptions($min, $max) !!}
</select>{{ $unit }}
@endforeach
@endforeach
<input type="hidden" name="{{ $key }}_type" value="date_hiduke">
@break

@default
		<small>{{ $field->tb_koumoku_name }}</small><br />
			<input type="text" name="{{ $key }}" value="{{ $word($key) }}" class="inputM" />
<input type="hidden" name="{{ $key }}_type" value="xxx">
@endswitch
	</th>
@endforeach
@if ($loop->last && $row->count() < 3)
	<th colspan="{{ 3 - $row->count() }}"></th>
@endif
	</tr>
@endforeach
</table>
</fieldset>
<!--基本情報table end-->
