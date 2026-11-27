{{-- Legacy: enlaces 男女別 / 年代別 / 職業別 de cada bloque de model1/model2/point1/stamp1 (?cond=N&<campo>=<valor> + filtros).
     Solo si no se está ya filtrando por sex/age/job; se omite el cruce actual.
     Variables: $in, $meta (CustomerReportService::applyVisitFilters), $block (value), $selfUrl --}}
@if (! array_key_exists('sex', $in) && ! array_key_exists('age', $in) && ! array_key_exists('job', $in))
@foreach (\App\Services\Customers\Reports\CustomerReportService::TYPES as $i => $type)
@continue((string) ($in['cond'] ?? '') !== '' && (int) $in['cond'] === $i)
<span style="font-size: 140%;font-weight: bold;"><a href="{{ $selfUrl }}?cond={{ $i }}{{ $meta['field'] !== '' ? '&' . $meta['field'] . '=' . rawurlencode($block['value']) : '' }}{{ $meta['addRirekiStr'] }}">{{ $type }}別</a></span>&emsp;&emsp;
@endforeach
@endif
