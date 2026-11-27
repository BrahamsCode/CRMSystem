<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Services\Customers\ReaderModeService;
use App\Services\Customers\UserCardService;
use App\Services\Nucleo\LegacyUi;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * カード管理 (legacy `system/category1/config/card_new.php`): modo del lector (運用 / 登録), lista de las
 * tarjetas leídas (tb_user_card) y asignación de una tarjeta a un socio sin tarjeta (生徒選択一覧 → 登録).
 * El legacy hacía todo con POST a la misma página; aquí: GET lista, GET|POST select, POST store y POST mode.
 */
class CardController extends Controller
{
    public function __construct(
        private UserCardService $cards,
        private ReaderModeService $readerMode,
    ) {}

    /** Lista de tarjetas: card_flg 1 割当済 / 0 未割当 / '' 全て; 10 por página */
    public function index(Request $request): View
    {
        $cardFlg = in_array((string) $request->input('card_flg'), ['0', '1'], true) ? (string) $request->input('card_flg') : '';

        return $this->render([
            'cardFlg' => $cardFlg,
            'cards' => $this->cards->cards($cardFlg, $this->int($request, 'page', 1), $this->int($request, 'kensu', 10)),
        ]);
    }

    /** felica_add=up: 生徒選択一覧 para la tarjeta (felica_no, card_no); 20 por página como inc/page_control.php */
    public function select(Request $request): View
    {
        return $this->render([
            'felicaNo' => (string) $request->input('felica_no', ''),
            'cardNo' => (string) $request->input('card_no', ''),
            'candidates' => $this->cards->candidates($this->int($request, 'page', 1), $this->int($request, 'kensu', 20)),
        ]);
    }

    /** felica_set_flg=up: asigna la tarjeta al socio (staff_cd = tb_customer.cd) */
    public function store(Request $request): View|RedirectResponse
    {
        $customer = Customer::query()->find((int) $request->input('staff_cd'));
        $felicaNo = trim((string) $request->input('felica_no', ''));
        abort_if($customer === null || $felicaNo === '', 404);

        try {
            $this->cards->assign($customer, $felicaNo);
        } catch (DomainException) {
            // Mensaje del legacy: en rojo el felica_no que ya tenía el socio (o, si no tenía, la tarjeta pedida)
            return $this->render(['cardError' => filled($customer->felica_no) ? (string) $customer->felica_no : $felicaNo]);
        }

        return to_route('admin.clientes.cards');
    }

    /** felica_mode_up=up desde la カード管理 (運用モード / 登録モード) */
    public function mode(Request $request): RedirectResponse
    {
        $this->readerMode->setMode($request->input('felica_mode'));

        return to_route('admin.clientes.cards');
    }

    private function render(array $data): View
    {
        return view('admin.clientes.config.cards', $data + [
            'siteName' => app(LegacyUi::class)->siteName(),
            'mode' => $this->readerMode->mode(),
            'cardError' => null,
            'cards' => null,
            'cardFlg' => '',
            'candidates' => null,
            'felicaNo' => '',
            'cardNo' => '',
        ]);
    }

    private function int(Request $request, string $key, int $default): int
    {
        $value = $request->input($key);

        return is_scalar($value) && ctype_digit((string) $value) && (int) $value > 0 ? (int) $value : $default;
    }
}
