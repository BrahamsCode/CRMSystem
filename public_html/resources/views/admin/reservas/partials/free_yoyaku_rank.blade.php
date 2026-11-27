{{--
    Legacy: system/category9/yoyaku/ajax_free_yoyaku_rank.php (fragmento que free_yoyaku carga en .tableDisp).
    Variables:
      $shopCd  int        tienda.
      $ranking Collection objetos {cd (tb_staff.cd), name, rank (send_vl del legacy), prev_cd (empleado de la fila anterior)}.
--}}
@php
    $rankStyle = 'text-align: center;line-height: 1em;cursor: pointer;';
@endphp
@if ($ranking->isNotEmpty())<h3>フリー予約の割り当て優先順位</h3>※上にあるスタッフが優先してフリー予約に割り当てられます。<table class="tableBox8"><tr><th style="width:5em;">優先順位</th><th style="width:auto;">スタッフ名</th><th style="width:3.5em;"></th></tr>@foreach ($ranking as $row)<tr><td>{{ $loop->iteration }}</td><td>{{ $row->name }}</td><td><div style="{{ $rankStyle }}" onclick="statusControl('{{ $shopCd }}', 'rankup', '{{ $row->cd }}', '{{ $row->prev_cd }}', '{{ $row->rank }}')">▲</div><div style="{{ $rankStyle }}" onclick="statusControl('{{ $shopCd }}', 'rankdown', '{{ $row->cd }}', '{{ $row->prev_cd }}', '{{ $row->rank }}')">▼</div></td></tr>@endforeach</table>@endif
