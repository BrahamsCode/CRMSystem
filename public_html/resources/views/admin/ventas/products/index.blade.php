{{--
    Legacy: system/category2/item/list.php (商品検索・一覧).
    Variables:
      $paginator  LengthAwarePaginator<SalesItem>  productos visibles (disp_flg = 1), orden cd desc, 10 por página
      $shopNames  Collection<int, string>          kaishamei por cd (tiendas no borradas)
      $images     Collection<int, ?string>         URL de `<cd>_small.jpg` por cd (null sin imagen)
      $query      array                            parámetros GET actuales (para la paginación y el enlace 編集)
--}}
@extends('layouts.legacy')

@section('title', '商品管理 - ' . $siteName)

@section('menu', 'ventas')

@section('css')
<style>
    .itemListTitle {
        font-size: 20px;
    }
</style>
@endsection

@section('js')
<script>
    $(function() {
        // 削除ボタン押下時の処理 (el legacy iba por GET; aquí POST con el token CSRF del layout)
        $('.delete').click(function(){
            if (confirm ( "削除してもよろしいですか？\n\n削除しない場合は['キャンセル']ボタンを押して下さい")) {
                var error = '';
                var index = $('.delete').index(this);
                $.ajax({
                    type     : "POST",
                    url      : "{{ route('legacy.ventas.products.delete') }}",
                    data     : "cd="+$('#cd'+ index).val(),
                    cache    : false,
                    dataType : "json",
                    success:(res)=>{
                        if (res.status == 'success') {
                            location.reload();
                        } else {
                            error = '削除できませんでした。';
                        }
                        return false;
                    },
                    error:()=>{
                        error = '削除できませんでした。';
                    }
                })
                $('.error').empty();
                $('.error').append(error);
            }
        })
    })
</script>
@endsection

@section('content')
@php
    $queryString = $query ? '&' . http_build_query($query) : '';
    // Pagenation::getPagenation(): ?<consulta sin page>&page=N, 3 páginas a cada lado, ＜ ＞
    $baseQuery = http_build_query(\Illuminate\Support\Arr::except($query, ['page']));
    $pageUrl = fn (int $p) => route('admin.ventas.products.index') . '?' . ($baseQuery !== '' ? $baseQuery . '&' : '') . 'page=' . $p;
    $page = $paginator->currentPage();
    $pageMax = $paginator->lastPage();
@endphp
<div class="contents">

    <p class="h2Title">■商品検索</p>
    <div class="error">{{ session('product_error') }}</div>
    <form method="get" name="form_search" id="form_search" class="form" action="{{ route('admin.ventas.products.index') }}#result">
    <fieldset>
        <legend>商品情報項目</legend>
        <table class="tableBox">
            <tbody>
                <tr>
                    <th>
                        <small>商品名</small><br>
                        <input type="text" name="name" value="{{ $query['name'] ?? '' }}" class="inputM">
                    </th>
                    <th>
                        <small>説明</small><br>
                        <input type="text" name="discription" value="{{ $query['discription'] ?? '' }}" class="inputM">
                    </th>
                </tr>
            </tbody>
        </table>
    </fieldset>
    <div class="btnBox">
        <input type="hidden" name="act" value="search">
        <input type="submit" class="btnStyleM" value="検索">
    </div>

    　<a href="{{ route('admin.ventas.products.index') }}">検索条件クリア</a>
    </form>

<!-- / .contents --></div>

<div class="contents" id="result">
<p class="h2Title">■商品一覧</p>
<div class="form">
    <ul>
    @if ($paginator->count() > 0)
        <table class="tableBox2">
            <thead>
                <tr>
                    <th>画像</th>
                    <th>商品名(正式)</th>
                    <th>ポイント交換するか</th>
                    <th>使用可能な店舗</th>
                    <th>表示</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>

        @foreach ($paginator as $key => $value)
                <tr>
                    <td>@if ($images[$value->cd] ?? null)<img src="{{ $images[$value->cd] }}" alt="商品画像{{ $key }}" style="float: left;">@endif</td>
                    <td>{{ $value->name }}</td>
                    <td>{{ $value->is_exchangeable_of_point == 1 ? 'する' : 'しない' }}</td>
                    <td>
                        @foreach ($value->availableShopCds() as $shopCd){{ $shopNames[$shopCd] ?? '' }}<br>@endforeach
                    </td>
                    <td>{{ $value->hyouji_flg == 1 ? 'する' : 'しない' }}</td>
                    <td style="text-align: center;">
                        <a href="{{ route('admin.ventas.products.edit', $value->cd) . ($queryString !== '' ? '?' . ltrim($queryString, '&') : '') }}" class="btn btnStyle" style="width: 50px;">編集</a>
                        <a class="btn btnStyle delete" style="color: red;">削除</a>
                        <input type="hidden" id="cd{{ $key }}" value="{{ $value->cd }}">
                    </td>
                </tr>
        @endforeach
            </tbody>
        </table>
    </ul>

    <ul class="btnBox2" style="clear: both;">
    <ul class="pagination pagination-sm inline">
    @if ($page > 1)<li class="page-item"><a class="page-link" href="{{ $pageUrl($page - 1) }}">＜</a></li>@endif
    @for ($i = 3; $i > 0; $i--)
        @continue($page - $i < 1)
        <li class="page-item"><a class="page-link" href="{{ $pageUrl($page - $i) }}">{{ $page - $i }}</a></li>
    @endfor
    <li class="page-item visit"><a class="page-link">{{ $page }}</a></li>
    @for ($i = 1; $i <= 3; $i++)
        @break($page + $i > $pageMax)
        <li class="page-item"><a class="page-link" href="{{ $pageUrl($page + $i) }}">{{ $page + $i }}</a></li>
    @endfor
    @if ($page < $pageMax)<li class="page-item"><a class="page-link" href="{{ $pageUrl($page + 1) }}">＞</a></li>@endif
    </ul>
    </ul>
    @else
        検索条件に合った商品は、見つかりませんでした。再度検索してください
    @endif

</div>

<!-- / .contents --></div>
@endsection
