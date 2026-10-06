<?php

namespace App\Http\Controllers\Admin\Promotions;

use App\Models\TestMailAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Direcciones para los envíos de prueba (テストメールアドレス管理) */
class TestAddressController extends ModuleController
{
    public function index(): View
    {
        return view('admin.promotions.test-addresses', [
            'shops' => $this->shops()->load(['testMailAddresses' => fn ($q) => $q->orderBy('mail')]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'shop_id' => ['required', 'exists:shops,id'],
            'name' => ['nullable', 'string', 'max:100'],
            'mail' => ['required', 'email', 'max:255',
                Rule::unique('test_mail_addresses')->where('shop_id', $request->integer('shop_id'))->whereNull('deleted_at')],
        ], ['mail.unique' => 'Esa dirección ya está en la lista de la tienda.'], ['mail' => 'email']);

        TestMailAddress::create($data);

        return back()->with('status', "Añadida {$data['mail']}.");
    }

    public function destroy(TestMailAddress $address): RedirectResponse
    {
        // Borrado definitivo: así se puede volver a añadir la misma dirección
        $address->forceDelete();

        return back()->with('status', "Quitada {$address->mail}.");
    }
}
