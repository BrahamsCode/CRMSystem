{{--
    Selector 表示件数 ($kensu_option de system/category7/inc/page_control.php): opciones 10, 20, 30, 40, 50.
    Llama a kensuSet(this), que cada listado define en su sección js y que envía #form_search.

    Parámetros:
      $kensu  int  filas por página actuales (10 por defecto en el legacy)
--}}
<div id="form">
<p class="maxListLength">表示件数<select name="kensu_act" id="kensu_act" class="selectBox" onchange="kensuSet(this)">@for ($kensuNum = 10; $kensuNum <= 50; $kensuNum += 10)<option value="{{ $kensuNum }}" {{ $kensu == $kensuNum ? 'selected' : '' }}>{{ $kensuNum }}</option>
@endfor</select></p>
</div>
