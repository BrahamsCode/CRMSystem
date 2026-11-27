{{--
	Bloques del buscador de clientes en 発行条件選択 (mismo orden que inc/mail_customer_select_frm.php del legacy):
	search_set1, カスタマイズ検索, search_rireki_set1, search_kaikei_set1, search_method_set1 (parciales de S2).
	search_pos_kaikei_set1: POS fuera de alcance.
	Variables: las de CustomerSearchForm::viewData() de S2.
--}}
@include('admin.clientes.partials.search_set1')
@include('admin.clientes.partials.search_custom_set1')
@include('admin.clientes.partials.search_rireki_set1')
@include('admin.clientes.partials.search_kaikei_set1')
@include('admin.clientes.partials.search_method_set1')
