<?php

use App\Services\Promotions\QrImage;
use App\Services\Promotions\QrPrintPdf;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/../Terminales/helpers.php';
require_once __DIR__.'/pdf_helpers.php';

/* M7 QRコード情報 / メールマガジン基本情報 (mail_mag/base*.php), qr_img.php y QR印刷 (qr_print_pdf.php) */

function qrBaseCustomers(): void
{
    $row = fn (int $cd, ?string $type, array $extra = []) => $extra + [
        'cd' => $cd, 'id' => "K$cd", 'name' => "客$cd", 'touroku_kubun' => '2', 'mailmaga_flg' => '1', 'disp_flg' => '1',
        'mailmaga_email_type' => $type, 'tb_shop_cd' => 1, 'touroku_date' => (string) (1700000000 + $cd),
    ];
    DB::table('tb_customer')->insert([
        $row(1, 'パソコン'), $row(2, 'パソコン'), $row(3, 'DoCoMo'), $row(4, 'AU'), $row(5, 'Softbank'),
        $row(6, 'その他'), $row(7, null), $row(8, 'willcom'),
        // No cuentan: baja de メルマガ, borrado, otro touroku_kubun
        $row(9, 'パソコン', ['mailmaga_flg' => '0']), $row(10, 'パソコン', ['disp_flg' => '9']), $row(11, 'DoCoMo', ['touroku_kubun' => '1']),
    ]);
}

/** Matriz de módulos (1 = negro) leída de una imagen QR de módulo $s con 4 módulos de margen */
function qrModulesFromImage(string $bytes, int $s): array
{
    $img = imagecreatefromstring($bytes);
    $modules = intdiv(imagesx($img), $s) - 8;
    $out = [];
    for ($y = 0; $y < $modules; $y++) {
        for ($x = 0; $x < $modules; $x++) {
            $rgb = imagecolorsforindex($img, imagecolorat($img, (int) (($x + 4.5) * $s), (int) (($y + 4.5) * $s)));
            $out[$y][$x] = $rgb['red'] < 128 ? 1 : 0;
        }
    }

    return $out;
}

beforeEach(function () {
    s4Login();
});

it('QRコード情報 (base.php): 登録状況, QR de la app con application_use_flg y 携帯WEBマイページ por tienda', function () {
    qrBaseCustomers();
    DB::table('admin_master')->where('cd', 1)->update(['application_use_flg' => '1']);
    DB::table('tb_shop')->insert(['cd' => 2, 'master_cd' => 1, 'kaishamei' => '梅田店', 'disp_flg' => '1']);
    DB::table('tb_shop')->insert(['cd' => 3, 'master_cd' => 1, 'kaishamei' => '閉店', 'disp_flg' => '9']);

    $response = $this->get(route('admin.promocion.base.show'))
        ->assertOk()
        ->assertSee('<title>メールマガジン基本情報 - ', false)
        ->assertSee('■QRコード情報')
        ->assertSee('<p class="h3Title">メール会員登録状況</p>', false)
        // パソコン 2, DoCoMo 1, Au 1, Softbank 1, その他 (その他 + desconocidos) 3, 総数 8
        ->assertSeeInOrder(['パソコン', '<td>2</td>', 'DoCoMo', '<td>1</td>', 'Au', '<td>1</td>', 'Softbank', '<td>1</td>', 'その他', '<td>3</td>', '総数', '<td>8</td>'], false)
        ->assertSee('<p class="h3Title">モバイルQRコード</p>', false)
        ->assertSee('<button id="print_button">印刷</button>', false)
        ->assertSee('action="'.route('admin.promocion.base.qr-print').'" method="post" id="qr_print_pdf" target="_blank"', false)
        ->assertSeeInOrder(['マイコレ用の会員登録用QRコード', '本店', '梅田店', '店舗コード埋め込み無し', '携帯WEBマイページ用の会員登録用QRコード', '本店', '梅田店'])
        ->assertSee('src="/system/category3/mail_mag/qr_img.php?d=localhost%3fshop_cd=1"', false)
        ->assertSee('src="/system/category3/mail_mag/qr_img.php?d=localhost%3fshop_cd=2"', false)
        ->assertSee('src="/system/category3/mail_mag/qr_img.php?d=localhost"', false)
        ->assertSee('src="/system/category3/mail_mag/qr_img.php?d=http://localhost/hp/mailsyori/base.php?subject=1&amp;e=Q&amp;s=3&amp;t=j&amp;yoshi=', false)
        ->assertSee('data-qr_code="http://localhost/system/category3/mail_mag/qr_img.php?d=localhost%3fshop_cd=2"', false)
        ->assertSee('data-name="店舗コード埋め込み無し"', false)
        ->assertDontSee('閉店')
        ->assertDontSee('ポイント用QRコード');

    expect(substr_count($response->getContent(), 'name="qr_print_flg[]"'))->toBe(5);
});

