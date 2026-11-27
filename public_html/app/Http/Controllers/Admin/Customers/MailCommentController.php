<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Services\Customers\ReaderModeService;
use App\Services\Nucleo\LegacyUi;
use App\Support\LegacyText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * メールコメント (legacy `system/category1/config/mail_comment.php` y `mail_comment_register.php`; sin enlace
 * en el menú): textos de entrada y salida en la fila `cd = 2` de tb_felica_mode, guardados con get_form2().
 */
class MailCommentController extends Controller
{
    public function __construct(private ReaderModeService $readerMode) {}

    public function edit(): View
    {
        $row = $this->readerMode->row();

        return view('admin.clientes.config.mail-comment', [
            'siteName' => app(LegacyUi::class)->siteName(),
            'entryComment' => (string) $row?->nyushitsu_komento,
            'exitComment' => (string) $row?->taishitsu_komento,
        ]);
    }

    public function update(Request $request): View|RedirectResponse
    {
        $entry = LegacyText::input($request->input('nyushitsu_komento'));
        $exit = LegacyText::input($request->input('taishitsu_komento'));
        // submitChk() del legacy (solo JS): sin alguno de los dos comentarios no se registra
        if ($entry === '' || $exit === '') {
            return to_route('admin.clientes.mail-comment');
        }

        $this->readerMode->setComments($entry, $exit);

        return view('admin.clientes.settings.processing', [
            'title' => 'メールコメント登録 ' . app(LegacyUi::class)->siteName(),
            'heading' => '■メールコメント登録',
            'message' => '登録中です。',
            'redirectUrl' => route('admin.clientes.mail-comment'),
        ]);
    }
}
