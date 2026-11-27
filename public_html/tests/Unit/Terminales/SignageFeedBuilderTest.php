<?php

use App\Services\Terminales\SignageFeedBuilder;

/*
 * Forma de los XML que lee el reproductor, comparada con la salida de
 * category7/digital_signage/xml_up.php y xml_info_up.php (espacios dobles incluidos).
 * Sin BD: se pasan filas como las de tb_screensaver / tb_recruit.
 */

it('arma la línea <p> de un material igual que xml_up.php', function () {
    $line = (new SignageFeedBuilder)->materialLine([
        'cd' => 12,
        'title' => '本日のおすすめ',
        'play_frame_sec' => '15',
        'filetype' => '画像ファイル',
        'pic_extension' => 'jpg<>',
        'pic_flg' => '1<>',
    ]);

    expect($line)->toBe(
        '<p cd="12" time="15"  title="本日のおすすめ"  type="画像ファイル" pic_extension="jpg," pic_flg="1,">本日のおすすめ</p>'."\n"
    );
});

it('escapa los caracteres que rompían el XML del legacy', function () {
    $line = (new SignageFeedBuilder)->materialLine([
        'cd' => 1, 'title' => 'A&B "特売"', 'play_frame_sec' => '5', 'filetype' => 'FLVファイル',
        'pic_extension' => null, 'pic_flg' => null,
    ]);

    expect($line)->toContain('title="A&amp;B &quot;特売&quot;"')
        ->and($line)->toContain('type="FLVファイル" pic_extension="" pic_flg=""');
});

it('arma floormap.xml con planta, tienda, ficha y 求人', function () {
    $xml = (new SignageFeedBuilder)->renderFloormap([
        '1F' => [[
            'cd' => 3,
            'cate' => '2',
            'name' => 'Polos',
            'kana' => 'ポロス',
            'details' => [['営業時間', '10:00-20:00'], ['定休日', '']],
            'recruit' => [
                'cd' => 7,
                'boshu_headline' => 'スタッフ募集',
                'boshu_shokushu_name' => 'ホールスタッフ',
                'shigoto_naiyou' => '接客',
                'koyou_keitai' => 'アルバイト',
                'oubo_shikaku' => '未経験歓迎',
                'kyuyo' => '時給1200円',
            ],
        ]],
    ]);

    expect($xml)->toBe(
        "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
        ."<data>\n"
        ."<map floor=\"1F\">\n"
        ."<shop cd=\"3\" cate=\"2\" name=\"Polos\" kana=\"ポロス\">\n"
        ."<detail>【営業時間】\n10:00-20:00\n\n</detail>\n"
        ."<recruit cd=\"7\">【募集見出し】\nスタッフ募集\n"
        ."【募集職種】\nホールスタッフ\n\n"
        ."【仕事内容】\n接客\n\n"
        ."【雇用形態】\nアルバイト\n\n"
        ."【応募資格】\n未経験歓迎\n\n"
        ."【給与】\n時給1200円\n\n"
        ."</recruit>\n"
        ."</shop>\n"
        ."</map>\n"
        ."</data>\n"
    );
});

it('omite <detail> y <recruit> cuando la tienda no tiene datos', function () {
    $xml = (new SignageFeedBuilder)->renderFloormap([
        'B1' => [['cd' => 1, 'cate' => null, 'name' => 'shop', 'kana' => null, 'details' => [], 'recruit' => null]],
    ]);

    expect($xml)->toContain("<shop cd=\"1\" cate=\"\" name=\"shop\" kana=\"\">\n</shop>\n");
});

it('lee kaisha_data de tb_shop en pares', function () {
    expect((new SignageFeedBuilder)->companyDetails('営業時間<>10:00||定休日<>水曜'))
        ->toBe([['営業時間', '10:00'], ['定休日', '水曜']]);
});
