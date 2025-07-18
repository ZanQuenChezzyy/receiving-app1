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
                        <td style="width: 35%;">Tanggal</td>
                        <td style="text-align: center;">:</td>
                        <td>{{ \Carbon\Carbon::parse($do->received_date)->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td style="width: 35%;">Total Item</td>
                        <td style="text-align: center;">:</td>
                        <td>{{ $do->deliveryOrderReceiptDetails->count() }} Item</td>
                    </tr>
                    <tr>
                        <td style="width: 35%;">Diterima Oleh</td>
                        <td style="text-align: center;">:</td>
                        <td>{{ \Illuminate\Support\Str::limit(optional($do->receivedBy)->name ?? '-', 13) }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 35%; text-align: right;">
                <img src="{{ $qrDo }}" style="height: 120px;">
            </td>
        </tr>
    </table>

    <div style="text-align: right; margin-top: 4px;">
        <img src="{{ $logo }}" style="height: 15px;">
    </div>
</div>
