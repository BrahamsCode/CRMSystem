{{-- Legacy: system/category1/inc/detail_tab14.php (pestaña 有料会員状況 de la ficha; parcial sin @extends).
     Los datos de la API MobileShop (CustomerForMobileShop::fetchPaidMemberFlg) los obtiene el controlador (MobileShopClient).
     Variables:
       $customer               (modelo tb_customer: cd, paid_member_flg)
       $paidMemberCancellation (bool: la API devuelve can_ymd no vacío → 月末解約予定)
     JS: jConfirm/jAlert (jquery.alerts.js) que carga la ficha. La URL del fetch es la del legacy
     (/system/category1/inc/ajax_changePaidMemberFlg.php, ruta legacy.clientes.*); en el legacy era relativa ('./'). --}}
@php
    $currentIsPaidMember = (int) $customer->paid_member_flg;
@endphp

<div class="tabBody">
    <div>
        <p class="textCenter" style="margin: 20px;">
            現在:@if ($paidMemberCancellation)<span style="font-size: 24px; color: red">有料会員ですが月末解約予定です</span>@elseif ($currentIsPaidMember === 2 || $currentIsPaidMember === 3)<span style="font-size: 24px; color: blue">有料会員</span>@else<span style="font-size: 24px;">無料会員</span>@endif
        </p>
        @if ($paidMemberCancellation)
            <div class="right" style="margin: 10px;">
                現在解約予定です
            </div>
        @elseif ($currentIsPaidMember === 2 || $currentIsPaidMember === 3)
            <div class="right" style="margin: 10px;">
                この会員を解約処理して無料会員に、
                <input type="submit" id="paidMemberSendBtn" value="変更する" style="color: red;">
            </div>
        @endif
    </div>
    <table class="tableBox2">
        <thead>
            <tr>
                <th style="width: 10em;">日時</th>
                <th style="width: auto;">処理内容</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>

</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var customerCd = "{{ $customer->cd }}";
        var isPaidMemberCurrent = "{{ $currentIsPaidMember }}";
        var paidMemberSendBtn = document.querySelector('#paidMemberSendBtn');
        // Laravel: el botón solo existe para 有料会員; el legacy lanzaba un error de JS con 無料会員
        if (! paidMemberSendBtn) {
            return;
        }

        paidMemberSendBtn.addEventListener("click", function(){

            jConfirm( "解約処理してもよろしいですか？", "会員状況変更処理", function(ret){
                var postdata = {
                    "cd": customerCd,
                    // Laravel: token CSRF para el POST (el legacy no lo tenía)
                    "_token": "{{ csrf_token() }}"
                };

                if (ret) {
                    fetch('/system/category1/inc/ajax_changePaidMemberFlg.php', {
                        method: "POST",
                        cache: "no-cache",
                        body: Object.keys(postdata).reduce((o,key)=>(o.set(key, postdata[key]), o), new FormData())
                    })
                        .then(function(response) {
                            return response.json();
                        })
                        .then(function(myJson) {
                            if (myJson.status != 'ok' && myJson.message != '') {
                                jAlert(myJson.message);
                            } else if (myJson.status == 'ok') {
                                location.reload();
                            }
                        });
                }

            });
            return false;
        });
    });
</script>
