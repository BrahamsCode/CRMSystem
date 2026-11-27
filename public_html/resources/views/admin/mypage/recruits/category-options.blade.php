{{-- ajax_category.php: opciones del <select id="boshu_shokushu"> para la tienda elegida. $categories = Collection<RecruitCategory> --}}

<option value="">▼職種選択</option>

@foreach ($categories as $category)
<option value="{{ $category->cd }}">{{ $category->shokushu }}</option>
@endforeach
