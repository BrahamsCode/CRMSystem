{{--
    Respuesta del borrado por AJAX (legacy: system/category7/felica/ajax_del.php). El JS la carga con
    $("#row_tr" + cd).load(...): si se borró, el script quita la fila.
      $deleted  bool
      $cd       string  cd pedido
--}}
@if ($deleted)

<script type="text/javascript">
$(document).ready(function(){

	$('#row_tr{{ $cd }}').remove();
});
</script>
@else
削除失敗しました。
@endif
