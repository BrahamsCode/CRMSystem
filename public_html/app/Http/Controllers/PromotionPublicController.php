<?php

namespace App\Http\Controllers;

use App\Enums\CustomFieldType;
use App\Models\CouponCustomer;
use App\Models\Customer;
use App\Models\CustomerMessage;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Services\Promotions\CouponIssuer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Lo que abre el cliente desde un mensaje: seguimiento de aperturas y clics,
 * su cupón y las encuestas. No requiere sesión: el uid del enlace identifica.
 */
class PromotionPublicController extends Controller
{
    /** Píxel de apertura de los emails HTML */
    public function open(CustomerMessage $recipient): Response
    {
        $this->markOpened($recipient);

        return response(base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'), 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, max-age=0',
        ]);
    }

    /** Toque en la notificación push: cuenta como apertura y lleva a Mi página */
    public function openPush(CustomerMessage $recipient): RedirectResponse
    {
        $this->markOpened($recipient);

        return redirect()->away(config('crm.mypage_url'));
    }

    /** Clic en un enlace: el middleware «signed» garantiza que el destino lo generó el sistema */
    public function click(Request $request, CustomerMessage $recipient): RedirectResponse
    {
        $this->markOpened($recipient);
        $recipient->increment('click_count');
        if (! $recipient->clicked_at) {
            $recipient->forceFill(['clicked_at' => now()])->save();
        }

        return redirect()->away((string) $request->query('u'));
    }

    public function coupon(CouponCustomer $issued): View
    {
        return view('promotions.public.coupon', ['issued' => $issued->load(['coupon.shop', 'customer'])]);
    }

    public function survey(Request $request, Survey $survey): View
    {
        $recipient = $request->filled('r') ? CustomerMessage::where('uid', $request->query('r'))->first() : null;
        $customer = $recipient?->customer;

        return view('promotions.public.survey', [
            'survey' => $survey->load('questions'),
            'recipient' => $recipient,
            'answered' => $customer && $survey->responses()->where('customer_id', $customer->id)->exists(),
        ]);
    }

    public function answer(Request $request, Survey $survey, CouponIssuer $coupons): View|RedirectResponse
    {
        abort_unless($survey->isOpen(), 410, 'La encuesta está cerrada.');

        $recipient = $request->filled('r') ? CustomerMessage::where('uid', $request->input('r'))->first() : null;
        $customer = $recipient?->customer;

        if ($customer && $survey->responses()->where('customer_id', $customer->id)->exists()) {
            return back()->with('status', 'Ya respondiste esta encuesta. ¡Gracias!');
        }

        $answers = $this->validatedAnswers($request, $survey);

        SurveyResponse::create([
            'survey_id' => $survey->id,
            'customer_id' => $customer?->id,
            'message_id' => $recipient?->message_id,
            'answers' => $answers,
            'answered_at' => now(),
        ]);

        // Premio por responder (アンケート回答時プレゼント)
        $issued = $customer && $survey->coupon ? $coupons->issue($survey->coupon, $customer) : null;

        return view('promotions.public.survey-thanks', ['survey' => $survey, 'issued' => $issued]);
    }

    private function markOpened(CustomerMessage $recipient): void
    {
        if (! $recipient->opened_at) {
            $recipient->forceFill(['opened_at' => now()])->save();
        }
    }

    private function validatedAnswers(Request $request, Survey $survey): array
    {
        $answers = [];
        $errors = [];

        foreach ($survey->questions as $q) {
            $value = $request->input('q.' . $q->id);

            if ($q->type === CustomFieldType::Checkbox) {
                $value = array_values(array_intersect((array) $value, $q->options ?? []));
            } elseif ($q->type->hasOptions()) {
                $value = in_array($value, $q->options ?? [], true) ? $value : null;
            } elseif ($q->type === CustomFieldType::Numeric) {
                $value = is_numeric($value) ? $value + 0 : null;
            } else {
                $value = is_string($value) ? mb_substr(trim($value), 0, 5000) : null;
            }

            if ($q->required_flg && ($value === null || $value === '' || $value === [])) {
                $errors['q.' . $q->id] = 'Esta pregunta es obligatoria.';
            }

            $answers[(string) $q->id] = $value;
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return $answers;
    }
}
