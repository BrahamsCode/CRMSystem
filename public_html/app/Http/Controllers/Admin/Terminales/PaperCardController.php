<?php

namespace App\Http\Controllers\Admin\Terminales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Terminales\PaperCardControlRequest;
use App\Models\Terminales\PaperCard;
use App\Services\Terminales\LegacyListPaginator;
use App\Services\Terminales\PaperCardQr;
use App\Services\Terminales\PaperCardService;
use App\Services\Terminales\PaperCardStats;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** ペーパーサイネージ (category7/card_signage/*.php) */
class PaperCardController extends Controller
{
    public function __construct(
        private PaperCardService $cards,
        private PaperCardStats $stats,
        private PaperCardQr $qr,
        private LegacyListPaginator $paginator,
    ) {}

    /** list.php; con ?qrCodeDownloadData=&fileName= descarga el QR como <fileName>.jpg (como el legacy) */
    public function index(Request $request): View|Response
    {
        if ($request->has('qrCodeDownloadData')) {
            return response($this->qr->jpeg((string) $request->input('qrCodeDownloadData')), 200, [
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename="'.str_replace(['"', '/', '\\'], '',(string) $request->input('fileName')).'.jpg"',
            ]);
        }

        $query = PaperCard::query()->where('disp_flg', '1')->orderByDesc('cd');

        return view('admin.terminales.paper-cards.index', [
            'cards' => $this->paginator->paginate($query, $request, route('admin.terminales.paper-cards.index')),
            'kensu' => LegacyListPaginator::kensu($request),
            'host' => $request->getHost(),
        ]);
    }

    /** new.php */
    public function create(): View
    {
        return view('admin.terminales.paper-cards.create');
    }

    /** edit.php: fragmento de la ficha (POST cd); sin cd, al listado como el legacy */
    public function edit(Request $request): View|RedirectResponse
    {
        $card = $request->filled('cd') ? PaperCard::withDeleted()->find((int) $request->input('cd')) : null;
        if (! $card) {
            return redirect()->route('admin.terminales.paper-cards.index');
        }

        return view('admin.terminales.paper-cards.edit', ['card' => $card]);
    }

    /** aggregate.php?cd= (sin cd el legacy terminaba en blanco: aquí 404) */
    public function aggregate(Request $request): View
    {
        $card = PaperCard::withDeleted()->find((int) $request->query('cd')) ?? abort(404);

        return view('admin.terminales.paper-cards.aggregate', [
            'card' => $card,
            'summary' => $this->stats->summary($card),
            'hourly' => $this->stats->hourly($card),
            'ranking' => $this->stats->ranking(
                $card,
                (int) $request->query('page') ?: 1,
                LegacyListPaginator::kensu($request),
                route('admin.terminales.paper-cards.aggregate'),
            ),
        ]);
    }

    /** csv_output.php (?cd= opcional) */
    public function csv(Request $request): StreamedResponse
    {
        return $this->stats->csv((int) $request->query('cd') ?: null);
    }

    /** JPEG del QR (?d=), en lugar de /system/category3/mail_mag/qr_img.php?d=…&t=J */
    public function qr(Request $request): Response
    {
        return response($this->qr->jpeg((string) $request->query('d', '')), 200, ['Content-Type' => 'image/jpeg']);
    }

    /** control.php (AJAX): new, update, check, delete, hyouji_switch → texto plano */
    public function control(PaperCardControlRequest $request): Response
    {
        return response($this->cards->handle($request->all()), 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
