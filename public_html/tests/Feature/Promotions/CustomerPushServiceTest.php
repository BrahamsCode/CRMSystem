<?php

use App\Services\Promotions\CustomerPushService;
use App\Services\Promotions\PushTrigger;
use Illuminate\Support\Facades\DB;

it('crea el push_master dirigido al cliente, su push_notifications y avisa a g-api', function () {
    DB::table('hatuban')->insert(['koumoku' => 'push_master', 'kazu' => 50]);
    $calls = [];
    app()->instance(PushTrigger::class, new class($calls) implements PushTrigger {
        public function __construct(private array &$calls) {}

        public function trigger(string $type, int $masterCd): void
        {
            $this->calls[] = [$type, $masterCd];
        }
    });

    $cd = app(CustomerPushService::class)->send("o'neil", 2, 'ご登録ありがとうございます', '本文', ['system' => 'mpa', 'file' => 'signUp', 'action' => 'newRegist'],
        100, 1800000000, ['cd' => 9, 'registration_id' => 'abc']);

    $row = DB::table('push_master')->where('cd', $cd)->first();
    expect($cd)->toBe(50)
        ->and([$row->wherestr, unserialize($row->message), $row->post_type, (int) $row->post_point, (int) $row->version, $row->disp_flg])
        ->toBe(["WHERE id = 'o''neil' AND disp_flg = '1' AND touroku_kubun = '2'", ['本文'], '2', 100, 2, '1'])
        ->and(json_decode($row->extra_data, true)['action'])->toBe('newRegist')
        ->and(DB::table('push_notifications')->where('type_master_cd', $cd)->value('registration_id'))->toBe('abc')
        ->and($calls)->toBe([['push', 50]]);
});
