<?php

namespace App\Http\Controllers\Admin\Customers;

use App\Http\Requests\Admin\StoreCustomerRequest;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Shop;
use App\Models\VisitMotive;
use App\Services\Customers\CustomerCsvExporter;
use App\Services\Customers\CustomerSearch;
use App\Services\Customers\FilterOptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $customer = DB::transaction(function () use ($request) {
            $customer = Customer::create(
                $request->customerData() + ['custom_data' => $this->customData($request->input('custom', []))]
            );

            if ($company = $request->companyData()) {
                $customer->company()->create($company);
            }

            return $customer;
        });

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('status', "Cliente registrado. Nº de socio {$customer->code}.");
    }

    public function show(Customer $customer): View
    {
        return view('admin.customers.show', [
            'customer' => $customer->load([
                'shop', 'terminal', 'group', 'visitMotive', 'company',
                'amountRank', 'visitRank', 'referrer',
                'coupons' => fn ($q) => $q->with('coupon')->take(30),
                'stamps' => fn ($q) => $q->take(30),
                'points' => fn ($q) => $q->take(30),
            ]),
        ]);
    }

    public function search(Request $request, CustomerSearch $search): View
    {
        $filters = $this->filters($request);
        $perPage = in_array((int) $request->input('per_page'), [10, 25, 50, 100], true) ? (int) $request->input('per_page') : 25;

        return view('admin.customers.search', [
            'filters' => $filters,
            'summary' => $search->summary($filters),
            'activeCount' => CustomerSearch::activeCount($filters),
            'perPage' => $perPage,
            'customers' => $search->query($filters)
                ->with(['shop', 'terminal'])
                ->orderByDesc('code')
                ->paginate($perPage)
                ->withQueryString(),
            // Desglose por tipo de dirección (operador del email) de los resultados, igual que el legacy
            'byCarrier' => $search->query($filters)
                ->selectRaw("coalesce(address_type::text, '') as tipo, count(*) as total")
                ->groupBy('tipo')
                ->pluck('total', 'tipo'),
        ] + FilterOptions::for($this->shop()?->id));
    }

    /** CSV de los resultados: normal (columnas de «Campos y CSV») o para correo postal */
    public function export(Request $request, CustomerSearch $search, CustomerCsvExporter $exporter): StreamedResponse
    {
        $query = $search->query($this->filters($request));
        $stamp = now()->format('Ymd_His');

        return $request->input('format') === 'mailing'
            ? $exporter->exportMailing($query, "clientes_correo_{$stamp}.csv")
            : $exporter->export($query, $this->shop()?->id, "clientes_{$stamp}.csv");
    }

    /** Filtros de la URL, validados con las mismas reglas que usa promociones */
    private function filters(Request $request): array
    {
        $validator = Validator::make($request->query(), CustomerSearch::rules());

        // Un filtro mal escrito en la URL se descarta en vez de romper la página
        return CustomerSearch::clean(array_intersect_key($request->query(), $validator->valid()));
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
