{{-- Legacy: formulario oculto mailForm del final de model1/model2/point1/stamp1 (todos los parámetros GET + conditionText
     y otherCondition, que rellena sendMailConfirm() de model1). Variables: $in --}}
<form name="mailForm" id="mailForm" action="{{ route('legacy.clientes.reports.model1-select') }}" method="get" target="_blank">
@foreach ($in as $key => $val)
@if (! is_array($val))
<input type="hidden" name="{{ $key }}" value="{{ $val }}" />
@endif
@endforeach
<input type="hidden" name="conditionText" value="" />
<input type="hidden" name="otherCondition" value="" />
</form>
