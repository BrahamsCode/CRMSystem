<?php

use App\Services\Terminales\SignageFeedBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/* XML del reproductor leído de las tablas del legacy (tb_c7_movie_program, tb_screensaver, tb_shop, tb_recruit*) */

function screensaver(int $cd, string $title, string $dispFlg = '1'): void
{
    DB::table('tb_screensaver')->insert([
        'cd' => $cd, 'title' => $title, 'play_frame_sec' => '10', 'filetype' => '画像ファイル',
        'pic_extension' => 'jpg', 'pic_flg' => '1', 'disp_flg' => $dispFlg,
    ]);
}

it('genera el XML del programa en el orden de tb_screensaver_cds y sin los borrados', function () {
    screensaver(901, 'A');
    screensaver(902, 'B');
    screensaver(903, 'C', '9');
    DB::table('tb_c7_movie_program')->insert([
        'cd' => 950, 'name' => '店頭', 'disp_conf' => '1', 'disp_flg' => '1',
        'tb_screensaver_cds' => '902<>901<>903<>',
    ]);

    expect(app(SignageFeedBuilder::class)->program(950))->toBe(
        "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<data>\n"
        ."<p cd=\"902\" time=\"10\"  title=\"B\"  type=\"画像ファイル\" pic_extension=\"jpg\" pic_flg=\"1\">B</p>\n"
        ."<p cd=\"901\" time=\"10\"  title=\"A\"  type=\"画像ファイル\" pic_extension=\"jpg\" pic_flg=\"1\">A</p>\n"
        ."</data>\n"
    );
});

it('deja vacío el XML de un programa システム内蔵 y lo escribe en hp/uploads/c7/display', function () {
    Storage::fake('public');
    DB::table('tb_c7_movie_program')->insert(['cd' => 951, 'name' => 'システム', 'disp_conf' => '2', 'disp_flg' => '1']);

    $path = app(SignageFeedBuilder::class)->writeProgram(951);

    expect($path)->toBe('hp/uploads/c7/display/951.xml')
        ->and(Storage::disk('public')->get($path))->toBe('');
});

it('arma floormap.xml desde tb_shop y tb_recruit', function () {
    DB::table('tb_shop')->insert([
        'cd' => 960, 'kaishamei' => 'Polos', 'kaishamei_kana' => 'ポロス', 'service_kubun' => '2',
        'floor_name' => '1F', 'kaisha_data' => '営業時間<>10:00', 'disp_flg' => '1',
    ]);
    DB::table('tb_recruit_category')->insert(['cd' => 961, 'shokushu' => 'ホール', 'disp_flg' => '1']);
    DB::table('tb_recruit')->insert([
        'cd' => 962, 'tb_shop_cd' => 960, 'boshu_shokushu' => 961, 'boshu_headline' => '募集', 'disp_flg' => '1',
    ]);

    $xml = app(SignageFeedBuilder::class)->floormap();

    expect($xml)->toContain("<map floor=\"1F\">\n<shop cd=\"960\" cate=\"2\" name=\"Polos\" kana=\"ポロス\">\n")
        ->and($xml)->toContain("<detail>【営業時間】\n10:00\n\n</detail>\n")
        ->and($xml)->toContain("<recruit cd=\"962\">【募集見出し】\n募集\n【募集職種】\nホール\n\n</recruit>\n");
});