it('las 4 variantes: URL del legacy, bloques y textos de cada una', function () {
    qrBaseCustomers();
    DB::table('admin_master')->where('cd', 1)->update(['application_use_flg' => '1', 'push_api_host' => '1']);

    // Las URL del menú (left_navi3) llevan a la pantalla con su type
    foreach (['base' => 'base', 'base_mailmaga' => 'mailmaga', 'base_mycolle' => 'mycolle', 'base_point' => 'point'] as $file => $type) {
        $this->get("/system/category3/mail_mag/$file.php")->assertRedirect(route('admin.promocion.base.show', ['type' => $type]));
    }

    // メルマガ用QR: 登録状況 y 携帯WEBマイページ, sin la app
    $this->get(route('admin.promocion.base.show', ['type' => 'mailmaga']))
        ->assertOk()
        ->assertSee('■QRコード情報')
        ->assertSee('メール会員登録状況')
        ->assertSee('携帯WEBマイページ用の会員登録用QRコード')
        ->assertDontSee('マイコレ用の会員登録用QRコード')
        ->assertDontSee('店舗コード埋め込み無し');

    // マイコレ登録用QR: solo el bloque de la app
    $this->get(route('admin.promocion.base.show', ['type' => 'mycolle']))
        ->assertOk()
        ->assertSee('マイコレ用の会員登録用QRコード')
        ->assertSee('店舗コード埋め込み無し')
        ->assertSee('qr_img.php?d=localhost%3fshop_cd=1', false)
        ->assertDontSee('メール会員登録状況')
        ->assertDontSee('携帯WEBマイページ用の会員登録用QRコード');

    // ポイント利用QR: viewUsePointInfo del API (push_api_host 1 = b.g-api.net)
    $this->get(route('admin.promocion.base.show', ['type' => 'point']))
        ->assertOk()
        ->assertSee('ポイント用QRコード')
        ->assertSee('src="/system/category3/mail_mag/qr_img.php?d=http://b.g-api.net/api/mpa/viewUsePointInfo%3fdomain=localhost%26shop_cd=1"', false)
        ->assertSee('src="/system/category3/mail_mag/qr_img.php?d=http://b.g-api.net/api/mpa/viewUsePointInfo%3fdomain=localhost"', false)
        ->assertDontSee('マイコレ用の会員登録用QRコード')
        ->assertDontSee('メール会員登録状況');

    DB::table('admin_master')->where('cd', 1)->update(['push_api_host' => '0']);
    $this->get(route('admin.promocion.base.show', ['type' => 'point']))->assertSee('http://b-develop.g-api.net/api/mpa/viewUsePointInfo', false);

    // Sin la app: las variantes de la app quedan con la cabecera y la tabla vacía, como el legacy
    DB::table('admin_master')->where('cd', 1)->update(['application_use_flg' => '0']);
    $this->get(route('admin.promocion.base.show', ['type' => 'mycolle']))
        ->assertOk()
        ->assertSee('モバイルQRコード')
        ->assertDontSee('マイコレ用の会員登録用QRコード');
    $this->get(route('admin.promocion.base.show'))->assertDontSee('マイコレ用の会員登録用QRコード')->assertSee('携帯WEBマイページ用の会員登録用QRコード');

    $this->get(route('admin.promocion.base.show', ['type' => 'otro']))->assertNotFound();
});

it('un usuario que no es master solo ve su tienda', function () {
    DB::table('tb_staff')->insert(['cd' => 2, 'office_cd' => 1, 'shop_cd' => 2, 'login_id' => 'tencho', 'login_pass' => 'secreto2', 'name' => '店長', 'disp_flg' => '1']);
    DB::table('tb_shop')->insert(['cd' => 2, 'master_cd' => 1, 'kaishamei' => '梅田店', 'disp_flg' => '1']);
    $this->get('/system/logout.php');
    loginAs('tencho', 'secreto2');

    $this->get(route('admin.promocion.base.show', ['type' => 'mailmaga']))
        ->assertOk()
        ->assertSee('梅田店')
        ->assertDontSee('本店');
});

