{{-- Barra 男性/女性/不明 y su leyenda (customer_sumup_list.php). Variables: $stats (breakdown()), $small (bool: versión _s de las celdas) --}}
@php
    $hide = ' font-size: 0; text-indent: -9999px;';
    $s = ! empty($small) ? '_s' : '';
@endphp
<ul id="graphField1" class="perGraphField{{ $s }}"><!--
--><li style="width: {{ $stats['male_pct'] }}%; background: #8EBAF9;{{ $stats['male_pct'] == 0 ? $hide : '' }}"><span>{{ $stats['male_pct'] }}%</span></li><!--
--><li style="width: {{ $stats['female_pct'] }}%; background: #F98E8E;{{ $stats['female_pct'] == 0 ? $hide : '' }}"><span>{{ $stats['female_pct'] }}%</span></li><!--
--><li style="width: {{ $stats['unknown_pct'] }}%; background: #CCCCCC;{{ $stats['unknown_pct'] < 1 ? $hide : '' }}"><span>{{ $stats['unknown_pct'] }}%</span></li><!--
--></ul>


<ul class="colorExplainField{{ $s }}" style="margin-top:3px">
<li class="colorExplain{{ $s }}"><span style="background: #8EBAF9;">&nbsp;</span>男性<small>[{{ $stats['male'] }}人]</small></li>@if($s)

@endif<li class="colorExplain{{ $s }}"><span style="background: #F98E8E;">&nbsp;</span>女性<small>[{{ $stats['female'] }}人]</small></li>@if($s)

@endif<li class="colorExplain{{ $s }}"><span style="background: #CCCCCC;">&nbsp;</span>不明<small>[{{ $stats['unknown'] }}人]</small></li>
</ul>
