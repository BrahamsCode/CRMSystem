<?php

namespace App\Services\Customers;

use App\Models\Customer;
use App\Models\FieldSetting;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV de clientes (CSV出力). Las columnas son los campos que tienen «Exportar
 * CSV» activado en «Campos y CSV» para la tienda.
 *
 * El CSV para correo directo (宛名CSV) es fijo: lo que hace falta para
 * imprimir etiquetas de dirección.
 */
class CustomerCsvExporter
{
    /** Código del catálogo config('crm.customer_fields') → valor de la columna */
    private function resolvers(): array
    {
        $date = fn ($d) => $d?->format('Y-m-d') ?? '';

        return [
            'num' => fn (Customer $c) => $c->code,
            'mgmt' => fn (Customer $c) => $c->management_no,
            'store' => fn (Customer $c) => $c->shop?->name,
            'ptype' => fn (Customer $c) => $c->type?->label(),
            'joined' => fn (Customer $c) => $c->created_at?->format('Y-m-d H:i'),
            'status' => fn (Customer $c) => $c->trashed() ? 'Eliminado' : $c->status?->label(),
            'news' => fn (Customer $c) => $c->mail_magazine_flg?->label(),
            'updated' => fn (Customer $c) => $c->updated_at?->format('Y-m-d H:i'),
            'group' => fn (Customer $c) => $c->group?->name,
            'name' => fn (Customer $c) => $c->full_name,
            'namek' => fn (Customer $c) => $c->full_name_kana,
            'birth' => fn (Customer $c) => $date($c->birth_date),
            'age' => fn (Customer $c) => $c->age(),
            'sex' => fn (Customer $c) => $c->sex?->label(),
            'blood' => fn (Customer $c) => $c->blood_type,
            'job' => fn (Customer $c) => $c->occupation?->label(),
            'tel' => fn (Customer $c) => $c->tel1,
            'mobile' => fn (Customer $c) => $c->tel2,
            'fax' => fn (Customer $c) => $c->fax,
            'mail1' => fn (Customer $c) => $c->mail1,
            'mail2' => fn (Customer $c) => $c->mail2,
            'pmail' => fn (Customer $c) => $c->mail3,
            'zip' => fn (Customer $c) => $c->zip,
            'pref' => fn (Customer $c) => $c->pref,
            'city' => fn (Customer $c) => $c->city,
            'cityk' => fn (Customer $c) => $c->city_kana,
            'street' => fn (Customer $c) => $c->street_address,
            'bldg' => fn (Customer $c) => $c->building,
            'bldgk' => fn (Customer $c) => $c->building_kana,
            'wname' => fn (Customer $c) => $c->company_name,
            'wnamek' => fn (Customer $c) => $c->company_name_kana,
            'wind' => fn (Customer $c) => $c->company_industry?->label(),
            'wtel' => fn (Customer $c) => $c->company_tel,
            'wfax' => fn (Customer $c) => $c->company_fax,
            'founded' => fn (Customer $c) => $date($c->company_founded_date),
            'capital' => fn (Customer $c) => $c->company_capital,
            'industry' => fn (Customer $c) => $c->industry?->label(),
            'dept' => fn (Customer $c) => $c->company_dept,
            'rep' => fn (Customer $c) => trim("{$c->rep_last_name} {$c->rep_first_name}"),
            'repk' => fn (Customer $c) => trim("{$c->rep_last_name_kana} {$c->rep_first_name_kana}"),
            'repb' => fn (Customer $c) => $date($c->rep_birth_date),
            'cname' => fn (Customer $c) => trim("{$c->contact_last_name} {$c->contact_first_name}"),
            'cnamek' => fn (Customer $c) => trim("{$c->contact_last_name_kana} {$c->contact_first_name_kana}"),
            'ctel' => fn (Customer $c) => $c->contact_tel,
            'cmail' => fn (Customer $c) => $c->contact_mail,
            'treg' => fn (Customer $c) => $c->hasTerminal() ? 'Sí' : 'No',
            'tid' => fn (Customer $c) => $c->terminal?->name,
            'bounce' => fn (Customer $c) => $c->bounce_count,
            'stamps' => fn (Customer $c) => $c->stamp_balance,
            'points' => fn (Customer $c) => $c->point_balance,
            'staff' => fn (Customer $c) => '',
            'motive' => fn (Customer $c) => $c->visitMotive?->name,
            'arn' => fn (Customer $c) => $c->amountRank?->name,
            'arp' => fn (Customer $c) => $c->prevAmountRank?->name,
            'vrn' => fn (Customer $c) => $c->visitRank?->name,
            'vrp' => fn (Customer $c) => $c->prevVisitRank?->name,
            'spouse' => fn (Customer $c) => $c->spouse_flg === null ? '' : ($c->spouse_flg ? 'Sí' : 'No'),
            'wedding' => fn (Customer $c) => $date($c->wedding_date),
        ];
    }

    public function export(Builder $query, ?int $shopId, string $filename): StreamedResponse
    {
        $labels = collect(config('crm.customer_fields'))->flatMap(fn ($filas) => collect($filas)->mapWithKeys(fn ($f) => [$f[0] => $f[1]]))
            ->merge(collect(config('crm.family_fields'))->mapWithKeys(fn ($f) => [$f[0] => $f[1]]));

        $codes = FieldSetting::where('shop_id', $shopId)->where('csv_flg', 1)->orderBy('sort')->pluck('field_name')
            // El Nº de socio va siempre primero: sin él el archivo no sirve para reimportar
            ->prepend('num')->unique()->values();

        $resolvers = $this->resolvers();
        $codes = $codes->filter(fn ($c) => isset($resolvers[$c]))->values();

        return $this->stream($query, $filename, $codes->map(fn ($c) => $labels[$c] ?? $c)->all(),
            fn (Customer $c) => $codes->map(fn ($code) => $resolvers[$code]($c))->all());
    }

    /** Etiquetas de correo: nombre, código postal y dirección completa */
    public function exportMailing(Builder $query, string $filename): StreamedResponse
    {
        return $this->stream($query->whereNotNull('street_address'), $filename,
            ['Nº de socio', 'Nombre', 'Código postal', 'Prefectura', 'Ciudad', 'Dirección', 'Edificio'],
            fn (Customer $c) => [$c->code, $c->full_name, $c->zip, $c->pref, $c->city, $c->street_address, $c->building]);
    }

    private function stream(Builder $query, string $filename, array $header, \Closure $row): StreamedResponse
    {
        return response()->streamDownload(function () use ($query, $header, $row) {
            $out = fopen('php://output', 'w');
            // BOM para que Excel abra el UTF-8 (kanji y tildes) sin estropearlo
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $header);

            $query->with(['shop', 'group', 'terminal', 'visitMotive', 'amountRank', 'prevAmountRank', 'visitRank', 'prevVisitRank'])
                ->orderBy('id')
                ->chunk(500, function ($customers) use ($out, $row) {
                    foreach ($customers as $customer) {
                        fputcsv($out, array_map(fn ($v) => $v ?? '', $row($customer)));
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
