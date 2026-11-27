{{--
    Hueco de un .swf (放映素材 de tipo SWFファイル). Flash ya no se reproduce: en lugar del
    <object>/<embed> del legacy se deja un recuadro negro del mismo tamaño que el <embed>
    (300x200) con el nombre del archivo.

    Parámetros:
      $fileName  string  nombre del archivo, p. ej. "20.swf"
--}}
<div class="swfPlaceholder" style="width:300px;height:200px;background-color:#000000;color:#FFFFFF;">{{ $fileName }}</div>
