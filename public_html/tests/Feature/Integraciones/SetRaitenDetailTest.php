<?php

use App\Services\Customers\VisitRecordService;
use App\Services\Customers\VisitRecordValidationException;
use Illuminate\Support\Facades\DB;

/*
 * tool/api/category1/setRaitenDetail.php sobre VisitRecordService de S2. La lógica de puntos, sellos y 関連情報
 * es de S2 (tarea C3): aquí se prueba el endpoint con el servicio simulado. La prueba de contrato contra el legacy
 * queda pendiente hasta C3.
 */

const SRD_URL = '/system/tool/api/category1/setRaitenDetail.php';

beforeEach(function () {
    config(['services.crm.api_key' => 'k']);
    DB::table('admin_master')->insert(['cd' => 1, 'related_features' => '1<>1<>10']);
    DB::table('tb_customer')->insert(['cd' => 77, 'id' => '1000077', 'name' => '山田', 'disp_flg' => '1', 'touroku_kubun' => '2']);
});

it('setRaitenDetail: guarda con VisitRecordService (mismas claves, sin personal) y responde el cd', function () {
    $records = Mockery::mock(VisitRecordService::class);
    $records->shouldReceive('save')->once()
        ->withArgs(fn ($customer, array $data, $staff = null) => $customer->cd === 77 && $data['kaikei'] === '3000' && $data['form101'] === 'カット'
            && ! array_key_exists('key', $data) && $staff === null)
        ->andReturn(901);
    app()->instance(VisitRecordService::class, $records);

    $params = ['key' => 'k', 'kaiin_cd' => '77', 'raiten_y' => '2026', 'raiten_m' => '3', 'raiten_d' => '5', 'raiten_h' => '10', 'raiten_i' => '0',
        'kaikei' => '3000', 'form101' => 'カット', 'add_record_verify' => 'abc'];

    expect($this->post(SRD_URL, $params)->getContent())->toBe('{ "status": "SUCCESS", "result": "901" }');

    // El mismo add_record_verify en la sesión: no se guarda dos veces (SUCCESS sin cd, como el legacy)
    expect($this->post(SRD_URL, $params)->getContent())->toBe('{ "status": "SUCCESS", "result": "" }');
});

it('setRaitenDetail: errores del endpoint y de S2 con el formato del legacy (también JSONP)', function () {
    $records = Mockery::mock(VisitRecordService::class);
    $records->shouldReceive('save')->andThrow(new VisitRecordValidationException(['raiten' => '※日付の入力をご確認ください。<br>', '101' => '※必須入力項目です。']));
    app()->instance(VisitRecordService::class, $records);

    expect($this->post(SRD_URL, ['key' => 'k'])->getContent())
        ->toBe('{ "status": "error", "result": { "customer" : "顧客CD番号が空です。" } }');

    expect($this->post(SRD_URL, ['key' => 'k', 'kaiin_cd' => '77'])->getContent())
        ->toBe('{ "status": "error", "result": { "add_record_verify": "多重登録エラーです。" } }');

    expect($this->post(SRD_URL, ['key' => 'k', 'kaiin_cd' => '77', 'add_record_verify' => 'x', 'data_type' => 'jsonp', 'callback' => 'cb'])->getContent())
        ->toBe('cb({ "status": "error", "result": { "raiten" : "日付の入力をご確認ください。","101" : "必須入力項目です。" } })');

    DB::table('admin_master')->update(['related_features' => '']);
    expect($this->post(SRD_URL, ['key' => 'k', 'kaiin_cd' => '77'])->getContent())
        ->toBe('{ "status": "error", "result": { "relatedFeaturesConfig" : "カルテ使用設定がされていません。" } }');
});

it('setRaitenDetail: contrato byte a byte contra el legacy', function () {
    // Grabar los casos de setRaitenDetail (tools/cases-tool.json) cuando S2 implemente VisitRecordService (C3)
})->todo();
