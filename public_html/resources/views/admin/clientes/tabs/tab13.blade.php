{{-- Legacy: system/category1/inc/detail_tab13.php (pestaña 紹介実績 de la ficha; parcial sin @extends).
     Variables:
       $referrals  (LengthAwarePaginator, 20 por página por defecto (?kensu): tb_customer con shoukai_tb_customer_cd = cliente,
                    disp_flg '1', touroku_kubun '2', orden touroku_date DESC. Claves: cd, id, kanri_id, name, touroku_date,
                    raiten_count (count(*) de tb_raiten_rireki del referido, disp_flg <> '9'; si el usuario no es master,
                    solo del shop_cd de su sesión, como el legacy))
       $isTablet   (bool: terminal tablet → roid.popUp en lugar de window.open)
     El legacy calcula el paginador pero no imprime $page_control en esta pestaña: aquí tampoco.
     Permiso: $kengen->myKengen[1001][1] o rank master → @can('kengen', [1001, 1]). --}}
@php
    $youbi = ['日', '月', '火', '水', '木', '金', '土'];
@endphp

<div class="tabBody">

<table class="tableBox2">

<tr>
<th style="width: 10em;">紹介日時</th>
<th style="width:  5em;">会員番号</th>
<th style="width: auto;">管理番号</th>
<th style="width: auto;">名前</th>
<th style="width:  5em;">来店回数</th>
<th style="width:  2em;">&nbsp;</th>
</tr>

@forelse ($referrals as $referral)
@php
    $shoukaiDate = ($referral['touroku_date'] ?? '') !== '' && $referral['touroku_date'] !== null
        ? \Illuminate\Support\Carbon::createFromTimestamp((int) $referral['touroku_date'], 'Asia/Tokyo')
        : null;
    // ポップアップで顧客詳細を開く (legacy: /system/category1/inc/customer_detail.php?kaiin_cd=…&wintype=no_headers)
    $shousaiAddress = route('admin.clientes.show', [$referral['cd'], 'kaiin_cd' => $referral['cd'], 'wintype' => 'no_headers']);
@endphp
<tr{!! $loop->index % 2 != 0 ? ' class="tableBg"' : '' !!}>
<td>{{ $shoukaiDate?->format('Y年m月d日') }}({{ $shoukaiDate ? $youbi[$shoukaiDate->dayOfWeek] : '' }}) </td>
<td>{{ $referral['id'] }}</td>
<td>{{ $referral['kanri_id'] }}</td>
<td>{{ $referral['name'] }}</td>
<td>{{ $referral['raiten_count'] }}&nbsp;回</td>
<td>@can('kengen', [1001, 1])@if (! empty($isTablet))<input class="btn btnStyle" type="button" value="詳細" onclick="roid.popUp({{ \Illuminate\Support\Js::from($shousaiAddress) }})" />@else<input class="btn btnStyle" type="button" value="詳細" onclick="javascript:window.open({{ \Illuminate\Support\Js::from($shousaiAddress) }}, 'mywindow2', 'width=970, height=550, menubar=no, toolbar=no, scrollbars=yes');" />@endif @endcan</td>
</tr>

@empty
<tr>
<td colspan="6">紹介実績がありません。</td>
</tr>

@endforelse
</table>

</div>
