<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Requests\Admin\StoreCustomerRequest;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Shop;
use App\Models\VisitMotive;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CustomerController extends ModuleController
{
    /** Resumen del módulo: altas y bajas por periodo */
    public function index(): View
    {
        $today = now();

        return view('admin.customers.index', [
            'totalSignups' => Customer::count(),
            'monthSignups' => Customer::where('created_at', '>=', $today->copy()->startOfMonth())->count(),
            'todaySignups' => Customer::whereDate('created_at', $today)->count(),
            'totalWithdrawn' => Customer::where('status', 0)->count(),
            'monthWithdrawn' => Customer::where('status', 0)
                ->where('updated_at', '>=', $today->copy()->startOfMonth())->count(),
            'todayWithdrawn' => Customer::where('status', 0)->whereDate('updated_at', $today)->count(),
            'todayCustomers' => Customer::whereDate('created_at', $today)->latest()->take(5)->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.customers.create', [
            'shops' => Shop::active()->orderBy('name')->get(),
            'groups' => CustomerGroup::active()->ordered()->get(),
            'motives' => VisitMotive::active()->ordered()->get(),
            'nextCode' => Customer::nextCode(),
        ]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $customer = Customer::create(
            $request->validated() + ['custom_data' => $this->customData($request->input('custom', []))]
        );

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('status', "Cliente registrado. Nº de socio {$customer->code}.");
    }

    public function show(Customer $customer): View
    {
        return view('admin.customers.show', [
            'customer' => $customer->load([
                'shop', 'terminal', 'group', 'visitMotive',
                'amountRank', 'visitRank', 'referrer',
            ]),
        ]);
    }

    public function search(): View
    {
        return view('admin.customers.search', [
            'shops' => Shop::active()->orderBy('name')->get(),
            'customers' => Customer::with('shop')->orderByDesc('code')->paginate(10),
            // Desglose por operador de correo, igual que el legacy
            'byCarrier' => Customer::selectRaw("coalesce(address_type::text, '') as tipo, count(*) as total")
                ->groupBy('tipo')
                ->pluck('total', 'tipo'),
        ]);
    }

    /**
     * Campos personalizados del formulario. Se descartan las categorías que
     * llegan completamente vacías para no guardar ruido en el jsonb.
     */
    private function customData(array $custom): object
    {
        $clean = [];

        foreach ($custom as $category => $fields) {
            $fields = array_filter((array) $fields, fn ($v) => $v !== null && $v !== '');

            if ($fields !== []) {
                $clean[$category] = $fields;
            }
        }

        // stdClass y no array: un array vacío se serializa como [] y los
        // operadores JSONB esperan un objeto.
        return (object) $clean;
    }
}
