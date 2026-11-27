<?php

use App\Services\Promotions\PointLogClassifier;

it('clasifica los movimientos como ポイント集計', function (array $row, string $category) {
    expect((new PointLogClassifier)->category($row))->toBe($category);
})->with([
    'caja' => [['kaikei' => 1000, 'action_flg' => 'crm:newRegist'], 'accounting'],
    'login diario (push 0)' => [['push_master_cd' => 0], 'mycolle:dailyLogin'],
    'alta CRM' => [['action_flg' => 'crm:newRegist'], 'crm:newRegist'],
    'alta app' => [['action_flg' => 'mycolle:newRegist'], 'mycolle:newRegist'],
    'push' => [['action_flg' => 'mycolle:push', 'push_master_cd' => 12], 'mycolle:push'],
    'ajuste manual' => [['action_flg' => null], 'adjust'],
    'cadena vacía no es NULL' => [['action_flg' => ''], 'other'],
    'uso por API' => [['action_flg' => 'api:point_use'], 'other'],
    'socio de pago' => [['action_flg' => 'paidMemberPoint:'], 'other'],
]);
