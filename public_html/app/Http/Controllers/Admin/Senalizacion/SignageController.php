<?php

namespace App\Http\Controllers\Admin\Senalizacion;

use App\Http\Controllers\Controller;
use App\Services\Senalizacion\SignageLegacyView;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

/**
 * Base de la gestión de la señalización web (`signage/`): Gate `senalizacion.manage` y los avisos
 * 登録が完了しました。/削除が完了しました。 del legacy (se muestran tras la redirección).
 */
abstract class SignageController extends Controller
{
    public const SAVED = '登録が完了しました。';

    public const DELETED = '削除が完了しました。';

    public function __construct(protected readonly SignageLegacyView $legacyView)
    {
        Gate::authorize('senalizacion.manage');
    }

    protected function done(string $url, string $message): RedirectResponse
    {
        return redirect($url)->with('signage_success', [$message]);
    }

    protected function failed(string $url, string $message): RedirectResponse
    {
        return redirect($url)->with('signage_error', [$message]);
    }
}
