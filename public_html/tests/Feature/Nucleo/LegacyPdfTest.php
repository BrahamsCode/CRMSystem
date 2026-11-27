<?php

use App\Support\LegacyPdf;

// LegacyPdf (mPDF): HTML/Blade → PDF con texto japonés

it('genera un PDF con la fuente CJK de mPDF para el japonés', function () {
    $pdf = LegacyPdf::fromHtml('<h1>請求書</h1><p>株式会社テスト 御中 ￥1,000</p>');

    expect($pdf)->toStartWith('%PDF-')
        ->and($pdf)->toContain('Sun-ExtA');
});

it('descarga o muestra el PDF con StreamedResponse', function () {
    $response = LegacyPdf::download('領収書.pdf', '<p>領収書</p>');

    ob_start();
    $response->sendContent();
    $body = ob_get_clean();

    expect($response->headers->get('Content-Type'))->toBe('application/pdf')
        ->and($response->headers->get('Content-Disposition'))->toStartWith('attachment')
        ->and($body)->toStartWith('%PDF-');

    $inline = LegacyPdf::download('dm.pdf', '<p>DM</p>', ['format' => 'A4-L'], inline: true);
    expect($inline->headers->get('Content-Disposition'))->toStartWith('inline');
});

it('usa una fuente propia si su archivo existe en crm.pdf.font_dir', function () {
    $dir = storage_path('framework/testing/pdf-fonts');
    @mkdir($dir, 0777, true);
    copy(base_path('vendor/mpdf/mpdf/ttfonts/DejaVuSerif.ttf'), "$dir/propia.ttf");
    config(['crm.pdf.font_dir' => $dir, 'crm.pdf.fonts' => ['propia' => ['R' => 'propia.ttf'], 'falta' => ['R' => 'no-existe.ttf']], 'crm.pdf.default_font' => 'propia']);

    expect(LegacyPdf::fromHtml('<p>abc</p>'))->toContain('DejaVuSerif');

    // una fuente cuyo archivo falta no se registra y no rompe
    config(['crm.pdf.default_font' => 'falta']);
    expect(LegacyPdf::fromHtml('<p>日本語</p>'))->toContain('Sun-ExtA');
});
