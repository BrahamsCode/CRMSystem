<?php

namespace App\Models\Integraciones;

use App\Models\Customers\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Accesos a /ptag/<ID> (`url_transfer_data`): `access` (timestamp), `tb_customer_cd` (NULL =
 * visitante sin cuenta) y `data_id` (= `tb_url_list.id`). La tabla del legacy **no tiene clave
 * primaria**: el modelo solo inserta y lee. S4 la lee para su 集計.
 */
class UrlTransferData extends Model
{
    protected $table = 'url_transfer_data';

    protected $primaryKey = null;

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'access' => 'datetime',
        'tb_customer_cd' => 'integer',
    ];

    /** Registra un acceso como ptag/index.php */
    public static function record(string $dataId, ?int $customerCd): void
    {
        DB::table('url_transfer_data')->insert([
            'access' => now()->format('Y-m-d H:i:s'),
            'tb_customer_cd' => $customerCd,
            'data_id' => $dataId,
        ]);
    }

    /** Socio que abrió el enlace (tb_customer de S2); null para visitantes sin cuenta */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'tb_customer_cd', 'cd');
    }
}
