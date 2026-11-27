<?php

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

/*
 * M1: テンプレート管理 y テストメールアドレス管理.
 * Mientras S1 no publique layout, login y la carga de routes/legacy, la prueba monta un layout
 * mínimo con las mismas secciones y carga routes/legacy/promocion.php si hace falta.
 */
beforeEach(function () {
    if (! View::exists('layouts.legacy')) {
        $dir = storage_path('framework/testing/promocion-views/layouts');
        File::ensureDirectoryExists($dir);
        $stub = "<html><head><title>@yield('title')</title>@yield('css')@yield('js')</head><body>@yield('content')</body></html>";
        File::put("$dir/legacy.blade.php", $stub);
        File::put("$dir/legacy-popup.blade.php", $stub);
        View::addLocation(dirname($dir));
    }
    if (! Route::has('legacy.promocion.mail_mag.ajax_mail_template')) {
        Route::middleware('web')->group(base_path('routes/legacy/promocion.php'));
        app('router')->getRoutes()->refreshNameLookups();
    }
    $this->withoutMiddleware(Authenticate::class);

    DB::table('tb_mail_template_category')->insert([
        ['cd' => 1, 'name' => 'お知らせ', 'rank' => 1, 'hyouji_flg' => '1', 'disp_flg' => '1'],
        ['cd' => 2, 'name' => '誕生日', 'rank' => 2, 'hyouji_flg' => '1', 'disp_flg' => '1'],
        ['cd' => 3, 'name' => '非表示', 'rank' => 3, 'hyouji_flg' => '9', 'disp_flg' => '1'],
    ]);
    DB::table('tb_mail_template')->insert([
        ['cd' => 10, 'tb_mail_template_category_cd' => 1, 'title' => '新商品', 'naiyou' => "こんにちは\n{名前}様", 'mail_type' => '', 'rank' => 1, 'hyouji_flg' => '1', 'disp_flg' => '1'],
        ['cd' => 11, 'tb_mail_template_category_cd' => 2, 'title' => 'お誕生日', 'naiyou' => '<b>おめでとう</b>', 'mail_type' => 'deco', 'rank' => 2, 'hyouji_flg' => '1', 'disp_flg' => '1'],
    ]);
});

describe('テンプレート管理', function () {
    it('abre la lista con las categorías visibles', function () {
        $this->get(route('admin.promocion.templates.index'))
            ->assertOk()
            ->assertSee('■テンプレート管理')
            ->assertSee('<option value="1">お知らせ</option>', false)
            ->assertDontSee('非表示');
    });

    it('carga la lista por AJAX, filtra por categoría y muestra la デコメール en HTML', function () {
        $this->post(route('legacy.promocion.mail_mag.ajax_mail_template'))
            ->assertOk()->assertSee('新商品')->assertSee('<b>おめでとう</b>', false)->assertSee('こんにちは<br />', false)->assertSee('順番');

        $this->post(route('legacy.promocion.mail_mag.ajax_mail_template'), ['send_cate' => '2'])
            ->assertOk()->assertSee('お誕生日')->assertDontSee('新商品')->assertDontSee('rankup');
    });

    it('cambia la visibilidad, el orden y borra con disp_flg = 9', function () {
        $ajax = route('legacy.promocion.mail_mag.ajax_mail_template');

        $this->post($ajax, ['send_type' => 'hyouji', 'send_targetCd' => 10, 'send_vl' => '1']);
        expect(DB::table('tb_mail_template')->where('cd', 10)->value('hyouji_flg'))->toBe('9');

        $this->post($ajax, ['send_type' => 'rankdown', 'send_targetCd' => 10, 'send_vl' => '1']);
        expect(DB::table('tb_mail_template')->orderBy('rank')->pluck('cd')->all())->toBe([11, 10]);

        $this->post($ajax, ['send_type' => 'del', 'send_targetCd' => 11])->assertDontSee('お誕生日');
        expect(DB::table('tb_mail_template')->where('cd', 11)->first())->disp_flg->toBe('9')->rank->toBeNull();
    });

    it('abre el popup de alta con las etiquetas y el de edición con los datos', function () {
        $this->get(route('admin.promocion.templates.entry'))
            ->assertOk()->assertSee('■テンプレート登録')->assertSee('{名前}')->assertSee('{ID}')->assertDontSee('{スタンプ数}');

        DB::table('tb_base_system_child')->insert(['cd' => 1, 'system_name' => 'スタンプ管理', 'system_flg' => '1']);

        $this->get(route('admin.promocion.templates.entry', ['cd' => 10]))
            ->assertOk()->assertSee('value="新商品"', false)->assertSee('{スタンプ数}')->assertSee('name="cd" value="10"', false);
    });

    it('da de alta con cd de hatuban y rank = cd, y modifica una デコメール', function () {
        DB::table('hatuban')->insert(['koumoku' => 'tb_mail_template', 'kazu' => 20]);

        $this->post(route('legacy.promocion.mail_mag.mail_template_register'), [
            'tb_mail_template_category_cd' => 1, 'mail_type' => '', 'title' => 'セール', 'naiyou' => '本文',
        ])->assertRedirect(route('admin.promocion.templates.entry', ['comp' => 1]));

        $row = DB::table('tb_mail_template')->where('cd', 20)->first();
        expect($row->title)->toBe('セール')->and($row->rank)->toBe(20)->and($row->naiyou)->toBe('本文')->and($row->disp_flg)->toBe('1');

        $this->post(route('legacy.promocion.mail_mag.mail_template_register'), [
            'cd' => 11, 'tb_mail_template_category_cd' => 2, 'mail_type' => 'deco', 'title' => '誕生日2', 'naiyou' => '', 'naiyou_deco' => '<p>新</p>',
        ])->assertRedirect();

        expect(DB::table('tb_mail_template')->where('cd', 11)->value('naiyou'))->toBe('<p>新</p>')
            ->and(DB::table('tb_mail_template')->where('cd', 11)->value('rank'))->toBe(2);
    });

    it('no registra sin título', function () {
        $this->post(route('legacy.promocion.mail_mag.mail_template_register'), ['tb_mail_template_category_cd' => 1, 'mail_type' => '', 'naiyou' => 'x'])
            ->assertSessionHasErrors('title');
    });
});

