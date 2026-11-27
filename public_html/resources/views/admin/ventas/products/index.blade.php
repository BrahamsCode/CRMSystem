{{-- 商品検索・一覧: system/category2/item/list.php --}}
@extends('layouts.legacy')

@section('title', '商品管理 - ' . $siteName)

@section('menu', 'ventas')

@section('js')
<script>
    $(function() {
        // 削除ボタン押下時の処理
        $('.delete').click(function(){
            if (confirm ( "削除してもよろしいですか？\n\n削除しない場合は['キャンセル']ボタンを押して下さい")) {
                var index = $('.delete').index(this);
                // El legacy borraba con GET; aquí POST (con el CSRF del layout) y el error se muestra al recibir la respuesta
                $.ajax({
                    type     : "POST",
                    url      : "/system/category2/item/ajax_list_delete.php?cd="+$('#cd'+ index).val(),
                    cache    : false,
                    dataType : "json",
                    success:(res)=>{
                        if (res.status == 'success') {
                            location.reload();
                        } else {
                            $('.error').empty().append('削除できませんでした。');
                        }
                        return false;
                    },
                    error:()=>{
                        $('.error').empty().append('削除できませんでした。');
                    }
                })
            }
        })
    })
</script>
<style>
    .itemListTitle {
        font-size: 20px;
    }
</style>
@endsection

@section('content')
<div class="contents">

    <p class="h2Title">■商品検索</p>
    <div class="error"></div>
    <form method="get" name="form_search" id="form_search" class="form" action="{{ route('admin.ventas.products.index') }}#result">
    <fieldset>
        <legend>商品情報項目</legend>
        <table class="tableBox">
            <tbody>
                <tr>
                    <th>
                        <small>商品名</small><br>
                        <input type="text" name="name" value="{{ is_string(request('name')) ? request('name') : '' }}" class="inputM">
                    </th>
                    <th>
                        <small>説明</small><br>
                        <input type="text" name="discription" value="{{ is_string(request('discription')) ? request('discription') : '' }}" class="inputM">
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
    @if ($result)
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

        @foreach ($result->data as $key => $value)
                <tr>
                    <td>@if ($small = $images->url($value->cd, '_small'))<img src="{{ $small }}" alt="商品画像{{ $key }}" style="float: left;">@endif</td>
                    <td>{{ $value->name }}</td>
                    <td>{{ $value->is_exchangeable_of_point == 1 ? 'する' : 'しない' }}</td>
                    <td>
                        @foreach ($value->availableShopCds() as $shopCd)
                            @if ($shops->has($shopCd)){{ $shops[$shopCd] }}<br>@endif
                        @endforeach
                    </td>
                    <td>{{ $value->hyouji_flg == 1 ? 'する' : 'しない' }}</td>
                    <td style="text-align: center;">
                        <a href="{{ route('admin.ventas.products.edit', $value->cd) . ($queryString !== '' ? '?' . $queryString : '') }}" class="btn btnStyle" style="width: 50px;">編集</a>
                        <a class="btn btnStyle delete" style="color: red;">削除</a>
                        <input type="hidden" id="cd{{ $key }}" value="{{ $value->cd }}">
                    </td>
                </tr>
        @endforeach
            </tbody>
        </table>
    </ul>

    <ul class="btnBox2" style="clear: both;">
    {!! $pagination !!}
    </ul>
    @else
        検索条件に合った商品は、見つかりませんでした。再度検索してください
    @endif



</div>

<!-- / .contents --></div>
@endsection
