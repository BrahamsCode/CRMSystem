<?php

namespace App\Services\Reservations;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 予約プリント (legacy category9/yoyaku/ajaxYoyakuPrint.php): toma la primera reserva pendiente de imprimir
 * (`tb_yoyaku.print_flg = '1'`, la marca ReservationService al registrar si リアルタイム印刷 está activa),
 * arma el mensaje SOAP/OFSC que el navegador envía a la impresora local y la deja en `print_flg = '2'`.
 */
class ReservationPrintService
{
    /**
     * Siguiente reserva pendiente (orden cd) y su XML; null si no hay ninguna.
     * `$shopCd` limita a las reservas de empleados de esa tienda (null = todas, como el legacy).
     */
    public function takeNext(?int $shopCd): ?string
    {
        return DB::transaction(function () use ($shopCd) {
            $row = DB::table('tb_yoyaku')
                ->where('print_flg', '1')
                ->when($shopCd !== null, fn ($q) => $q->whereIn('tb_yoyaku_uketsuke_day_cd', DB::table('tb_yoyaku_uketsuke_day as d')
                    ->join('tb_staff as s', 'd.tb_staff_cd', '=', 's.cd')
                    ->where('s.shop_cd', $shopCd)
                    ->select('d.cd')))
                ->orderBy('cd')
                ->lockForUpdate()
                ->first();
            if ($row === null) {
                return null;
            }

            $xml = $this->soapMessage($row);

            DB::table('tb_yoyaku')->where('cd', $row->cd)->update(['print_flg' => '2']);

            return $xml;
        });
    }

