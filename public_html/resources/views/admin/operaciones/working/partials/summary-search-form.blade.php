{{-- Legacy: bloque ■スタッフ検索 de working_list.php / working_total_list.php (form_search).
     Envía a admin.operaciones.working.search ({screen}); kensuSet() del legacy cambia 表示件数 con el mismo formulario.
     Variables: $screen ('monthly' | 'totals'), $kensu, $fields, $words, $shops, $belongposts --}}
<div class="contents">

<p class="h2Title">■スタッフ検索</p>

<form method="post" name="form_search" id="form_search" class="form" action="{{ route('admin.operaciones.working.search', $screen) }}">
@csrf

@include('admin.operaciones.working.partials.summary-staff-search')

<div class="btnBox">
<input type="hidden" name="item_max_no" value="{{ $fields->count() }}" />
<input type="hidden" name="act" value="search" id="act" />
<input type="hidden" name="kensu" id="kensu" value="{{ $kensu }}" />
<input type="hidden" name="se_flg" value="1" />
<input type="submit" name="search_frm" id="search_frm" class="btn" value="この条件で抽出する" />
</div>

</form>

<!-- / .contents --></div>