it('qr_img.php: PNG o JPEG del tamaño del legacy, sin login y decodificable', function () {
    auth('admin')->logout();
    $qr = app(QrImage::class);

    // Numérico, M, v1 (21 módulos): PNG módulo 4 por defecto → (21 + 8) × 4
    $png = $this->get('/system/category3/mail_mag/qr_img.php?d=12345')
        ->assertOk()->assertHeader('Content-Type', 'image/png')->getContent();
    expect(getimagesizefromstring($png))->toMatchArray([0 => 116, 1 => 116, 'mime' => 'image/png']);

    // t=J: JPEG con módulo 8 por defecto
    $jpeg = $this->get('/system/category3/mail_mag/qr_img.php?d=12345&t=J')
        ->assertOk()->assertHeader('Content-Type', 'image/jpeg')->getContent();
    expect(getimagesizefromstring($jpeg))->toMatchArray([0 => 232, 1 => 232, 'mime' => 'image/jpeg']);

    // s y v: versión 5 (37 módulos) × 3 px
    expect(getimagesizefromstring($this->get('/system/category3/mail_mag/qr_img.php?d=ABC&s=3&v=5')->getContent())[0])->toBe((37 + 8) * 3);

    // Query como la de 携帯WEBマイページ (d sin codificar con ? dentro): los módulos son los de los datos esperados con corrección Q
    $data = 'http://localhost/hp/mailsyori/base.php?subject=1';
    $bytes = $this->get('/system/category3/mail_mag/qr_img.php?d='.$data.'&e=Q&s=3&t=p&yoshi=1')->assertOk()->getContent();
    $matrix = $qr->matrix($data, 'Q');
    $expected = [];
    for ($y = 0; $y < $matrix->getWidth(); $y++) {
        for ($x = 0; $x < $matrix->getWidth(); $x++) {
            $expected[$y][$x] = $matrix->get($x, $y);
        }
    }
    expect(getimagesizefromstring($bytes)[0])->toBe(($matrix->getWidth() + 8) * 3)
        ->and(qrModulesFromImage($bytes, 3))->toBe($expected)
        // Patrón de localización arriba a la izquierda (7×7 con borde negro)
        ->and(array_slice($expected[0], 0, 7))->toBe([1, 1, 1, 1, 1, 1, 1])
        ->and(array_slice($expected[1], 0, 7))->toBe([1, 0, 0, 0, 0, 0, 1]);

    // %3f doblemente codificado, como el QR de マイコレ: codifica "localhost?shop_cd=1"
    $mycolle = $this->get('/system/category3/mail_mag/qr_img.php?d=localhost%253fshop_cd=1')->getContent();
    expect(qrModulesFromImage($mycolle, 4))->toBe(qrModulesFromImage($qr->render('localhost?shop_cd=1'), 4));

    // Sin datos: error, como el legacy
    $this->get('/system/category3/mail_mag/qr_img.php')->assertStatus(400);
    // Demasiado grande (> 1480 px)
    $this->get('/system/category3/mail_mag/qr_img.php?d=12345&s=60')->assertStatus(400);
});

it('ダウンロード: el QR en JPEG con el nombre de la tienda, sin leer archivos del servidor', function () {
    $response = $this->get(route('admin.promocion.base.download', ['qr' => 'd=localhost%3fshop_cd=1', 'name' => '本店']))
        ->assertOk();

    expect($response->headers->get('Content-Disposition'))->toContain("filename*=utf-8''".rawurlencode('本店.jpg'))
        ->and(getimagesizefromstring($response->getContent())['mime'])->toBe('image/jpeg');

    // El file_path del legacy (cualquier archivo) ya no existe: base.php?is_download=1 solo redirige a la pantalla
    $this->get('/system/category3/mail_mag/base.php?is_download=1&file_path=/etc/passwd')
        ->assertRedirect(route('admin.promocion.base.show', ['type' => 'base']));
});

