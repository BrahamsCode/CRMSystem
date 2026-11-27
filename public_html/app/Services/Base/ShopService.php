<?php

namespace App\Services\Base;

use App\Mail\Base\ShopCreatedMail;
use App\Models\AdminMaster;
use App\Models\Shop;
use App\Support\LegacyCheck;
use App\Support\LegacyText;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

/**
 * 店舗情報管理 (category0/shop): validación de shop_new_confirm.php, guardado de shop_new_register.php
 * y borrado/recuperación de shop_delete_register.php.
 */
class ShopService
{
    /** Campos del formulario shop_new.php, en orden */
    public const FIELDS = [
        'kaishamei_kana', 'kaishamei', 'shop_category_cd', 'area_cd', 'is_closed', 'is_new_store',
        'new_store_start_date', 'new_store_end_date', 'zip', 'pref', 'add_kana', 'add', 'add_number',
        'add_build_kana', 'add_build', 'tel', 'fax', 'mycolle_tel', 'shop_url', 'email', 'tantou_kana', 'tantou',
        'kyuyo_marume', 'start_work_time1', 'start_work_time2', 'break_time', 'shiire_id', 'shiire_pass',
        'latitude', 'longitude', 'beacon_visiting_area', 'beacon_accounting_area',
    ];

    /** Distancias de beacon (cm) del selector */
    public const BEACON_AREAS = [0, 10, 50, 100, 500, 1000, 2500, 5000];

    /** Tienda "casa central": recibe al personal y los clientes de las tiendas borradas */
    public const HEAD_SHOP_CD = 1;

    /**
     * Datos del confirm: recorte de espacios, teléfonos sin guiones y 責任者 por defecto en modo app.
     *
     * @return array<string, string>
     */
    public function normalize(array $input, bool $extShop): array
    {
        $data = [];
        foreach (self::FIELDS as $field) {
            $data[$field] = LegacyText::trim(is_scalar($input[$field] ?? null) ? (string) $input[$field] : '');
        }
        foreach (['tel', 'fax', 'mycolle_tel'] as $field) {
            $data[$field] = (string) LegacyCheck::replaceTel($data[$field]);
        }
        if ($extShop && $data['tantou'] === '') {
            $data['tantou'] = '責任者';
        }

        return $data;
    }

    /** Reglas de shop_new_confirm.php (el duplicado de nombre solo contra tiendas activas, sin contar la propia) */
    public function check(array $data, ?int $shopCd): LegacyCheck
    {
        $check = new LegacyCheck;

        if ($data['kaishamei'] !== '' && Shop::query()->where('kaishamei', LegacyText::form($data['kaishamei']))
            ->where('disp_flg', '1')->when($shopCd, fn ($q) => $q->where('cd', '<>', $shopCd))->exists()) {
            $check->add('kaishamei', '<p class="m0">※既に同じ会社名/屋号が登録されています。</p>');
        }
        foreach (['new_store_start_date' => '開始', 'new_store_end_date' => '終了'] as $field => $label) {
            if ($data[$field] !== '' && ! preg_match('@([0-9]{4,})/([0-9]{1,2})/([0-9]{1,2})@', $data[$field])) {
                $check->add('status', '<p class="m0">※『対象期間 (' . $label . ')』の日付のフォーマットが正しくありません。</p>');
            }
        }

        return $check->katakana('kaishamei_kana', $data['kaishamei_kana'])
            ->blank('kaishamei', $data['kaishamei'])
            ->zip('zip', $data['zip'])
            ->katakana('add_kana', $data['add_kana'])
            ->katakanaAlnum('add_build_kana', $data['add_build_kana'])
            ->num('tel', $data['tel'])
            ->num('mycolle_tel', $data['mycolle_tel'])
            ->num('fax', $data['fax'])
            ->url('shop_url', $data['shop_url'])
            ->mailRequired('email', $data['email'])
            ->katakana('tantou_kana', $data['tantou_kana'])
            ->blank('tantou', $data['tantou'])
            ->alnumHyphen('shiire_id', $data['shiire_id'])
            ->alnumHyphen('shiire_pass', $data['shiire_pass']);
    }

