{{-- 商品新規登録 / 編集: system/category2/item/edit.php --}}
@extends('layouts.legacy')

@section('title', '商品管理 - ' . $siteName)

@section('menu', 'ventas')

@section('js')
{{-- CKEditor del legacy (Base::createHead lo cargaba en todas las pantallas; el layout nuevo no) --}}
<script type="text/javascript" src="/common/ckeditor/ckeditor.js"></script>
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
        $('.discription').val(CKEDITOR.instances.discription.getData());
        $('.discription_detail').val(CKEDITOR.instances.discription_detail.getData());
        if (!$('#preview').attr('src')) {
            $('#form_editor').append('<input type="hidden" name="img_delete" value="1">');
        }

        // バリデーション (ajax_edit.php). El legacy la mandaba por GET con los 説明 en la URL (límite de longitud): aquí POST
        var error = '';
		$.ajax({
			type     : "POST",
			url      : "/system/category2/item/ajax_edit.php",
            data     : $('#form_editor').serialize(),
			cache    : false,
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

@php
    $errorText = (string) session('error');
    if ($errors->any()) {
        $errorText .= implode('<br>', array_map('e', $errors->all()));
    }
    $checkedShops = array_map('intval', (array) old('available_shop_cds', $item->availableShopCds()));
    $exchangeable = old('is_exchangeable_of_point', $item->is_exchangeable_of_point);
    // 表示: nuevo = する
    $hyoujiFlg = old('hyouji_flg', $item->hyouji_flg);
    $hyoujiFlg = $hyoujiFlg === null ? 1 : (int) $hyoujiFlg;
@endphp

@section('content')
<div class="contents">

    <p class="h2Title">
        ■商品{{ $title }}
    </p>
    <div class="error">{!! $errorText !!}</div>

    <form method="post" id="form_editor" action="{{ $formAction }}" enctype="multipart/form-data">
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
                            <input type="radio" name="is_exchangeable_of_point" id="is_exchangeable_of_point1" value="0" {{ $exchangeable == 0 ? 'checked' : '' }}>しない
                        </label>
                        <label for="is_exchangeable_of_point2">
                            <input type="radio" name="is_exchangeable_of_point" id="is_exchangeable_of_point2" value="1" {{ $exchangeable == 1 ? 'checked' : '' }}>する
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
                        @foreach ($shops->keys()->values() as $key => $shopCd)
                            <br>
                            <input type="checkbox" name="available_shop_cds[]" id="available_shop_cds{{ $key }}" {{ in_array((int) $shopCd, $checkedShops, true) ? 'checked' : '' }} value="{{ $shopCd }}">
                            <label for="available_shop_cds{{ $key }}">{{ $shops[$shopCd] }}</label>
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
            <a id="back" href="{{ $backUrl }}" class="btn btnStyle" style="float: left;">一覧に戻る</a>
            <a id="edit" class="btn btnStyle">登録</a>
            <input type="hidden" name="act" value="{{ $act }}">
            <input type="hidden" name="cd" value="{{ $item->cd }}">
        </div>
    </form>

<!-- / .contents --></div>
@endsection