it('QR印刷: PDF A4 (mPDF) con el HTML del legacy, el nombre y la imagen de cada QR marcado', function () {
    $names = ['本店', '店舗コード埋め込み無し', 'Shop A'];
    $urls = [
        'http://localhost/system/category3/mail_mag/qr_img.php?d=localhost%3fshop_cd=1',
        'http://localhost/system/category3/mail_mag/qr_img.php?d=localhost',
        'http://localhost/system/category3/mail_mag/qr_img.php?d=http://localhost/hp/mailsyori/base.php?subject=1&e=Q&s=3&t=j&yoshi=1',
    ];

    $pdf = $this->post(route('admin.promocion.base.qr-print'), ['print_name' => $names, 'qr_code' => $urls])
        ->assertOk()->assertHeader('Content-Type', 'application/pdf')->getContent();

    // mPDF parte los textos en varios Tj (por línea y por fuente): se comparan concatenados
    $text = implode('', pdfTextList($pdf));
    expect($pdf)->toStartWith('%PDF-')->toContain('%%EOF')
        ->toContain('/MediaBox [0 0 595.280 841.890]')
        ->toContain('/Count 1')
        ->and(substr_count($pdf, '/Subtype /Image'))->toBe(3)
        // 3 imágenes de 45.5 mm en la misma fila, una por columna de 47.5 mm
        ->and(preg_match_all('/([\d.]+) 0 0 [\d.]+ ([\d.]+) ([\d.]+) cm/', implode('', pdfStreams($pdf)), $cm))->toBe(3)
        ->and(array_map(fn ($w) => round($w / (72 / 25.4), 1), $cm[1]))->toBe([45.5, 45.5, 45.5])
        ->and(array_unique($cm[3]))->toHaveCount(1)
        ->and($text)->toContain('本店')->toContain('店舗コード埋め込み無し')->toContain('Shop A');
    // Cuatro columnas de igual ancho: los 3 nombres en la misma línea (mPDF escribe el japonés en varios Tj)
    expect(collect(pdfTexts($pdf))->pluck('y')->unique())->toHaveCount(1);

    // El HTML que recibe mPDF: el de qr_print_pdf.php (4 columnas al 25 %, <p>nombre</p> + imagen del ancho de la columna)
    ['html' => $html, 'images' => $images] = app(QrPrintPdf::class)->html($names, $urls);
    expect($html)->toContain('<title>QR印刷</title>')
        ->toContain('<col width="25%">')
        ->toContain('<p>本店</p>')
        ->toContain('<img src="var:qr0" style="width:45.5mm">')
        ->toContain('<td colspan="1"></td>')
        ->and($images)->toHaveCount(3);
    // Cada imagen es la de qr_img.php con su query: (módulos + 8) × módulo
    $side = (app(QrImage::class)->matrix('localhost?shop_cd=1')->getWidth() + 8) * 4;
    expect(getimagesizefromstring($images['qr0']))->toMatchArray([0 => $side, 1 => $side, 'mime' => 'image/png'])
        ->and(qrModulesFromImage($images['qr0'], 4))->toBe(qrModulesFromImage(app(QrImage::class)->render('localhost?shop_cd=1'), 4))
        ->and(getimagesizefromstring($images['qr2'])[0])->toBe((app(QrImage::class)->matrix('http://localhost/hp/mailsyori/base.php?subject=1', 'Q')->getWidth() + 8) * 3);

    // 4 por fila: 9 QR → 3 filas, la última con 1 celda y colspan 3
    $nine = app(QrPrintPdf::class)->html(array_map(fn ($i) => "店$i", range(1, 9)), array_map(fn ($i) => "http://localhost/system/category3/mail_mag/qr_img.php?d=$i", range(1, 9)));
    expect(substr_count($nine['html'], '<tr>'))->toBe(3)->and($nine['html'])->toContain('<td colspan="3"></td>');

    // Una URL ajena a qr_img.php no se descarga: solo el nombre
    $foreign = $this->post(route('admin.promocion.base.qr-print'), ['print_name' => ['X'], 'qr_code' => ['http://169.254.169.254/latest']])->getContent();
    expect(pdfTextList($foreign))->toContain('X')->and($foreign)->not->toContain('/Subtype /Image');

    // Sin marcar ninguno: el mensaje del legacy
    $this->post(route('admin.promocion.base.qr-print'))
        ->assertOk()
        ->assertSee('表示したい店舗を一店舗以上設定下さい。');
});

it('la pantalla exige la sesión del personal', function () {
    auth('admin')->logout();
    $this->get(route('admin.promocion.base.show'))->assertRedirect();
    $this->post(route('admin.promocion.base.qr-print'))->assertRedirect();
});
