<?php

use App\Models\ContentsMaster;
use App\Models\Mypage\MobileNavi;
use App\Models\Mypage\Recommendation;
use App\Models\Mypage\Recruit;
use App\Models\Mypage\RecruitCategory;
use App\Models\Shop;
use App\Services\Mypage\MypageConfig;
use Illuminate\Support\Facades\DB;

/* Modelos de マイページ sobre tb_hp_recommend, tb_recruit*, tb_mobile_navi y contents_master */

it('filtra las オススメ por tienda o 全店舗 y guarda la fecha en `update`', function () {
    DB::table('hatuban')->updateOrInsert(['koumoku' => 'tb_hp_recommend'], ['kazu' => 100]);

    $all = Recommendation::create(['title' => 'todas', 'tb_shop_cd' => 0, 'disp_date' => '2026/11/01', 'hyouji_flg' => '1', 'disp_flg' => '1']);
    Recommendation::create(['title' => 'tienda 5', 'tb_shop_cd' => 5, 'disp_date' => '2026/11/02', 'hyouji_flg' => '1', 'disp_flg' => '1']);
    Recommendation::create(['title' => 'tienda 6', 'tb_shop_cd' => 6, 'disp_date' => '2026/11/03', 'hyouji_flg' => '1', 'disp_flg' => '1']);

    expect($all->cd)->toBe(100)
        ->and($all->update)->toMatch('/^\d+$/')
        ->and(Recommendation::query()->forShop(5)->latestFirst()->pluck('title')->all())->toBe(['tienda 5', 'todas'])
        ->and(Recommendation::query()->forShop(null)->pluck('title')->all())->toBe(['todas']);
});

it('relaciona 求人, 職種 y tienda como el legacy', function () {
    $shop = Shop::query()->first() ?? Shop::create(['kaishamei' => 'shop', 'disp_flg' => '1']);
    $category = RecruitCategory::create(['cd' => 40, 'shokushu' => 'ホール', 'disp_flg' => '1']);
    RecruitCategory::create(['cd' => 41, 'shokushu' => '停止中', 'disp_flg' => '0']);
    $recruit = Recruit::create(['cd' => 50, 'tb_shop_cd' => $shop->cd, 'boshu_shokushu' => 40, 'boshu_headline' => '募集', 'disp_flg' => '1']);
    $withInactive = Recruit::create(['cd' => 51, 'tb_shop_cd' => $shop->cd, 'boshu_shokushu' => 41, 'disp_flg' => '1']);

    expect($recruit->category->shokushu)->toBe('ホール')
        ->and($withInactive->category)->toBeNull()
        ->and($recruit->shop->cd)->toBe($shop->cd)
        ->and($category->recruits()->pluck('cd')->all())->toBe([50]);
});

it('lee el nombre del menú de tb_mobile_navi', function () {
    MobileNavi::create(['cd' => 900, 'name' => 'イチオシ', 'link' => 'recommend.php', 'rank' => 1, 'disp_flg' => '1']);

    expect(MobileNavi::nameForLink('recommend', 'オススメ'))->toBe('イチオシ')
        ->and(MobileNavi::nameForLink('whatsnew', '新着情報'))->toBe('新着情報');
});

it('lee y guarda mypage_config con el formato del legacy', function () {
    $config = app(MypageConfig::class);
    $config->saveMypageConfig(['cover' => [0 => [0 => 1], 1 => [7 => 1]], 'comment_flg' => '1']);

    // El legacy guarda las comillas dobles como simples
    expect(ContentsMaster::query()->where('field', 'mypage_config')->value('text_data'))->toContain("s:5:'cover'")
        ->and($config->mypageConfig()['comment_flg'])->toBe('1')
        ->and($config->slotVisibleFor('cover', 0, null))->toBeTrue()
        ->and($config->slotVisibleFor('cover', 1, 7))->toBeTrue()
        ->and($config->slotVisibleFor('cover', 1, 8))->toBeFalse();

    $config->saveLinks('aplicover', ['https://a.example', '', 'tel:0612345678']);
    expect($config->links('aplicover'))->toBe(['https://a.example', '', 'tel:0612345678']);
});
