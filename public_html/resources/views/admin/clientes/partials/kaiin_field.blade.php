{{-- Legacy: system/class/cKengen.php → creatKaiinForm() (campo del cliente según permisos).
     Variables:
       $kengen  (int) nº de tb_kengen_contents del campo (myKengen[N])
       $upType  (int) 2 = alta, 3 = edición (koumoku_up_flg)
       $type    'text' | 'select' | 'radio'
       $name    name/id del campo (en radio: name del grupo)
       $value   valor (text) / valor del radio (radio)
       $class   clase CSS (opcional)
       $style   estilo en línea (text/radio; opcional)
       $other   atributos extra generados por el sistema, p. ej. ' size="4" maxlength="4"' (text; opcional)
       $idSuffix  sufijo del id del radio (radio)
       $checked   bool, radio marcado (radio)
       $names / $values  arrays de etiquetas y valores de las opciones (select)
       $selected  valor seleccionado (select)
     Permisos: Gate 'kengen' con [N, función] (1 閲覧, 2 新規, 3 編集); master siempre pasa. --}}
@php
    $canView = \Illuminate\Support\Facades\Gate::allows('kengen', [$kengen, 1]);
    $canEdit = \Illuminate\Support\Facades\Gate::allows('kengen', [$kengen, $upType]);
    $class = $class ?? '';
    $style = $style ?? '';
    $other = $other ?? '';
@endphp
@switch($type)
@case('text')
@if (! $canView)******@endif<input type="{{ $canView ? 'text' : 'hidden' }}" name="{{ $name }}" id="{{ $name }}" value="{{ $value }}" class="{{ $class }}"@if (! $canEdit) readonly="readonly"@endif @if ($style !== '')style="{{ $style }}"@endif{!! $other !!} />
@break
@case('select')
@if (! $canView)<input type="hidden" name="{{ $name }}" value="{{ $selected }}" />*****@else<select name="{{ $name }}" id="{{ $name }}"@if (! $canEdit) disabled @endif>@foreach ($values as $i => $optionValue)<option value="{{ $optionValue }}"@if ((string) $optionValue === (string) $selected) selected="selected"@endif>{{ $names[$i] ?? '' }}</option>@endforeach</select>@if (! $canEdit)<input type="hidden" name="{{ $name }}" value="{{ $selected }}" />@endif @endif
@break
@case('radio')
@if (! $canView)@if ($checked)<input type="hidden" name="{{ $name }}" value="{{ $value }}" />@endif*****@else<input type="radio" name="{{ $name }}" id="{{ $name }}{{ $idSuffix }}" value="{{ $value }}" class="{{ $class }}"@if (! $canEdit) disabled @endif @if ($style !== '')style="{{ $style }}"@endif @if ($checked) checked="checked"@endif /><label for="{{ $name }}{{ $idSuffix }}">{{ $value }}</label>@if (! $canEdit && $checked)<input type="hidden" name="{{ $name }}" value="{{ $value }}" />@endif @endif
@break
@endswitch
