{{--
    Legacy: system/category2/item/edit.php (商品新規登録 / 商品編集).
    Variables:
      $item              SalesItem          producto (nuevo sin guardar en el alta)
      $shops             Collection<Shop>   tiendas no borradas (cd, kaishamei), orden cd
      $availableShopCds  list<int>          tiendas marcadas (available_shop_cds)
      $imageUrl          string             `/hp/uploads/c2/item/<cd>/<cd>_big.jpg?t=` (o `<cd>.jpg`), '' sin imagen
      $backQuery         array              name/discription de la búsqueda para 一覧に戻る ([] = sin consulta)
    Editor: CKEditor 4 del legacy (public/legacy/common/ckeditor), sobre los mismos id/name. El HTML se guarda tal cual
    (sin purificador en el núcleo).
--}}
@extends('layouts.legacy')

@section('title', '商品管理 - ' . $siteName)

@section('menu', 'ventas')

@section('js')
<script type="text/javascript" src="{{ asset('legacy/common/ckeditor/ckeditor.js') }}"></script>
<script>
$(function() {
    // エディタへ値を設定する
    CKEDITOR.on('instanceReady',function(e){
    // 代入処理
    CKEDITOR.instances.discription.setData($('.discription').val());
    CKEDITOR.instances.discription_detail.setData($('.discription_detail').val());
    });

    // エディタへの設定を適用する
    CKEDITOR.replace('discription', {
      uiColor: '#EEEEEE',
      height: 200,
    });
    CKEDITOR.replace('discription_detail', {
      uiColor: '#EEEEEE',
      height: 200,
    });
    $('#edit').click(()=>{
        $('.discription').text(CKEDITOR.instances.discription.getData());
        $('.discription_detail').text(CKEDITOR.instances.discription_detail.getData());
        if (!$('#preview').attr('src')) {
            $('#form_editor').append('<input type="hidden" name="img_delete" value="1">');
        }

        // バリデーション
        $form_obj  = $('#form_editor');
        var error = '';
		$.ajax({
			type     : "GET",
			url      : "{{ route('legacy.ventas.products.validate') }}",
            data     : $('#form_editor').serialize(),
			cache    : false,
            processData : false,
            contentType : false,
            dataType : "json",
            success:(res)=>{
                if (res.status == 'success') {
                    $('#form_editor').submit();
                } else {
                    $.each(res.errorMsg ,(idnex,element)=>{
                        $.each(element,(i, elem) =>{
                            error += elem+'<br>';
                        })
                    })
                    $('.error').empty();
                    $('.error').append(error);
                    $(window).scrollTop(0);
                }
                return false;
            },
            error:()=>{

            }
        })
    })
    $('#clear').click(()=>{
        $('#myImage').val('');
        $('#preview').attr('src','');
    })
    $('#myImage').change((e)=>{
        var reader = new FileReader();
        reader.onload = function (e) {
            $("#preview").attr('src', e.target.result);
        }
        reader.readAsDataURL(e.target.files[0]);
    });
})
</script>
@endsection

@section('content')
@php
    $title = $item->exists ? '編集' : '新規登録';
    $hyoujiFlg = (int) old('hyouji_flg', $item->hyouji_flg ?? 1);
    $exchangeable = (int) old('is_exchangeable_of_point', $item->is_exchangeable_of_point ?? 0);
    $checkedShops = array_map('intval', (array) old('available_shop_cds', $availableShopCds));
