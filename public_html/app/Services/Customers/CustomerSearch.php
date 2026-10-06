<?php

namespace App\Services\Customers;

use App\Enums\AddressType;
use App\Enums\MailMagazine;
use App\Enums\Occupation;
use App\Enums\Sex;
use App\Models\CustomCategory;
use App\Models\Customer;
use App\Models\Shop;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Búsqueda de clientes por filtros.
 *
 * La comparten «Buscar clientes» (módulo 1) y la selección de destinatarios de
 * los envíos del módulo de promociones (発行条件選択), que en el legacy tienen
 * exactamente los mismos filtros. Los filtros llegan como un array plano, el
 * mismo que envía el formulario por GET y el que se guarda en messages.filters.
 *
 * Reglas del legacy que se conservan:
 * - «Lógica» (Y / O) y «Resultado» (coinciden / no coinciden) combinan los
 *   filtros de perfil, visitas y ventas.
 * - «Estado de registro» queda fuera de esa combinación: siempre se aplica tal
 *   cual (por defecto, solo registrados).
 */
class CustomerSearch
{
    /** Filtros que no se combinan con Y / O */
    private const OUTSIDE = ['status', 'logic', 'negate', 'per_page', 'page', 'sort'];

    /** Valores del filtro de estado */
    public const STATUS_REGISTERED = '1';
    public const STATUS_WITHDRAWN = '0';
    public const STATUS_DELETED = 'deleted';
    public const STATUS_ALL = 'all';

