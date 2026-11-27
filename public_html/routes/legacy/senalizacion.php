<?php

use App\Http\Controllers\Admin\Senalizacion\BillingPdfController;
use App\Http\Controllers\Admin\Senalizacion\ContactController;
use Illuminate\Support\Facades\Route;

/*
 * URL literales de GoTel en signage/ del legacy (S4, PROVISIONAL: en pausa hasta que Japón confirme el uso).
 * S1 las carga sin prefijo de URL, con el guard del personal y el prefijo de nombre `legacy.`: legacy.senalizacion.*
 */
Route::name('senalizacion.')->group(function () {
    Route::match(['get', 'post'], 'signage/mail', [ContactController::class, 'form'])->name('contact');
    Route::match(['get', 'post'], 'signage/mail/index.php', [ContactController::class, 'form'])->name('contact.index');
    Route::get('signage/pdf/invoice.php', [BillingPdfController::class, 'invoice'])->name('invoice');
    Route::get('signage/pdf/receipt.php', [BillingPdfController::class, 'receipt'])->name('receipt');
});
