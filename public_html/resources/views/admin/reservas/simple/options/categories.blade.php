{{-- Legacy: system/category3/reservation/ajax_category.php (<option> de 予約内容 para jQuery .load).
     Variables: $categories Collection (cd, reserve_cate_name con entidades HTML: {!! !!}) --}}

<option value="">選択して下さい</option>

@foreach ($categories as $category)
<option value="{{ $category->cd }}">{!! $category->reserve_cate_name !!}</option>
@endforeach