    /** shop_new_register.php: alta (cd con hatuban tb_shop) o edición */
    public function save(array $input, ?int $shopCd, string $host): Shop
    {
        $data = array_map(fn ($v) => LegacyText::form($v), array_intersect_key($input, array_flip(self::FIELDS)) + array_fill_keys(self::FIELDS, ''));

        $shop = DB::transaction(function () use ($data, $shopCd) {
            $shop = $shopCd ? Shop::withDeleted()->findOrFail($shopCd) : new Shop(['disp_flg' => '1']);
            $shop->fill([
                'kaishamei' => $data['kaishamei'],
                'kaishamei_kana' => $data['kaishamei_kana'],
                'zip' => $data['zip'],
                'pref' => $data['pref'],
                'add' => $data['add'],
                'add_kana' => $data['add_kana'],
                'add_number' => $data['add_number'],
                'add_build' => $data['add_build'],
                'add_build_kana' => $data['add_build_kana'],
                'shop_url' => $data['shop_url'],
                'email' => $data['email'],
                'tel' => $data['tel'],
                'mycolle_tel' => $data['mycolle_tel'],
                'fax' => $data['fax'],
                'tantou' => $data['tantou'],
                'kyuyo_marume' => $data['kyuyo_marume'],
                'start_work_time' => $data['start_work_time1'] . '<>' . $data['start_work_time2'],
                'break_time' => $data['break_time'],
                'tantou_kana' => $data['tantou_kana'],
                'shop_category_cd' => (int) $data['shop_category_cd'],
                'area_cd' => (int) $data['area_cd'],
                'is_closed' => (int) $data['is_closed'],
                'is_new_store' => (int) $data['is_new_store'],
                'new_store_start_date' => $data['new_store_start_date'],
                'new_store_end_date' => $data['new_store_end_date'],
                'beacon_visiting_area' => (int) $data['beacon_visiting_area'],
                'beacon_accounting_area' => (int) $data['beacon_accounting_area'],
                'shiire_id' => $data['shiire_id'],
                'shiire_pass' => $data['shiire_pass'],
                'latitude' => $data['latitude'] === '' ? null : (float) $data['latitude'],
                'longitude' => $data['longitude'] === '' ? null : (float) $data['longitude'],
            ]);
            $shop->save();

            return $shop;
        });

        $this->notify($shop, $shopCd === null, $host);

        return $shop;
    }

    /**
     * Después de guardar, como el legacy: teléfono de マイコレ al servidor de la app (si la app está activa)
     * y, en el alta, aviso por correo al soporte del proveedor.
     */
    private function notify(Shop $shop, bool $created, string $host): void
    {
        $admin = AdminMaster::current();
        if ((string) $admin->application_use_flg === '1') {
            $apiDomain = (int) $admin->push_api_host === 0 ? 'b-develop.g-api.net' : 'b.g-api.net';
            try {
                Http::timeout(3)->get("http://{$apiDomain}/api/mpa/setStoreTel", [
                    'domain' => $host, 'shop_cd' => $shop->cd, 'tel' => $shop->mycolle_tel,
                ]);
            } catch (\Throwable) {
                // El legacy ignora el resultado (una sola llamada, falle o no)
            }
        }

        $to = config('crm.notify.shop_created');
        if ($created && $to) {
            Mail::to($to)->send(new ShopCreatedMail($shop, $host));
        }
    }

    /** shop_delete_register.php: borrado lógico y reasignación de personal y clientes a la tienda 1 */
    public function delete(int $shopCd): void
    {
        DB::transaction(function () use ($shopCd) {
            DB::table('tb_shop')->where('cd', $shopCd)->update(['disp_flg' => '9']);
            DB::table('tb_staff')->where('shop_cd', $shopCd)->update(['shop_cd' => self::HEAD_SHOP_CD]);
            DB::table('tb_customer')->where('tb_shop_cd', $shopCd)->update(['tb_shop_cd' => self::HEAD_SHOP_CD]);
        });
    }

    /** 削除を取り消す: vuelve a disp_flg = '1' (las reasignaciones no se deshacen, como el legacy) */
    public function recover(int $shopCd): void
    {
        DB::table('tb_shop')->where('cd', $shopCd)->update(['disp_flg' => '1']);
    }
}
