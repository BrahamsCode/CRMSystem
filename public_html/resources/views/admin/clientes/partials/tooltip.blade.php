{{-- Legacy: system/inc/tooltip.php + system/inc/tooltip_define.php (textos de ayuda).
     Variables: $n (int, índice de $tooltip_sentence del legacy). --}}
@php
    // Textos copiados de tooltip_define.php (solo los índices que usan las pantallas de clientes).
    // Contienen <br /> propios del sistema: el legacy los imprimía sin escapar.
    $tooltipSentence = [
        0 => '※ 入力内容は半角数字とし、ハイフン(-)で連結せずに入力してください。',
        1 => '※ 月日は2桁で入力し、入力項目はスラッシュ(/)で連結して入力して下さい。<br />例) 2009/04/18',
        2 => 'ハイフン(-)で連結して入力してください。<br />例) 3-5-3',
        3 => '※ 例) 総合太郎',
        4 => '※ 全角カタカナで入力してください。<br />例) ソウゴウタロウ',
        5 => '※ 例) 株式会社総合業務管理システム',
        6 => '※ 入力内容は法人格を省いて入力してください。<br />例) ソウゴウカンリシステム',
        7 => '※ 例1) 株式会社総合業務管理システム<br />例2) 総合太郎',
        8 => '※ 入力内容は法人格を省いて、全角カナで入力してください。<br />例1) ソウゴウカンリシステム<br />例2) ソウゴウタロウ',
        9 => '例) 千代田区 千代田',
        10 => '例) 総合管理ビルディング3階D号',
        11 => '例) ソウゴウカンリビルディング3カイDゴウ',
        12 => '※ 登記されていない団体の意場合は不要<br />月日は2桁で入力し、入力項目はスラッシュ(/)で連結して入力して下さい。<br />例) 1985/09/21',
        13 => '※ 登録する画像は、jpg,gif,png形式で登録してください。ファイルサイズは2M未満でご登録ください。',
        40 => '例) 千代田区 千代田',
        41 => '例) チヨダク チヨダ',
        50 => '※ セレクトボックスに該当部署がない場合は、何も選択せずにフリースペースにご記入ください。',
        100 => '※ 半角英数のみで入力してください。',
    ];
@endphp
<dl class="toolTip">
<dt class="toolTipFig"><img src="{{ asset('legacy/system/img/icon_help.gif') }}" /></dt>
<dd class="toolTipBody" style="background:#FFFFCF;">{!! $tooltipSentence[$n] ?? '' !!}</dd>
</dl>
