<?php

namespace App\Services\Customers;

use App\Models\Customers\Customer;
use DomainException;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * カード管理 de 顧客管理 (legacy `system/category1/config/card_new.php`) sobre `tb_user_card`
 * (tabla de S5; aquí solo se lee: la escritura va por CustomerFelicaService).
 *
 * - Lista de tarjetas leídas por el lector en 登録モード, filtro 割当済 (card_flg 1) / 未割当 (0) / 全て.
 * - 生徒選択一覧: socios activos sin tarjeta (felica_no vacío), ORDER BY touroku_date DESC.
 * - 登録: CustomerFelicaService::attach() (mismas comprobaciones y UPDATE que el legacy).
 */
class UserCardService
{
    public function __construct(private CustomerFelicaService $felica) {}

    /**
     * Tarjetas ORDER BY cd DESC; cada fila con `customer_id` y `customer_name` del socio de `staff_cd`
     * (el legacy lo buscaba en tb_customer sin mirar disp_flg).
     */
    public function cards(string $cardFlg, int $page, int $kensu): LengthAwarePaginator
    {
        $query = DB::table('tb_user_card')
            ->leftJoin('tb_customer', DB::raw('tb_customer.cd::text'), '=', 'tb_user_card.staff_cd')
            ->when($cardFlg !== '', fn ($q) => $q->where('tb_user_card.card_flg', $cardFlg))
            ->select('tb_user_card.*', 'tb_customer.id as customer_id', 'tb_customer.name as customer_name')
            ->orderByDesc('tb_user_card.cd');

        return $this->paginate($query, $page, $kensu);
    }

    /** 生徒選択一覧: tb_customer sin felica_no y disp_flg = '1', ORDER BY touroku_date DESC */
    public function candidates(int $page, int $kensu): LengthAwarePaginator
    {
        $query = Customer::query()
            ->where(fn ($q) => $q->whereNull('felica_no')->orWhere('felica_no', ''))
            ->where('disp_flg', '1')
            ->orderByDesc('touroku_date')
            ->orderByDesc('cd');

        return $this->paginate($query, $page, $kensu);
    }

    /**
     * felica_set_flg=up: asigna la tarjeta al socio.
     *
     * @throws DomainException si el socio ya tiene tarjeta o la tarjeta ya está asignada (mensaje del legacy)
     */
    public function assign(Customer $customer, string $felicaNo): void
    {
        $this->felica->attach($customer, $felicaNo);
    }

    /** Paginación de card_new.php / inc/page_control.php: la página se limita a la última */
    private function paginate($query, int $page, int $kensu): LengthAwarePaginator
    {
        $kensu = max(1, $kensu);
        $base = $query instanceof EloquentBuilder ? $query->toBase() : $query;
        $total = (clone $base)->getCountForPagination();
        $page = min(max(1, $page), max(1, (int) ceil($total / $kensu)));
        $items = $total > 0 ? (clone $query)->forPage($page, $kensu)->get() : collect();

        return new LengthAwarePaginator($items, $total, $kensu, $page);
    }
}
