<?php

use App\Models\Terminal;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/helpers.php';

/* ICカード管理 / 端末管理 (felica/terminal_list.php): listado, ▲▼ y borrado por AJAX */

beforeEach(function () {
    s4Login(); // master de la tienda 1

    DB::table('tb_terminal')->insert([
        ['cd' => 1, 'tb_shop_cd' => 1, 'rank' => 1, 'type' => 'ピットタッチスリム', 'terminal_no' => 'PTS-001', 'mac_address' => 'AA:AA', 'flg' => '1', 'touroku_date' => '1700000000'],
        ['cd' => 2, 'tb_shop_cd' => 1, 'rank' => 2, 'type' => 'タブレット', 'terminal_no' => 'X', 'mac_address' => 'BB:BB', 'flg' => '1', 'touroku_date' => '1700000000'],
        ['cd' => 3, 'tb_shop_cd' => 1, 'rank' => 3, 'type' => 'タブレット', 'terminal_no' => 'Y', 'mac_address' => 'CC:CC', 'flg' => '1', 'touroku_date' => '1700000000'],
        ['cd' => 4, 'tb_shop_cd' => 1, 'rank' => 4, 'type' => '非表示', 'terminal_no' => 'Z', 'mac_address' => 'DD:DD', 'flg' => '9', 'touroku_date' => '1700000000'],
    ]);
});

it('lista los terminales visibles con tienda, serie y ▲▼', function () {
    $this->get(route('admin.terminales.felica-terminals.index'))
        ->assertOk()
        ->assertSee('■端末管理', false)
        ->assertSee('3件', false)
        ->assertSee('本店')
        ->assertSee('PTS-001')   // ピットタッチスリム: terminal_no
        ->assertSee('BB:BB')     // otro tipo: mac_address
        ->assertDontSee('DD:DD') // flg = '9'
        ->assertSee('row_tr1', false)
        ->assertSee(route('legacy.terminales.felica-del-terminal'), false);
});

it('redirige la URL del legacy a la pantalla nueva', function () {
    $this->get('/system/category7/felica/terminal_list.php?page=1')
        ->assertRedirect(route('admin.terminales.felica-terminals.index', ['page' => 1]));
});

it('sube y baja un terminal con ▲▼', function () {
    $this->get(route('admin.terminales.felica-terminals.sort', ['thisCd' => 2, 'thisRow' => 2, 'kind' => 'up']))
        ->assertRedirect(route('admin.terminales.felica-terminals.index'));

    expect(Terminal::find(2)->rank)->toBe(1)
        ->and(Terminal::find(1)->rank)->toBe(2);

    $this->get(route('admin.terminales.felica-terminals.sort', ['thisCd' => 2, 'thisRow' => 1, 'kind' => 'down']));

    expect(Terminal::find(2)->rank)->toBe(2)
        ->and(Terminal::find(1)->rank)->toBe(1);

    // El orden del listado sigue el rank
    $this->get(route('admin.terminales.felica-terminals.index'))
        ->assertSeeInOrder(['PTS-001', 'BB:BB', 'CC:CC']);
});

it('borra un terminal (físico) por AJAX y devuelve el script que quita la fila', function () {
    $this->post(route('legacy.terminales.felica-del-terminal'), ['cd' => 2])
        ->assertOk()
        ->assertSee("\$('#row_tr2').remove();", false);

    expect(DB::table('tb_terminal')->where('cd', 2)->exists())->toBeFalse();

    // Inexistente u oculto: mensaje del legacy
    $this->post(route('legacy.terminales.felica-del-terminal'), ['cd' => 4])
        ->assertOk()
        ->assertSee('削除失敗しました。');
    expect(DB::table('tb_terminal')->where('cd', 4)->exists())->toBeTrue();
});

it('muestra 見つかりませんでした sin terminales', function () {
    DB::table('tb_terminal')->delete();

    $this->get(route('admin.terminales.felica-terminals.index', ['page' => 3]))
        ->assertOk()
        ->assertSee('見つかりませんでした');
});
