<?php

use App\Support\LegacyCheck;
use App\Support\LegacyText;

// LegacyText (get_form / get_form2) y LegacyCheck (error_check.php)

it('limpia como get_form(): recorta espacios y cambia comillas y barra', function () {
    expect(LegacyText::form("　 A&B \"x\" 'y' \\z 　"))->toBe('A&B x ’y’ ￥z')
        ->and(LegacyText::form(['a']))->toBe('')
        ->and(LegacyText::form(null))->toBe('');
});

it('limpia como get_form2(): recorta y escapa HTML con comillas dobles', function () {
    expect(LegacyText::input(" <b>A&B</b> \"x\" 'y' "))->toBe('&lt;b&gt;A&amp;B&lt;/b&gt; &quot;x&quot; \'y\'')
        ->and(LegacyText::inputs(['a' => ' x '], ['a', 'b']))->toBe(['a' => 'x', 'b' => '']);
});

it('valida con las reglas y mensajes de error_check.php', function () {
    $check = (new LegacyCheck)
        ->blank('kaishamei', '　 ')
        ->katakana('kana', 'カタカナ（ア）')
        ->katakana('kana_ok', 'カタカナ 123-()')
        ->num('tel', '03-1234')
        ->zip('zip', '123', '郵便番号')
        ->mailRequired('email', 'mal@')
        ->url('url', 'http://example.com/a?b=1')
        ->mail('mail_vacio', '');

    expect($check->fails())->toBeTrue()
        ->and($check->ng('kaishamei'))->toBe(' class="Ng"')
        ->and($check->messages('kaishamei'))->toBe('<p class="m0">※必須入力項目です。</p>')
        ->and($check->ng('kana'))->toBe(' class="Ng"')
        ->and($check->ng('kana_ok'))->toBe('')
        ->and($check->messages('tel'))->toBe('<p class="m0">※半角数字で入力して下さい。</p>')
        ->and($check->messages('zip'))->toBe('※郵便番号は半角数字7文字で入力して下さい。<br />')
        ->and($check->messages('email'))->toBe('<p class="m0">※メールアドレスが不適切な形式です。</p>')
        ->and($check->ng('url'))->toBe('')
        ->and($check->ng('mail_vacio'))->toBe('');

    expect((new LegacyCheck)->zip('zip', '1234567')->fails())->toBeFalse()
        ->and((new LegacyCheck)->mailRequired('m', '')->messages('m'))->toBe('<p class="m0">※メールアドレスは必須入力項目です。</p>')
        ->and(LegacyCheck::replaceTel('03-1234/56*7ー8'))->toBe('0312345678')
        ->and(LegacyCheck::replaceTel(''))->toBeNull();
});
