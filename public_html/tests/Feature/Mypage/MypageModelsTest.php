<?php

use App\Enums\LotteryRank;
use App\Enums\Mypage\AppImageSection;
use App\Models\AppImage;
use App\Models\LotterySetting;
use App\Models\Recommendation;
use App\Models\Shop;
use Database\Seeders\MypageSeeder;
use Database\Seeders\ShopSeeder;
use Illuminate\Support\Facades\Storage;

it('muestra las imágenes de 全店舗 y las de la tienda del cliente', function () {
    $this->seed(ShopSeeder::class);
    [$shopA, $shopB] = Shop::query()->orderBy('id')->take(2)->get();

    AppImage::factory()->create(['section' => AppImageSection::Cover, 'position' => 0]);
    $onlyA = AppImage::factory()->create(['section' => AppImageSection::Cover, 'position' => 1, 'all_shops_flg' => 0]);
    $onlyA->shops()->attach($shopA->id);

    $visible = fn (?int $shopId) => AppImage::query()->inSection(AppImageSection::Cover)->visibleForShop($shopId)->pluck('position')->all();

    expect($visible($shopA->id))->toBe([0, 1])
        ->and($visible($shopB->id))->toBe([0])
        ->and($visible(null))->toBe([0]);
});

it('conserva el nombre de archivo legacy y añade ?r=<mtime> a la URL', function () {
    Storage::fake('public');
    $path = AppImage::storagePathFor(AppImageSection::Banner, '3.png');
    Storage::disk('public')->put($path, 'x');
    $image = AppImage::factory()->create(['section' => AppImageSection::Banner, 'position' => 3, 'path' => $path]);

    expect($path)->toBe('hp/uploads/c6/aplibanner/3.png')
        ->and($image->mtime())->toBeInt()
        ->and($image->url())->toEndWith('hp/uploads/c6/aplibanner/3.png?r='.$image->mtime());
});

it('filtra las オススメ por tienda o 全店舗', function () {
    $this->seed(ShopSeeder::class);
    $shop = Shop::query()->first();
    Recommendation::factory()->create(['title' => 'todas']);
    Recommendation::factory()->create(['title' => 'tienda', 'shop_id' => $shop->id]);

    expect(Recommendation::query()->forShop($shop->id)->count())->toBe(2)
        ->and(Recommendation::query()->forShop(null)->pluck('title')->all())->toBe(['todas']);
});

it('calcula la probabilidad de ハズレ como complemento de los premios', function () {
    $setting = LotterySetting::current();
    $setting->prizes()->create(['rank' => LotteryRank::Diamond, 'rate' => 1.5]);
    $setting->prizes()->create(['rank' => LotteryRank::Gold, 'rate' => 8.5]);

    expect($setting->fresh()->missRate())->toBe(90.0);
});

it('siembra los datos de prueba de マイページ', function () {
    $this->seed([ShopSeeder::class, MypageSeeder::class]);

    expect(AppImage::query()->count())->toBe(8)
        ->and(LotterySetting::current()->prizes)->toHaveCount(4)
        ->and(Recommendation::query()->count())->toBe(4);
});
