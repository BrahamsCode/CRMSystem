<?php

use App\Enums\Terminales\SignageContentMode;
use App\Enums\Terminales\SignageFileType;
use App\Models\JobCategory;
use App\Models\JobPosting;
use App\Models\SignageMaterial;
use App\Models\SignageProgram;
use App\Services\Terminales\SignageFeedBuilder;

// Arranca Laravel (los modelos lo necesitan) pero sin tocar la BD
uses(Tests\TestCase::class);

/*
 * Forma de los XML que lee el reproductor, comparada con la salida de
 * category7/digital_signage/xml_up.php y xml_info_up.php (espacios dobles incluidos).
 */

function material(array $attributes): SignageMaterial
{
    $material = new SignageMaterial($attributes);
    $material->id = $attributes['id'];

    return $material;
}

it('arma la línea <p> de un material igual que xml_up.php', function () {
    $line = (new SignageFeedBuilder)->materialLine(material([
        'id' => 12,
        'title' => '本日のおすすめ',
        'duration_seconds' => 15,
        'file_type' => SignageFileType::Image,
        'images' => [['flg' => '1', 'ext' => 'jpg'], ['flg' => '', 'ext' => '']],
    ]));

    expect($line)->toBe(
        '<p cd="12" time="15"  title="本日のおすすめ"  type="画像ファイル" pic_extension="jpg," pic_flg="1,">本日のおすすめ</p>'."\n"
    );
});

it('escapa los caracteres que rompían el XML del legacy', function () {
    $line = (new SignageFeedBuilder)->materialLine(material([
        'id' => 1,
        'title' => 'A&B "特売"',
        'duration_seconds' => 5,
        'file_type' => SignageFileType::Flv,
        'images' => [],
    ]));

    expect($line)->toContain('title="A&amp;B &quot;特売&quot;"')
        ->and($line)->toContain('type="FLVファイル" pic_extension="" pic_flg=""');
});

it('deja vacío el XML de un programa en modo システム内蔵', function () {
    $program = new SignageProgram(['content_mode' => SignageContentMode::System]);

    expect((new SignageFeedBuilder)->program($program))->toBe('');
});

it('arma floormap.xml con planta, tienda, ficha y 求人', function () {
    $category = new JobCategory(['name' => 'ホールスタッフ']);
    $posting = new JobPosting([
        'headline' => 'スタッフ募集',
        'description' => '接客',
        'employment_type' => 'アルバイト',
        'requirements' => '未経験歓迎',
        'salary' => '時給1200円',
    ]);
    $posting->id = 7;
    $posting->setRelation('category', $category);

    $xml = (new SignageFeedBuilder)->renderFloormap([
        '1F' => [[
            'cd' => 3,
            'cate' => '2',
            'name' => 'Polos',
            'kana' => 'ポロス',
            'details' => [['営業時間', '10:00-20:00'], ['定休日', '']],
            'posting' => $posting,
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
        'B1' => [['cd' => 1, 'cate' => null, 'name' => 'shop', 'kana' => null, 'details' => [], 'posting' => null]],
    ]);

    expect($xml)->toContain("<shop cd=\"1\" cate=\"\" name=\"shop\" kana=\"\">\n</shop>\n");
});

it('lee la ficha de tienda en el formato del legacy (kaisha_data)', function () {
    expect((new SignageFeedBuilder)->companyDetails('営業時間<>10:00||定休日<>水曜'))
        ->toBe([['営業時間', '10:00'], ['定休日', '水曜']]);
});