describe('テンプレートカテゴリ管理', function () {
    it('abre el popup, registra y borra categorías', function () {
        DB::table('hatuban')->insert(['koumoku' => 'tb_mail_template_category', 'kazu' => 5]);

        $this->get(route('admin.promocion.template-categories.index'))->assertOk()->assertSee('新規登録');
        $this->get(route('admin.promocion.template-categories.index', ['cd' => 1]))->assertOk()->assertSee('編集')->assertSee('value="お知らせ"', false);

        $this->post(route('legacy.promocion.mail_mag.mail_template_category_register'), ['name' => 'キャンペーン'])
            ->assertRedirect(route('admin.promocion.template-categories.index'));
        expect(DB::table('tb_mail_template_category')->where('cd', 5)->value('rank'))->toBe(5);

        $this->post(route('legacy.promocion.mail_mag.ajax_mail_template_category'), ['send_type' => 'del', 'send_targetCd' => 5])
            ->assertOk()->assertSee('登録カテゴリ一覧')->assertDontSee('キャンペーン');
    });
});

describe('テストメールアドレス管理', function () {
    beforeEach(function () {
        DB::table('tb_shop')->insert(['cd' => 1, 'kaishamei' => '本店', 'disp_flg' => '1']);
        DB::table('hatuban')->insert(['koumoku' => 'tb_test_mail_address', 'kazu' => 1]);
    });

    it('abre la pantalla con el selector de tiendas (master) y el formulario de la tienda', function () {
        $this->get(route('admin.promocion.test-addresses.index'))->assertOk()->assertSee('▼店舗選択')->assertDontSee('テストメールアドレス登録情報');
        $this->get(route('admin.promocion.test-addresses.index', ['shop' => 1]))->assertOk()->assertSee('■本店')->assertSee('テストメールアドレス登録情報');
        $this->get(route('admin.promocion.test-addresses.index', ['shop' => -1]))->assertOk()->assertSee('■全店舗用');
    });

    it('da de alta al final, modifica, ordena y borra subiendo a los de detrás', function () {
        $register = route('legacy.promocion.mail_mag.test_mail_address_register');
        foreach (['a', 'b', 'c'] as $name) {
            $this->post($register, ['act' => 'new', 'tb_shop_cd' => 1, 'name' => $name, 'email' => "$name@example.com"])
                ->assertRedirect(route('admin.promocion.test-addresses.index', ['shop' => 1]));
        }
        expect(DB::table('tb_test_mail_address')->orderBy('cd')->pluck('rank', 'name')->all())->toBe(['a' => 1, 'b' => 2, 'c' => 3]);

        $this->post($register, ['act' => 'up', 'cd' => 1, 'tb_shop_cd' => 1, 'name' => 'A', 'email' => 'a2@example.com']);
        expect(DB::table('tb_test_mail_address')->where('cd', 1)->value('email'))->toBe('a2@example.com');

        $this->post(route('legacy.promocion.mail_mag.ajax_test_mail_address_new'), ['shop_cd' => 1, 'send_type' => 'rankup', 'send_targetCd' => 3, 'send_vl' => 3])
            ->assertOk()->assertSee('a2@example.com');
        expect(DB::table('tb_test_mail_address')->where('cd', 3)->value('rank'))->toBe(2);

        $this->post($register, ['act' => 'del', 'cd' => 1, 'rank' => 1, 'tb_shop_cd' => 1]);
        expect(DB::table('tb_test_mail_address')->where('disp_flg', '1')->orderBy('rank')->pluck('rank', 'cd')->all())->toBe([3 => 1, 2 => 2]);
    });

    it('rechaza un correo mal formado como submitChk', function () {
        $this->post(route('legacy.promocion.mail_mag.test_mail_address_register'), ['act' => 'new', 'tb_shop_cd' => 1, 'name' => 'x', 'email' => 'no-es-correo'])
            ->assertSessionHasErrors('email');
    });

    it('devuelve nombre y correo del personal', function () {
        DB::table('tb_staff')->insert(['cd' => 4, 'name' => '佐藤', 'email1' => 'sato@example.com', 'disp_flg' => '1']);

        $this->get(route('legacy.promocion.mail_mag.ajax_test_mail_address_staff', ['cd' => 4]))
            ->assertExactJson(['name' => '佐藤', 'email' => 'sato@example.com']);
    });
});
