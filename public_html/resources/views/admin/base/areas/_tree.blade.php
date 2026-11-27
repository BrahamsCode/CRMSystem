{{-- Árbol dTree de エリア (columna izquierda de area.php / area_edit.php / area_confirm.php). $treeUrl null = sin enlaces --}}
		<div style="width: 30%; float: left;">
			<div class="module-content">
				<script type="text/javascript">
				d = new dTree('d');
				d.config.useCookies = false;
				d.add(0, -1,'エリア',{!! $treeUrl === null ? "'javascript: void(0);'" : "'" . $treeUrl . "','javascript: void(0);'" !!});
				{!! app(\App\Services\Nucleo\AreaTree::class)->dtreeJs($treeUrl, (string) ($treeAreaCd ?? '')) !!}
				document.write(d);
				@if ((int) request('pid') > 0)
				d.openTo({{ (int) request('pid') }}, true);
				@endif
				</script>
				<p style="text-align:center">
					<a href="javascript: d.openAll();">open all</a> | <a href="javascript: d.closeAll();">close all</a>
				</p>
			</div>
		</div>
