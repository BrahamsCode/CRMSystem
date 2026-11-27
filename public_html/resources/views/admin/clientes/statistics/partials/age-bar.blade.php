{{-- Barra por décadas (perGraphField) y su leyenda. Variables: $ages (breakdown()['male_ages'|'female_ages']), $barColors y $legendColors (8 colores, 10代…80代) --}}
@php($hide = ' font-size: 0; text-indent: -9999px;')
<ul id="graphField1" class="perGraphField"><!--
@foreach (\App\Services\Customers\Reports\CustomerStatisticsService::AGES as $i => $age)
--><li style="width: {{ $ages['ages'][$age]['pct'] }}%; background: {{ $barColors[$i] }};{{ $ages['ages'][$age]['pct'] == 0 ? $hide : '' }}"><span>{{ $age }}代 {{ $ages['ages'][$age]['pct'] }}%</span></li><!--
@endforeach
--><li style="width: {{ $ages['other_pct'] }}%; background: #CCCCCC;{{ $ages['other_pct'] == 0 ? $hide : '' }}"><span>{{ $ages['other_pct'] }}%</span></li><!--
--></ul>


<ul class="colorExplainField" style="margin-top:3px">
@foreach (\App\Services\Customers\Reports\CustomerStatisticsService::AGES as $i => $age)
<li class="colorExplain"><span style="background: {{ $legendColors[$i] }};">&nbsp;</span>{{ $age }}代<small>[{{ $ages['ages'][$age]['count'] }}人]</small></li>
@endforeach
<li class="colorExplain"><span style="background: #CCCCCC;">&nbsp;</span>その他 {{ $ages['other_pct'] }}% <small>[{{ $ages['other'] }}人]</small></li>

</ul>
