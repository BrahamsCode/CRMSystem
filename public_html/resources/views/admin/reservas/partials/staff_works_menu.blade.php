{{--
    Legacy: system/category9/yoyaku/ajax_menu.php (fragmento que staff_works carga en #menu).
    Variables:
      $reserveFlg ?string    tb_staff.reserve_flg del empleado (指名許諾: '1' = 受ける).
      $menus      Collection ReservationMenu asignables (tb_yoyaku_koumoku_master + category_name, category_hyouji_flg), ya ordenados.
      $works      Collection StaffWork activos del empleado indexados por tb_yoyaku_koumoku_master_cd.
--}}
@php
    $typeFlgNames = ['', 'ベース', 'オプション'];
    $currentTypeFlg = null;
@endphp
@if ($menus->isNotEmpty())
			<table class="tableBox">
				<tr>
					<th>指名許諾</th>
					<td colspan="2">
						<input
							type="radio"
							name="reserve_flg"
							id="reserve_flg1"
							class="checkBtn"
							value="0"
							@if ($reserveFlg != '1') checked="checked"@endif />
							受けない&emsp;&emsp;
						<input
							type="radio"
							name="reserve_flg"
							id="reserve_flg2"
							class="checkBtn"
							value="1"
							@if ($reserveFlg == '1') checked="checked"@endif />
							受ける
					</td>
				</tr>
			</table><br/>
@foreach ($menus as $i => $menu)
@php
    $work = $works->get($menu->cd);
    $bgStyle = $work ? ' style="background:#FFFFCC;"' : (($i % 2 == 1) ? ' class="tableBg"' : '');
    $shoyouTime = $work ? $work->shoyou_time : $menu->shoyou_time;
@endphp
@if ($currentTypeFlg !== (string) $menu->type_flg)
@php $currentTypeFlg = (string) $menu->type_flg; @endphp
@if (! $loop->first)
</table><br/>
@endif
			<table class="tableBox wordBreakAll">
				<tr>
					<th style="">カテゴリ</th>
					<th style="width:6em">{{ $typeFlgNames[(int) $menu->type_flg] ?? '' }}メニュー</th>
					<th style="">目安時間</th>
					<th>登録</th>
					<th>予約非表示</th>
				</tr>
@endif

				{{-- $bgStyle: atributo fijo generado aquí (sin datos del usuario) --}}
				<tr{!! $bgStyle !!}>
					<td>@if (blank($menu->tb_yoyaku_koumoku_category_cd) || $menu->category_name === null)カテゴリ設定なし@else{{ $menu->category_name }}@if ($menu->category_hyouji_flg == '9')<br /><span style="color:red;font-weight:bold;font-size:90%;">※非表示</span>@endif @endif</td>
					<td>
					<span style="font-weight:bold;">{{ $menu->name }}</span>

@if ((string) $menu->tb_shop_cd === '-1')
<small>(全店舗表示メニュー)</small>
@endif

					<div style="padding-left:10px; font-size:90%;">{!! nl2br(e($menu->naiyou)) !!}</div>
					</td>
					<td><input type="text" class="input2S" name="shoyou_time[{{ $menu->cd }}]" value="{{ $shoyouTime }}" />分</td>

					<td style="text-align:center;"><input type="checkbox" name="yoyaku_menu_cd[{{ $menu->cd }}]" value="1"@if ($work) checked @endif /></td>
					<td style="text-align:center;"><input type="checkbox" name="mypage_flg[{{ $menu->cd }}]" value="1"@if ($work?->mypage_flg == '1') checked @endif /></td>
				</tr>

@if ($loop->last)
</table>
@endif
@endforeach

		<div class="btnBox">
		<input type="hidden" name="act" id="act" value="reg" />
		<input type="submit" name="enter" id="enter" class="btn" value="設定" />
		</div>

@else
<p>メニューデータはありません</p>
@endif
