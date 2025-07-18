<div style="page-break-after: always; font-family: sans-serif; padding: 0 8px;">
    <table style="width: 100%; font-size: 10px;">
        <tr>
            <td style="width: 65%;">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 35%;">PO No</td>
                        <td style="text-align: center;">:</td>
                        <td>{{ $do->purchaseOrderTerbits->purchase_order_no ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td style="width: 35%;">DO No</td>
                        <td style="text-align: center;">:</td>
                        <td>{{ $do->nomor_do }}</td>
                    </tr>
                    <tr>
                        <td style="width: 35%;">Item No</td>
                        <td style="text-align: center;">:</td>
                        <td>{{ $item['label'] }}</td>
                    </tr>
                    <tr>
                        <td style="width: 35%;">Deskripsi</td>
                        <td style="text-align: center;">:</td>
                        <td>
                            {{ \Illuminate\Support\Str::limit(optional($do->deliveryOrderReceiptDetails->firstWhere('item_no', explode(' ', $item['label'])[1]))->description, 27) ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 35%;">Lokasi</td>
                        <td style="text-align: center;">:</td>
                        <td>
                            {{ optional($do->deliveryOrderReceiptDetails->firstWhere('item_no', explode(' ', $item['label'])[1]))
                                ?->is_different_location
                                ? \Illuminate\Support\Str::limit(
                                        optional($do->deliveryOrderReceiptDetails->firstWhere('item_no', explode(' ', $item['label'])[1]))
                                            ?->locations?->name,
                                        25,
                                    ) ?? 'Lokasi Beda (Tidak diketahui)'
                                : \Illuminate\Support\Str::limit(optional($do->locations)?->name, 25) ?? 'Lokasi Utama (Tidak diketahui)' }}
                        </td>
                    </tr>
                </table>
            </td>
            <td style="width: 35%; text-align: right;">
                <img src="{{ $item['qr'] }}" style="height: 120px;">
            </td>
        </tr>
    </table>

    <div style="text-align: right; margin-top: 4px;">
        <img src="{{ $logo }}" style="height: 15px;">
    </div>
</div>