@endphp
<div class="contents">

    <p class="h2Title">
        ■商品{{ $title }}
    </p>
    <div class="error">{{ session('product_error') }}@foreach ($errors->all() as $message){{ $message }}<br>@endforeach</div>

    <form method="post" id="form_editor" action="{{ $item->exists ? route('admin.ventas.products.update', $item->cd) : route('admin.ventas.products.store') }}" enctype="multipart/form-data">
        @csrf
        <table class="tableBox">
            <colgroup>
                <col style="width:250px;">
                <col style="width: auto;">
            </colgroup>
            <tbody>
                <tr>
                    <th>
                        商品名(正式)
                    </th>
                    <td>
                        <input type="text" name="name" value="{{ old('name', $item->name) }}">
                    </td>
                </tr>
                <tr>
                    <th>
                        商品名(表示用)
                    </th>
                    <td>
                        <input type="text" name="display_name" value="{{ old('display_name', $item->display_name) }}">
                    </td>
                </tr>
                <tr>
                    <th>
                        表示見出し
                    </th>
                    <td>
                        <input type="text" name="display_title" value="{{ old('display_title', $item->display_title) }}">
                    </td>
                </tr>
                <tr>
                    <th>
                        一覧用説明(HTML可)
                    </th>
                    <td>
                        <div id="discription"></div>
                        <textarea class='discription' name="discription" style="display: none;">{{ old('discription', $item->discription) }}</textarea>
                    </td>
                </tr>
                <tr>
                    <th>
                        詳細用説明(HTML可)
                    </th>
                    <td>
                        <div id="discription_detail"></div>
                        <textarea class='discription_detail' name="discription_detail" style="display: none;">{{ old('discription_detail', $item->discription_detail) }}</textarea>
                    </td>
                </tr>
                <tr>
                    <th>
                        ポイントと交換するかどうか
                    </th>
                    <td>
                        <label for="is_exchangeable_of_point1">
                            <input type="radio" name="is_exchangeable_of_point" id="is_exchangeable_of_point1" value="0" {{ $exchangeable === 0 ? 'checked' : '' }}>しない
                        </label>
                        <label for="is_exchangeable_of_point2">
                            <input type="radio" name="is_exchangeable_of_point" id="is_exchangeable_of_point2" value="1" {{ $exchangeable === 1 ? 'checked' : '' }}>する
                        </label>
                    </td>
                </tr>
                <tr>
                    <th>
                        ポイント交換数
                    </th>
                    <td>
                        <input type="text" name="number_of_points_exchanged" value="{{ old('number_of_points_exchanged', $item->number_of_points_exchanged) }}">
                    </td>
                </tr>
                <tr>
                    <th>
                        使用可能な店舗
                    </th>
                    <td>
                        @foreach ($shops as $key => $shop)
                            <br>
                            <input type="checkbox" name="available_shop_cds[]" id="available_shop_cds{{ $key }}" {{ in_array((int) $shop->cd, $checkedShops, true) ? 'checked' : '' }} value="{{ $shop->cd }}">
                            <label for="available_shop_cds{{ $key }}">{{ $shop->kaishamei }}</label>
                        @endforeach
                    </td>
                </tr>
                <tr>
                    <th>画像</th>
                    <td>
                        <input type="file" name="myImage" id="myImage" accept="image/*"><br>
                        <img id="preview" src="{{ $imageUrl }}" width="320">
                        <input type="button" id="clear" value="画像削除">
                    </td>
                </tr>
                <tr>
                    <th>
                        表示
                    </th>
                    <td>
                        @foreach ([0 => 'しない', 1 => 'する'] as $key => $label)<label for="hyouji_flg{{ $key }}"><input type="radio" name="hyouji_flg" id="hyouji_flg{{ $key }}" value="{{ $key }}" {{ $hyoujiFlg === $key ? 'checked' : '' }}>{{ $label }}</label>@endforeach
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="btnBox">
            <a id="back" href="{{ route('admin.ventas.products.index', $backQuery) }}" class="btn btnStyle" style="float: left;">一覧に戻る</a>
            <a id="edit" class="btn btnStyle">登録</a>
            <input type="hidden" name="act" value="{{ $item->exists ? 'register' : 'add' }}">
            <input type="hidden" name="cd" value="{{ $item->cd }}">
        </div>
    </form>

<!-- / .contents --></div>
@endsection
