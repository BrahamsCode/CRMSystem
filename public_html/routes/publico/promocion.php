<?php

use App\Http\Controllers\Publico\Promotions\QrImageController;
use Illuminate\Support\Facades\Route;

/*
 * 販売促進管理 (S3/S4): URL literales de category3 que se usan sin la sesión del personal (CONVENCIONES §2).
 * qr_img.php no pide login en el legacy: lo usan como <img src> appview (memberId.php, qr_code.php),
 * QRコード情報 y otras pantallas, y lo piden por HTTP card_signage y el HTML de QR印刷 del legacy.
 */
Route::get('system/category3/mail_mag/qr_img.php', QrImageController::class)->name('publico.promocion.qr_img');
