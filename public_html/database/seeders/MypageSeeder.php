<?php

namespace Database\Seeders;

use App\Enums\LotteryRank;
use App\Enums\Mypage\AppImageSection;
use App\Enums\Mypage\BannerMode;
use App\Models\AppImage;
use App\Models\AppSetting;
use App\Models\JobCategory;
use App\Models\JobPosting;
use App\Models\LotterySetting;
use App\Models\Recommendation;
use App\Models\Shop;
use Illuminate\Database\Seeder;

/** Datos de prueba de マイページ管理: マイコレ, オススメ, 求人 y ガチャリン */
class MypageSeeder extends Seeder
{
    public function run(): void
    {
        $shop = Shop::query()->orderBy('id')->first();

        AppSetting::current()->update([
            'customer_group_display_flg' => 1,
            'home_comment' => 'ご来店ありがとうございます。',
            'home_comment_flg' => 1,
        ]);

        // Dos huecos por sección; el segundo solo para la primera tienda
        foreach (AppImageSection::cases() as $section) {
            foreach ([0, 1] as $position) {
                $image = AppImage::firstOrCreate(
                    ['section' => $section, 'position' => $position],
                    [
                        'path' => AppImage::storagePathFor($section, "{$position}.jpg"),
                        'link_url' => $position === 0 ? 'https://example.com/' : null,
                        'banner_mode' => $section === AppImageSection::Banner ? BannerMode::Free : null,
                        'all_shops_flg' => $position === 0 || ! $shop ? 1 : 0,
                    ]
                );
                if (! $image->all_shops_flg && $shop) {
                    $image->shops()->syncWithoutDetaching([$shop->id]);
                }
            }
        }

        if (Recommendation::query()->doesntExist()) {
            Recommendation::factory()->count(3)->create();
            Recommendation::factory()->create(['shop_id' => $shop?->id, 'title' => '店舗限定のおすすめ']);
        }

        if (JobPosting::query()->doesntExist()) {
            $category = JobCategory::factory()->create(['name' => 'ホールスタッフ']);
            JobCategory::factory()->create(['name' => 'キッチンスタッフ']);
            JobPosting::factory()->create(['shop_id' => $shop?->id, 'job_category_id' => $category->id]);
        }

        $lottery = LotterySetting::current();
        $lottery->update(['name' => 'ガチャリン', 'enabled_flg' => 1, 'max_tries' => 1, 'prize_count' => 4]);
        $rates = [LotteryRank::Diamond->value => 1, LotteryRank::Gold->value => 4, LotteryRank::Silver->value => 10, LotteryRank::Bronze->value => 25];
        foreach ($rates as $rank => $rate) {
            $lottery->prizes()->firstOrCreate(['rank' => $rank], ['quantity' => 10, 'rate' => $rate]);
        }
    }
}
