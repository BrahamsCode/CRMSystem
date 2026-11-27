<?php

namespace App\Http\Controllers\Admin\Senalizacion;

use App\Mail\Senalizacion\ContactReceivedMail;
use App\Models\Senalizacion\SignageWebContact;
use App\Models\Senalizacion\SignageWebCustomer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;

/**
 * signage/mail/index.php: GoTel管理 / お問い合わせ (PROVISIONAL: en pausa hasta que Japón confirme el uso).
 * Una sola URL como el legacy: 入力 → 確認 (confirm=1) → 送信 (send=1, INSERT en `contact`).
 * Por GET con ?customer_cd= rellena el 【内線追加】 con el nombre del `customer`; sin cliente, el asunto por defecto.
 * El legacy no enviaba ningún correo (el texto de gracias lo promete); aquí se envía ContactReceivedMail en cola
 * solo si config('senalizacion.contact_notify') tiene destinatario (vacío por defecto = como el legacy).
 */
class ContactController extends SignageController
{
    public const DEFAULT_SUBJECT = '【お問い合わせ】';

    public const SAVE_ERROR = '登録失敗しました';

    public function form(Request $request): Response
    {
        $input = array_merge($request->query(), $request->post());
        $success = [];
        $error = [];

        if ((string) $request->post('send') === '1') {
            $contact = new SignageWebContact([
                'subject' => (string) $request->input('subject'),
                'name' => (string) $request->input('name'),
                'message' => (string) $request->input('message'),
            ]);
            if ($contact->save()) {
                $success[] = self::SAVED;
                if ($to = config('senalizacion.contact_notify')) {
                    Mail::to($to)->send(new ContactReceivedMail($contact));
                }
            } else {
                $error[] = self::SAVE_ERROR;
            }
        }

        if (! $request->isMethod('post')) {
            $input = array_merge($input, $this->defaults($request));
        }

        return $this->legacyView->screen($request, 'contact', [
            '_request' => $input,
            'success_msg_ary' => $success,
            'error_msg_ary' => $error,
            '_form_action' => $request->url(),
        ]);
    }

    /** Valores iniciales del legacy: asunto por defecto o el 【内線追加】 del cliente */
    private function defaults(Request $request): array
    {
        $customer = SignageWebCustomer::query()->whereKey((int) $request->query('customer_cd'))->first();
        if ($customer === null) {
            return ['subject' => self::DEFAULT_SUBJECT];
        }

        $appNum = (int) $request->query('app_num') > 0
            ? (int) $request->query('app_num').'台の内線を追加ください。'
            : '台数について連絡をお願いいたします。';

        return [
            'subject' => '【内線追加】顧客番号:'.$customer->cd.'の内線追加に関しまして',
            'name' => $customer->name,
            'message' => view('admin.senalizacion.contact-message', [
                'date' => now()->format('Y年m月d日 H:i:s'),
                'cd' => $customer->cd,
                'appNum' => $appNum,
            ])->render(),
        ];
    }
}
