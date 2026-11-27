{{-- Contenido de una celda (año, prefectura u ocupación). Variables: $stats, $totalLabel ('総数' o '■計'), $maleColors, $femaleColors --}}
<p class="h4Title">{{ $totalLabel }}：<span>{{ $stats['all'] }}</span> 人</p>

@include('admin.clientes.statistics.partials.sex-bar', ['stats' => $stats, 'small' => true])

<p class="h4Title">■男性：<span>{{ $stats['male'] }}</span> 人</p>

@include('admin.clientes.statistics.partials.age-list-small', ['ages' => $stats['male_ages'], 'colors' => $maleColors])

<p class="h4Title">■女性：<span>{{ $stats['female'] }}</span> 人</p>

@include('admin.clientes.statistics.partials.age-list-small', ['ages' => $stats['female_ages'], 'colors' => $femaleColors])