    /** Mensaje DeviceExecuteRequest del legacy (mismos nodos y valores fijos de ejemplo), con los textos escapados */
    public function soapMessage(object $row): string
    {
        $customerName = (string) $row->name;
        if ($row->tb_customer_cd) {
            $customerName = (string) (DB::table('tb_customer')->where('cd', (int) $row->tb_customer_cd)->value('name') ?? $customerName);
        }

        $slot = DB::table('tb_yoyaku_uketsuke_day')->where('cd', (int) $row->tb_yoyaku_uketsuke_day_cd)->first(['moushikomi_date', 'tb_staff_cd']);
        $staff = $slot ? DB::table('tb_staff')->where('cd', (int) $slot->tb_staff_cd)->first(['name', 'shop_cd']) : null;
        // El legacy tenía fijo 「長野県松本店」: aquí, la tienda del empleado de la franja
        $shopName = $staff ? (string) DB::table('tb_shop')->where('cd', (int) $staff->shop_cd)->value('kaishamei') : '';

        $touroku = is_numeric($row->touroku_date) ? Carbon::createFromTimestamp((int) $row->touroku_date, 'Asia/Tokyo')->format('Y/m/d H:i') : '';

        $e = fn (?string $value) => htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $requestor = $e($customerName);

        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.www.w3.org/2001/XMLSchema">',
            '	<soap:Body>',
            '	<DeviceExecuteRequest FixVersion="0" MajorVersion="1" MinorVersion="0" xmlns="http://www.ofsc.org/namespace/Device/2008/11">',
            '	<ARTSHeader MessageType="Request">',
            '		<MessageID>1</MessageID>',
            '		<DateTime TypeCode="Message">2011-05-11T20:20:52+09:00</DateTime>',
            '		<Requestor>' . $requestor . '</Requestor>',
            '		<WorkstationID>HT10</WorkstationID>',
            '	</ARTSHeader>',
            '	<Device>',
            '		<Name>local_printer</Name>',
            '		<Stylesheet>crm_yoyaku.xsl</Stylesheet>',
            '		<Timeout>10000</Timeout>',
            '	</Device>',
            '	<POSLog xmlns="http://www.nrf-arts.org/IXRetail/namespace/">',
            '		<Transaction MajorVersion="4" MinorVersion="0" FixVersion="0">',
            '			<RetailStoreID>' . $e($shopName) . '</RetailStoreID>',
            '			<WorkstationID>HT10</WorkstationID>',
            '			<SequenceNumber>1</SequenceNumber>',
            '			<POSLogDateTime>2011-05-11T20:20:52+09:00</POSLogDateTime>',
            '			<OperatorID>' . $requestor . '</OperatorID>',
            '			<CustomerOrderTransaction TransactionStatus="Suspended">',
            '				<ItemCount>1</ItemCount>',
            '               <OfficialBill>',
            '                   <BillNumber>1</BillNumber>',
            '                   <AmountOfBill>230</AmountOfBill>',
            '               </OfficialBill>',
            '				<LineItem>',
            '					<SequenceNumber>1</SequenceNumber>',
            '					<Sale>',
            '					    <ItemID type="MenuID">2002</ItemID>',
            '						<customer_name>' . $requestor . '</customer_name>',
            '						<yoyaku_date>' . $e($slot->moushikomi_date ?? '') . '</yoyaku_date>',
            '						<yoyaku_start_time>' . $this->clock((int) $row->start_time) . '</yoyaku_start_time>',
            '						<yoyaku_end_time>' . $this->clock((int) $row->end_time) . '</yoyaku_end_time>',
            '						<base_menu>' . $e($this->menuNames($row->base_tb_yoyaku_koumoku_master_cd)) . '</base_menu>',
            '						<option_menu>' . $e($this->menuNames($row->option_tb_yoyaku_koumoku_master_cd)) . '</option_menu>',
            '						<staff_name>' . $e($staff->name ?? '') . '</staff_name>',
            '						<touroku_date>' . $touroku . '</touroku_date>',
            '						<hakkoutime>' . now('Asia/Tokyo')->format('Y年m月d日 H時i分s秒') . '</hakkoutime>',
            '						<TaxIncludedInPriceFlag>true</TaxIncludedInPriceFlag>',
            '						<RegularSalesUnitPrice>230</RegularSalesUnitPrice>',
            '						<ExtendedAmount>230</ExtendedAmount>',
            '						<Quantity>1</Quantity>',
            '					</Sale>',
            '				</LineItem>',
            '               <LineItem>',
            '                   <SequenceNumber>2</SequenceNumber>',
            '                   <Tender TenderType="Cash">',
            '                       <Amount>1000</Amount>',
            '                       <TenderChange>',
            '                           <Amount>770</Amount>',
            '                       </TenderChange>',
            '                   </Tender>',
            '               </LineItem>',
            '				<Total TotalType="TransactionGrandAmount">230</Total>',
            '				<Total TotalType="TransactionNetAmount">219</Total>',
            '				<Total TotalType="TransactionTaxAmount">11</Total>',
            '				<Foodservice>',
            '					<TableID>A12</TableID>',
            '					<PartySize>1</PartySize>',
            '					<Gratuity>0</Gratuity>',
            '				</Foodservice>',
            '			</CustomerOrderTransaction>',
            '		</Transaction>',
            '	</POSLog>',
            '	</DeviceExecuteRequest>',
            '	</soap:Body>',
            '</soap:Envelope>',
        ];

        return implode("\n", $lines) . "\n";
    }

    /** date('H:i', t - 32400): hora del día (pasada la medianoche vuelve a 00) */
    private function clock(int $seconds): string
    {
        $seconds %= 86400;

        return sprintf('%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60));
    }

    /** Nombres de los menús en el orden de la lista ('100,101' → 'カット / シャンプー') */
    private function menuNames(?string $list): string
    {
        $cds = array_values(array_map('intval', array_filter(explode(',', (string) $list), fn ($cd) => is_numeric(trim($cd)))));
        if ($cds === []) {
            return '';
        }
        $names = DB::table('tb_yoyaku_koumoku_master')->whereIn('cd', $cds)->pluck('name', 'cd');

        return implode(' / ', array_map(fn ($cd) => (string) ($names[$cd] ?? ''), $cds));
    }
}
