{{--
    Resultado del 会員検索 del colorbox de カード管理 (legacy: system/category7/felica/ajax_search_kaiin.php).
      $members  Collection<Customer>|null  socios 本登録 de la tienda (máx. 200), con kaishamei; null sin tienda
    Al hacer clic llama a setMember(cd, '【id】  name') de cards.blade.php (texto pasado con Js::from).
--}}
@php($max = $members?->count())
<p><span class="attention">{{ $max }}件</span>ヒット（MAX200件まで）</p>

<table class="tableBox2" style="width:95%;overflow:auto;table-layout:fixed;">
@if ($max)
<tr style="line-height:15px;">
<th width="15%" style="line-height:15px;">ID</th>
<th width="30%" style="line-height:15px;">店舗</th>
<th width="40%" style="line-height:15px;">名前</th>
<th width="15%" style="line-height:15px;">生年月日</th>
</tr>
@foreach ($members as $member)
<tr style="line-height:15px;cursor:pointer;" onclick="setMember({{ (int) $member->cd }}, {{ \Illuminate\Support\Js::from('【' . $member->id . '】  ' . $member->name) }});">
<td style="line-height:15px;">{{ $member->id }}</td>
<td style="line-height:15px;">{{ $member->kaishamei }}</td>
<td style="line-height:15px;">{{ $member->name }}</td>
<td style="line-height:15px;">{{ $member->kojin_birth }}</td>
</tr>
@endforeach
@else
<p>データが見つかりませんでした</p>
@endif
</table>
