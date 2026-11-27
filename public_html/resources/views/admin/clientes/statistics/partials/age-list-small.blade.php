{{-- Leyenda por décadas de las celdas (colorExplain_s: el ancho en px es el porcentaje, como el legacy). Variables: $ages, $colors (8 colores) --}}
@php($hide = ' font-size: 0; text-indent: -9999px;')
<ul class="colorExplainField_s" style="margin-top:3px">
@foreach (\App\Services\Customers\Reports\CustomerStatisticsService::AGES as $i => $age)
<li class="colorExplain_s"><span style="background: {{ $colors[$i] }};width: {{ $ages['ages'][$age]['pct'] }}px;{{ $ages['ages'][$age]['pct'] == 0 ? $hide : '' }}">&nbsp;</span>{{ $age }}代 {{ $ages['ages'][$age]['pct'] }}% <small>[{{ $ages['ages'][$age]['count'] }}人]</small></li>
@endforeach
<li class="colorExplain_s"><span style="background: #CCCCCC;width: {{ $ages['other_pct'] }}px;{{ $ages['other_pct'] == 0 ? $hide : '' }}">&nbsp;</span>その他 {{ $ages['other_pct'] }}% <small>[{{ $ages['other'] }}人]</small></li>
</ul>
