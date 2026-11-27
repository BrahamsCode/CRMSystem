{{-- Legacy: system/inc/staff_search_set1.php (formulario de búsqueda de working_list / working_total_list).
     Cinco campos por fila; el valor elegido se repite como primera opción de cada select, como el legacy.
     Variables: $fields (tb_koumoku), $words (valores guardados en la sesión), $shops, $belongposts --}}
@php
    $word = fn (string $key) => (string) ($words[$key] ?? '');
    $prefs = ['北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県', '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県',
        '東京都', '神奈川県', '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県', '静岡県', '愛知県', '三重県',
        '滋賀県', '京都府', '大阪府', '兵庫県', '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県', '徳島県',
        '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県', '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'];
    $current = fn (string $key, ?string $label = null) => $word($key) !== ''
        ? '<option value="'.e($word($key)).'">'.e($label ?? $word($key)).'</option>' : '';
    $simple = fn (string $key, array $values) => $current($key)."\n".'<option value=""></option>'."\n"
        .collect($values)->map(fn ($v) => '<option value="'.e($v).'">'.e($v).'</option>')->implode("\n");
    $count = $fields->count();
@endphp
<fieldset>
<legend>スタッフ情報項目</legend>

<table class="tableBox">
@foreach ($fields->values() as $x => $field)
@php($key = $field->tb_koumoku)
@php($name = $field->tb_koumoku_name)
@if (($x + 1) % 5 === 1)
	<tr>
@endif
	<th>
@switch($name)
@case('登録店舗')
@if ($shops->count() > 1)
		<small>{{ $name }}</small><br />
		<select size="1" name="{{ $key }}">
@if ($word($key) !== '' && ($saved = $shops->firstWhere('cd', (int) $word($key))))
		<option value="{{ $saved->cd }}">{{ $saved->kaishamei }}</option>
@endif
		<option value=""></option>
@foreach ($shops as $shop)
		<option value="{{ $shop->cd }}">{{ $shop->kaishamei }}</option>
@endforeach
		</select>
@endif
@break

@case('都道府県')
		<small>{{ $name }}</small><br />
		<select size="1" name="{{ $key }}" class="selectBox">
		{!! $simple($key, $prefs) !!}
		</select>
@break

@case('個人・法人')
		<small>{{ $name }}</small><br />
<select size="1" name="{{ $key }}">
{!! $simple($key, ['個人', '法人']) !!}
</select>
@break

@case('性別')
		<small>{{ $name }}</small><br />
<select size="1" name="{{ $key }}">
{!! $simple($key, ['男性', '女性']) !!}
</select>
@break

@case('生年月日')
<small>{{ $name }}(生まれ月)</small><br />
<select size="1" name="{{ $key }}">
{!! $current($key, str_replace('/', '', $word($key)).'月') !!}
                        <option value=""></option>
@for ($m = 1; $m <= 12; $m++)
                        <option value="/{{ sprintf('%02d', $m) }}/">{{ sprintf('%02d', $m) }}月</option>
@endfor
</select>
@break

@case('血液型')
		<small>{{ $name }}</small><br />
<select size="1" name="{{ $key }}">
{!! $simple($key, ['A', 'B', 'O', 'AB']) !!}
</select>
@break

@case('入社日')
@case('退社日')
		<small>{{ $name }}</small><br />
			<input type="text" name="{{ $key }}_1" value="{{ $word($key.'_1') }}" class="input2S" /> ～ <input type="text" name="{{ $key }}_2" value="{{ $word($key.'_2') }}" class="input2S" />
<br />2009/01/01～2009/12/31
@break

@case('情報変更日')
		<small>{{ $name }}</small><br />
			<input type="text" size="5" name="{{ $key }}_1" value="{{ $word($key.'_1') }}" /> ～ <input type="text" size="5" name="{{ $key }}_2" value="{{ $word($key.'_2') }}" />
<br />2009/01/01～2009/12/31
@break

@case('登録区分')
		<small>{{ $name }}</small><br />
<select size="1" name="{{ $key }}">
{!! $current($key) !!}
                        <option value="登録済">登録済</option>
                        <option value="削除済">削除済</option>
</select>
@break

@case('年齢')
		<small>{{ $name }}</small><br />
			<input type="text" size="5" name="{{ $key }}_1" value="{{ $word($key.'_1') }}" />歳 ～ <input type="text" size="5" name="{{ $key }}_2" value="{{ $word($key.'_2') }}" />歳
@break

@case('所属部署')
	<small>{{ $name }}</small><br />
	<select name="{{ $key }}" id="{{ $key }}" class="selectBox">
	<option value="">▼所属部署選択</option>
@foreach ($belongposts as $post)
	<option value="{{ $post }}" @selected($word($key) === (string) $post)>{{ $post }}</option>
@endforeach
	</select>
@break

@default
		<small>{{ $name }}</small><br />
			<input type="text" name="{{ $key }}" value="{{ $word($key) }}" class="inputM" />
@endswitch
@if (($x + 1) % 5 === 0)
	</th>
	</tr>
@else
	</th>
@if ($x === $count - 1)
	<th colspan="{{ 5 - ($count % 5) }}"></th>
@endif
@endif
@endforeach

</table>
</fieldset>
<!--基本情報table end-->