    /** Reglas de validación de los filtros; también sirven para limpiar lo guardado en jsonb */
    public static function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:50'],
            // Selección manual en la lista de resultados
            'codes' => ['nullable', 'array', 'max:1000'],
            'codes.*' => ['string', 'max:50'],
            'tel' => ['nullable', 'string', 'max:50'],
            'name' => ['nullable', 'string', 'max:100'],
            'mail' => ['nullable', 'string', 'max:255'],
            'shop_ids' => ['nullable', 'array'],
            'shop_ids.*' => ['integer'],
            'birth_month' => ['nullable', 'integer', 'between:1,12'],
            'birth_day' => ['nullable', 'integer', 'between:1,31'],
            'sex' => ['nullable', 'integer', 'in:0,1,2,9'],
            'age_min' => ['nullable', 'integer', 'min:0', 'max:150'],
            'age_max' => ['nullable', 'integer', 'min:0', 'max:150'],
            'joined_from' => ['nullable', 'date'],
            'joined_to' => ['nullable', 'date'],
            'status' => ['nullable', 'in:0,1,deleted,all'],
            'mail_magazine' => ['nullable', 'integer', 'in:1,2,3'],
            'occupation' => ['nullable', 'integer'],
            'address_type' => ['nullable', 'integer'],
            'bounce_min' => ['nullable', 'integer', 'min:0'],
            'bounce_max' => ['nullable', 'integer', 'min:0'],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer'],
            'has_terminal' => ['nullable', 'in:0,1'],
            'spouse' => ['nullable', 'in:0,1'],
            'wedding_from' => ['nullable', 'date'],
            'wedding_to' => ['nullable', 'date'],
            'custom' => ['nullable', 'array'],
            'near_shop_id' => ['nullable', 'integer'],
            'near_km' => ['nullable', 'numeric', 'min:0.1', 'max:100'],
            'near_hours' => ['nullable', 'integer', 'min:1', 'max:720'],
            'visit_period' => ['nullable', 'in:7,30,90,180,365,custom,all'],
            'visit_from' => ['nullable', 'date'],
            'visit_to' => ['nullable', 'date'],
            'visit_shop_id' => ['nullable', 'integer'],
            'visit_day' => ['nullable', 'integer', 'between:1,31'],
            'visit_dow' => ['nullable', 'integer', 'between:0,6'],
            'visits_min' => ['nullable', 'integer', 'min:0'],
            'visits_max' => ['nullable', 'integer', 'min:0'],
            'last_visit_days' => ['nullable', 'integer', 'min:0'],
            'cycle_min' => ['nullable', 'integer', 'min:0'],
            'cycle_max' => ['nullable', 'integer', 'min:0'],
            'next_visit_from' => ['nullable', 'date'],
            'next_visit_to' => ['nullable', 'date'],
            'sale_shop_id' => ['nullable', 'integer'],
            'sale_from' => ['nullable', 'date'],
            'sale_to' => ['nullable', 'date'],
            'avg_amount_min' => ['nullable', 'integer', 'min:0'],
            'avg_amount_max' => ['nullable', 'integer', 'min:0'],
            'coupon_id' => ['nullable', 'integer'],
            'logic' => ['nullable', 'in:and,or'],
            'negate' => ['nullable', 'in:0,1'],
        ];
    }

    /** Quita los filtros vacíos para que lo guardado y la URL queden limpios */
    public static function clean(array $filters): array
    {
        $clean = [];

        foreach ($filters as $key => $value) {
            if ($key === 'custom') {
                $custom = [];
                foreach ((array) $value as $category => $fields) {
                    $fields = array_filter((array) $fields, fn ($v) => $v !== null && $v !== '' && $v !== []);
                    if ($fields !== []) {
                        $custom[$category] = $fields;
                    }
                }
                if ($custom !== []) {
                    $clean['custom'] = $custom;
                }

                continue;
            }

            if (is_array($value)) {
                $value = array_values(array_filter($value, fn ($v) => $v !== null && $v !== ''));
            }

            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            $clean[$key] = $value;
        }

        // Valores que son el comportamiento por defecto: no hace falta guardarlos
        if (($clean['logic'] ?? 'and') === 'and') {
            unset($clean['logic']);
        }
        if (($clean['negate'] ?? '0') === '0') {
            unset($clean['negate']);
        }

        return $clean;
    }

    public function query(array $filters): Builder
    {
        $filters = self::clean($filters);
        $query = Customer::query();

        $this->applyStatus($query, $filters['status'] ?? self::STATUS_REGISTERED);

        $conditions = $this->conditions($filters);

        if ($conditions !== []) {
            $or = ($filters['logic'] ?? 'and') === 'or';
            $group = function (Builder $q) use ($conditions, $or) {
                foreach ($conditions as $condition) {
                    $or ? $q->orWhere($condition) : $q->where($condition);
                }
            };

            if (($filters['negate'] ?? '0') === '1') {
                // NOT IN y no whereNot(): con whereNot un dato vacío (NULL) no cuenta como
                // «no coincide» y esos clientes desaparecerían de los dos resultados
                $query->whereNotIn('customers.id', Customer::withTrashed()->select('customers.id')->where($group));
            } else {
                $query->where($group);
            }
        }

        return $query;
    }

    public function count(array $filters): int
    {
        return $this->query($filters)->count();
    }

    /** Nº de filtros activos, sin contar estado, lógica ni resultado */
    public static function activeCount(array $filters): int
    {
        $filters = self::clean($filters);
        $n = count(array_diff_key($filters, array_flip(array_merge(self::OUTSIDE, ['custom']))));

        foreach ($filters['custom'] ?? [] as $fields) {
            $n += count($fields);
        }

        return $n;
    }

    /**
     * Resumen legible de los filtros (抽出条件 en el legacy): se muestra en la
     * lista de envíos y en el detalle de cada uno.
     *
     * @return list<array{0: string, 1: string}> pares [etiqueta, valor]
     */
    public function summary(array $filters): array
    {
        $f = self::clean($filters);
        $out = [];
        $range = fn ($a, $b, $unit = '') => trim(($a ?? '…') . ' – ' . ($b ?? '…') . ' ' . $unit);
        $shopNames = fn (array $ids) => Shop::whereIn('id', $ids)->pluck('name')->join(', ');

        $out[] = ['Estado de registro', match ($f['status'] ?? self::STATUS_REGISTERED) {
            self::STATUS_WITHDRAWN => 'Dado de baja',
            self::STATUS_DELETED => 'Eliminado',
            self::STATUS_ALL => 'Todos',
            default => 'Registrado',
        }];

        $simple = [
            'q' => 'Búsqueda', 'code' => 'Nº de socio', 'tel' => 'Teléfono', 'name' => 'Nombre', 'mail' => 'Email',
        ];
        foreach ($simple as $key => $label) {
            if (isset($f[$key])) {
                $out[] = [$label, $f[$key]];
            }
        }

        if (isset($f['codes'])) {
            $out[] = ['Selección manual', count($f['codes']) . ' clientes'];
        }
        if (isset($f['shop_ids'])) {
            $out[] = ['Tienda de registro', $shopNames($f['shop_ids'])];
        }
        if (isset($f['birth_month']) || isset($f['birth_day'])) {
            $out[] = ['Cumpleaños', ($f['birth_day'] ?? '–') . '/' . ($f['birth_month'] ?? '–')];
        }
        if (isset($f['sex'])) {
            $out[] = ['Sexo', Sex::tryFrom((int) $f['sex'])?->label() ?? '—'];
        }
        if (isset($f['age_min']) || isset($f['age_max'])) {
            $out[] = ['Edad', $range($f['age_min'] ?? null, $f['age_max'] ?? null, 'años')];
        }
        if (isset($f['joined_from']) || isset($f['joined_to'])) {
            $out[] = ['Fecha de alta', $range($f['joined_from'] ?? null, $f['joined_to'] ?? null)];
        }
        if (isset($f['mail_magazine'])) {
            $out[] = ['Newsletter', MailMagazine::from((int) $f['mail_magazine'])->label()];
        }
        if (isset($f['occupation'])) {
            $out[] = ['Ocupación', Occupation::tryFrom((int) $f['occupation'])?->label() ?? '—'];
        }
        if (isset($f['address_type'])) {
            $out[] = ['Tipo de dirección', AddressType::tryFrom((int) $f['address_type'])?->label() ?? '—'];
        }
        if (isset($f['bounce_min']) || isset($f['bounce_max'])) {
            $out[] = ['Correos no entregados', $range($f['bounce_min'] ?? null, $f['bounce_max'] ?? null)];
        }
        if (isset($f['group_ids'])) {
            $out[] = ['Grupo', DB::table('customer_groups')->whereIn('id', $f['group_ids'])->pluck('name')->join(', ')];
        }
        if (isset($f['has_terminal'])) {
            $out[] = ['Terminal', $f['has_terminal'] === '1' ? 'Con terminal' : 'Sin terminal'];
        }
        if (isset($f['spouse'])) {
            $out[] = ['Cónyuge', $f['spouse'] === '1' ? 'Sí' : 'No'];
        }
        if (isset($f['wedding_from']) || isset($f['wedding_to'])) {
            $out[] = ['Aniversario de boda', $range($f['wedding_from'] ?? null, $f['wedding_to'] ?? null)];
        }
        if (isset($f['near_shop_id'])) {
            $out[] = ['Cerca de', Shop::whereKey($f['near_shop_id'])->value('name') . ' · ' . ($f['near_km'] ?? 1) . ' km · últimas ' . ($f['near_hours'] ?? 24) . ' h'];
        }
        if (isset($f['visit_period'])) {
            $out[] = ['Periodo de visitas', match ($f['visit_period']) {
                'all' => 'Todo el historial',
                'custom' => $range($f['visit_from'] ?? null, $f['visit_to'] ?? null),
                default => 'Últimos ' . $f['visit_period'] . ' días',
            }];
        }
        if (isset($f['visit_shop_id'])) {
            $out[] = ['Tienda visitada', Shop::whereKey($f['visit_shop_id'])->value('name')];
        }
        if (isset($f['visits_min']) || isset($f['visits_max'])) {
            $out[] = ['Nº de visitas', $range($f['visits_min'] ?? null, $f['visits_max'] ?? null, 'veces')];
        }
        if (isset($f['visit_day'])) {
            $out[] = ['Día de la visita', 'Día ' . $f['visit_day']];
        }
        if (isset($f['visit_dow'])) {
            $out[] = ['Día de la semana', ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'][(int) $f['visit_dow']]];
        }
        if (isset($f['last_visit_days'])) {
            $out[] = ['Sin visitar desde hace', $f['last_visit_days'] . ' días o más'];
        }
        if (isset($f['cycle_min']) || isset($f['cycle_max'])) {
            $out[] = ['Ciclo medio', $range($f['cycle_min'] ?? null, $f['cycle_max'] ?? null, 'días')];
        }
        if (isset($f['next_visit_from']) || isset($f['next_visit_to'])) {
            $out[] = ['Próxima visita', $range($f['next_visit_from'] ?? null, $f['next_visit_to'] ?? null)];
        }
        if (isset($f['sale_from']) || isset($f['sale_to']) || isset($f['sale_shop_id'])) {
            $out[] = ['Ventas', trim(($f['sale_shop_id'] ?? null ? Shop::whereKey($f['sale_shop_id'])->value('name') . ' · ' : '') . $range($f['sale_from'] ?? null, $f['sale_to'] ?? null))];
        }
        if (isset($f['avg_amount_min']) || isset($f['avg_amount_max'])) {
            $out[] = ['Ticket promedio', $range($f['avg_amount_min'] ?? null, $f['avg_amount_max'] ?? null)];
        }
        if (isset($f['coupon_id'])) {
            $out[] = ['Cupón recibido', DB::table('coupons')->where('id', $f['coupon_id'])->value('name')];
        }

        if (isset($f['custom'])) {
            $categories = CustomCategory::with('fields')->whereIn('slug', array_keys($f['custom']))->get()->keyBy('slug');
            foreach ($f['custom'] as $slug => $fields) {
                foreach ($fields as $field => $value) {
                    $category = $categories->get($slug);
                    $label = ($category?->name ?? $slug) . ' · ' . ($category?->fields->firstWhere('slug', $field)?->name ?? $field);
                    $out[] = [$label, is_array($value) ? implode(', ', $value) : (string) $value];
                }
            }
        }

        if (count($out) > 2) {
            $out[] = ['Lógica', ($f['logic'] ?? 'and') === 'or' ? 'Cumple alguno (O)' : 'Cumple todos (Y)'];
        }
        if (($f['negate'] ?? '0') === '1') {
            $out[] = ['Resultado', 'Los que NO coinciden'];
        }

        return $out;
    }

    private function applyStatus(Builder $query, string $status): void
    {
        match ($status) {
            self::STATUS_WITHDRAWN => $query->where('status', 0),
            self::STATUS_DELETED => $query->onlyTrashed(),
            self::STATUS_ALL => $query->withTrashed(),
            default => $query->where('status', 1),
        };
    }

    /**
     * Una closure por filtro activo. Cada una restringe el builder que recibe,
     * así se pueden encadenar con where() o con orWhere() según la lógica.
     *
     * @return list<Closure>
     */
    private function conditions(array $f): array
    {
        $c = [];
        $like = fn (string $value) => '%' . addcslashes($value, '%_\\') . '%';

        if (isset($f['q'])) {
            $term = $like($f['q']);
            $digits = preg_replace('/\D+/', '', mb_convert_kana($f['q'], 'n'));
            $telTerm = $digits !== '' ? $like($digits) : $term;
            $c[] = fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('code', 'ilike', $term)
                ->orWhere('management_no', 'ilike', $term)
                ->orWhereRaw("concat_ws(' ', last_name, first_name) ilike ?", [$term])
                ->orWhereRaw("concat_ws(' ', last_name_kana, first_name_kana) ilike ?", [$term])
                ->orWhere('tel1', 'ilike', $telTerm)->orWhere('tel2', 'ilike', $telTerm)
                ->orWhere('mail1', 'ilike', $term)->orWhere('mail2', 'ilike', $term)->orWhere('mail3', 'ilike', $term));
        }
        if (isset($f['code'])) {
            $c[] = fn (Builder $q) => $q->where('code', $f['code']);
        }
        if (isset($f['codes'])) {
            $c[] = fn (Builder $q) => $q->whereIn('code', $f['codes']);
        }
        if (isset($f['tel'])) {
            // Los teléfonos se guardan solo con dígitos: se busca igual, aunque escriban guiones
            $term = $like(preg_replace('/\D+/', '', mb_convert_kana($f['tel'], 'n')));
            $c[] = fn (Builder $q) => $q->where(fn (Builder $w) => $w->where('tel1', 'ilike', $term)->orWhere('tel2', 'ilike', $term));
        }
        if (isset($f['name'])) {
            // «Apellido Nombre» con espacio busca por nombre completo, como en el legacy
            $term = $like(preg_replace('/\s+/u', ' ', trim($f['name'])));
            $c[] = fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->whereRaw("concat_ws(' ', last_name, first_name) ilike ?", [$term])
                ->orWhereRaw("concat_ws(' ', last_name_kana, first_name_kana) ilike ?", [$term]));
        }
        if (isset($f['mail'])) {
            $term = $like($f['mail']);
            $c[] = fn (Builder $q) => $q->where(fn (Builder $w) => $w->where('mail1', 'ilike', $term)->orWhere('mail2', 'ilike', $term)->orWhere('mail3', 'ilike', $term));
        }
        if (isset($f['shop_ids'])) {
            $c[] = fn (Builder $q) => $q->whereIn('shop_id', $f['shop_ids']);
        }
        if (isset($f['birth_month'])) {
            $c[] = fn (Builder $q) => $q->whereRaw('extract(month from birth_date) = ?', [(int) $f['birth_month']]);
        }
        if (isset($f['birth_day'])) {
            $c[] = fn (Builder $q) => $q->whereRaw('extract(day from birth_date) = ?', [(int) $f['birth_day']]);
        }
        if (isset($f['sex'])) {
            // 0 = no conocido (ISO 5218): incluye a quienes no lo tienen registrado
            $c[] = (int) $f['sex'] === 0
                ? fn (Builder $q) => $q->where(fn (Builder $w) => $w->whereNull('sex')->orWhere('sex', 0))
                : fn (Builder $q) => $q->where('sex', (int) $f['sex']);
        }
        if (isset($f['age_min'])) {
            // Edad >= N  ⇔  nació en o antes de hoy - N años
            $limit = now()->subYears((int) $f['age_min'])->toDateString();
            $c[] = fn (Builder $q) => $q->whereDate('birth_date', '<=', $limit);
        }
        if (isset($f['age_max'])) {
            // Edad <= N  ⇔  nació después de hoy - (N + 1) años
            $limit = now()->subYears((int) $f['age_max'] + 1)->toDateString();
            $c[] = fn (Builder $q) => $q->whereDate('birth_date', '>', $limit);
        }
        if (isset($f['joined_from'])) {
            $c[] = fn (Builder $q) => $q->whereDate('created_at', '>=', $f['joined_from']);
        }
        if (isset($f['joined_to'])) {
            $c[] = fn (Builder $q) => $q->whereDate('created_at', '<=', $f['joined_to']);
        }
        if (isset($f['mail_magazine'])) {
            $c[] = fn (Builder $q) => $q->where('mail_magazine', (int) $f['mail_magazine']);
        }
        if (isset($f['occupation'])) {
            $c[] = fn (Builder $q) => $q->where('occupation', (int) $f['occupation']);
        }
        if (isset($f['address_type'])) {
            $c[] = fn (Builder $q) => $q->where('address_type', (int) $f['address_type']);
        }
        if (isset($f['bounce_min'])) {
            $c[] = fn (Builder $q) => $q->where('bounce_count', '>=', (int) $f['bounce_min']);
        }
        if (isset($f['bounce_max'])) {
            $c[] = fn (Builder $q) => $q->where('bounce_count', '<=', (int) $f['bounce_max']);
        }
        if (isset($f['group_ids'])) {
            $c[] = fn (Builder $q) => $q->whereIn('customer_group_id', $f['group_ids']);
        }
        if (isset($f['has_terminal'])) {
            $c[] = $f['has_terminal'] === '1'
                ? fn (Builder $q) => $q->whereNotNull('terminal_id')
                : fn (Builder $q) => $q->whereNull('terminal_id');
        }
        if (isset($f['spouse'])) {
            $c[] = fn (Builder $q) => $q->where('spouse_flg', (int) $f['spouse']);
        }
        if (isset($f['wedding_from'])) {
            $c[] = fn (Builder $q) => $q->whereDate('wedding_date', '>=', $f['wedding_from']);
        }
        if (isset($f['wedding_to'])) {
            $c[] = fn (Builder $q) => $q->whereDate('wedding_date', '<=', $f['wedding_to']);
        }

        foreach ($f['custom'] ?? [] as $category => $fields) {
            foreach ($fields as $field => $value) {
                $c[] = $this->customCondition((string) $category, (string) $field, $value);
            }
        }

        if (isset($f['near_shop_id'])) {
            $c[] = $this->nearShopCondition((int) $f['near_shop_id'], (float) ($f['near_km'] ?? 1), (int) ($f['near_hours'] ?? 24));
        }

        if ($visits = $this->visitCondition($f)) {
            $c[] = $visits;
        }
        if (isset($f['last_visit_days'])) {
            $limit = now()->subDays((int) $f['last_visit_days'])->toDateString();
            $c[] = fn (Builder $q) => $q->whereDate('last_visit_date', '<=', $limit);
        }
        if (isset($f['cycle_min'])) {
            $c[] = fn (Builder $q) => $q->where('average_visit_cycle', '>=', (int) $f['cycle_min']);
        }
        if (isset($f['cycle_max'])) {
            $c[] = fn (Builder $q) => $q->where('average_visit_cycle', '<=', (int) $f['cycle_max']);
        }
        if (isset($f['next_visit_from'])) {
            $c[] = fn (Builder $q) => $q->whereDate('next_visit_date', '>=', $f['next_visit_from']);
        }
        if (isset($f['next_visit_to'])) {
            $c[] = fn (Builder $q) => $q->whereDate('next_visit_date', '<=', $f['next_visit_to']);
        }

        if ($sales = $this->salesCondition($f)) {
            $c[] = $sales;
        }

        if (isset($f['coupon_id'])) {
            $c[] = fn (Builder $q) => $q->whereExists(fn ($s) => $s->from('coupon_customer')
                ->whereColumn('coupon_customer.customer_id', 'customers.id')
                ->where('coupon_customer.coupon_id', (int) $f['coupon_id'])
                ->whereNull('coupon_customer.deleted_at'));
        }

        return $c;
    }

    /**
     * Campo personalizado dentro de customers.custom_data (jsonb).
     * Las opciones y números se comparan exactos; el texto, por contenido.
     */
    private function customCondition(string $category, string $field, mixed $value): Closure
    {
        $path = '{' . $category . ',' . $field . '}';

        if (is_array($value)) {
            // Casillas: basta con que coincida una de las marcadas
            return fn (Builder $q) => $q->where(function (Builder $w) use ($path, $value) {
                foreach ($value as $v) {
                    $w->orWhereRaw('custom_data #>> ? = ?', [$path, (string) $v])
                        ->orWhereRaw('(custom_data #> ?) @> ?::jsonb', [$path, json_encode([(string) $v])]);
                }
            });
        }

        $term = '%' . addcslashes((string) $value, '%_\\') . '%';

        return fn (Builder $q) => $q->whereRaw('custom_data #>> ? ilike ?', [$path, $term]);
    }

    /** Estuvo a menos de X km de la tienda en las últimas N horas (fórmula del haversine) */
    private function nearShopCondition(int $shopId, float $km, int $hours): Closure
    {
        $shop = Shop::find($shopId);

        if (! $shop || $shop->latitude === null || $shop->longitude === null) {
            // Sin coordenadas de la tienda no hay a quién encontrar
            return fn (Builder $q) => $q->whereRaw('false');
        }

        $since = now()->subHours($hours);

        return fn (Builder $q) => $q->whereExists(fn ($s) => $s->from('customer_locations')
            ->whereColumn('customer_locations.customer_id', 'customers.id')
            ->where('customer_locations.recorded_at', '>=', $since)
            ->whereNull('customer_locations.deleted_at')
            ->whereRaw(
                '6371 * 2 * asin(sqrt(power(sin(radians(latitude - ?) / 2), 2) + cos(radians(?)) * cos(radians(latitude)) * power(sin(radians(longitude - ?) / 2), 2))) <= ?',
                [(float) $shop->latitude, (float) $shop->latitude, (float) $shop->longitude, $km]
            ));
    }

    /**
     * Historial de visitas. Igual que en el legacy, el bloque solo cuenta si hay
     * periodo: sin él se ignora (los demás campos dependen del periodo).
     */
    private function visitCondition(array $f): ?Closure
    {
        if (! isset($f['visit_period'])) {
            return null;
        }

        [$from, $to] = $this->period($f['visit_period'], $f['visit_from'] ?? null, $f['visit_to'] ?? null);

        // Condiciones sobre la tabla visits; se reutilizan en exists y en count
        $visits = function ($s) use ($f, $from, $to) {
            $s->from('visits')
                ->whereColumn('visits.customer_id', 'customers.id')
                ->whereNull('visits.deleted_at');

            if ($from) {
                $s->where('visits.visited_at', '>=', $from);
            }
            if ($to) {
                $s->where('visits.visited_at', '<=', $to);
            }
            if (isset($f['visit_shop_id'])) {
                $s->where('visits.shop_id', (int) $f['visit_shop_id']);
            }
            if (isset($f['visit_day'])) {
                $s->whereRaw('extract(day from visits.visited_at) = ?', [(int) $f['visit_day']]);
            }
            if (isset($f['visit_dow'])) {
                $s->whereRaw('extract(dow from visits.visited_at) = ?', [(int) $f['visit_dow']]);
            }
        };
        $count = function ($s) use ($visits) {
            $visits($s);
            $s->selectRaw('count(*)');
        };

        $min = isset($f['visits_min']) ? (int) $f['visits_min'] : null;
        $max = isset($f['visits_max']) ? (int) $f['visits_max'] : null;

        return function (Builder $q) use ($visits, $count, $min, $max) {
            // Sin mínimo ni máximo: basta con haber venido al menos una vez en el periodo
            if ($min === null && $max === null) {
                $q->whereExists($visits);

                return;
            }
            // where(closure, operador, valor) compara contra el resultado de la subconsulta
            if ($min !== null) {
                $q->where($count, '>=', $min);
            }
            if ($max !== null) {
                $q->where($count, '<=', $max);
            }
        };
    }

    /**
     * Ventas: mientras no exista el módulo de caja, el importe de cada venta es
     * el importe de consumo de la visita (visits.amount).
     */
    private function salesCondition(array $f): ?Closure
    {
        $hasSales = isset($f['sale_shop_id']) || isset($f['sale_from']) || isset($f['sale_to'])
            || isset($f['avg_amount_min']) || isset($f['avg_amount_max']);

        if (! $hasSales) {
            return null;
        }

        $sales = function ($s) use ($f) {
            $s->from('visits')
                ->whereColumn('visits.customer_id', 'customers.id')
                ->whereNull('visits.deleted_at')
                ->where('visits.amount', '>', 0);

            if (isset($f['sale_shop_id'])) {
                $s->where('visits.shop_id', (int) $f['sale_shop_id']);
            }
            if (isset($f['sale_from'])) {
                $s->where('visits.visited_at', '>=', Carbon::parse($f['sale_from']));
            }
            if (isset($f['sale_to'])) {
                $s->where('visits.visited_at', '<=', Carbon::parse($f['sale_to']));
            }
        };

        return function (Builder $q) use ($sales, $f) {
            $q->whereExists($sales);

            $avg = function ($s) use ($sales) {
                $sales($s);
                $s->selectRaw('avg(visits.amount)');
            };

            if (isset($f['avg_amount_min'])) {
                $q->where($avg, '>=', (int) $f['avg_amount_min']);
            }
            if (isset($f['avg_amount_max'])) {
                $q->where($avg, '<=', (int) $f['avg_amount_max']);
            }
        };
    }

    /** @return array{0: ?Carbon, 1: ?Carbon} */
    private function period(string $period, ?string $from, ?string $to): array
    {
        return match ($period) {
            'all' => [null, null],
            'custom' => [$from ? Carbon::parse($from)->startOfDay() : null, $to ? Carbon::parse($to)->endOfDay() : null],
            default => [now()->subDays((int) $period)->startOfDay(), null],
        };
    }
}
