<!DOCTYPE html>
<html>

<head>
    <title>QR Code DO</title>
    <style>
        @page {
            size: 3in 2in landscape;
            margin: 1mm;
        }

        body {
            margin: 0;
            padding: 1mm;
            font-family: Helvetica, sans-serif;
        }

        .page {
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            page-break-after: always;
        }

        table {
            font-size: 10px;
            width: auto;
            font-family: Helvetica;
            font-weight: bold;
            color: black;
        }

        td {
            padding: 2px;
        }
    </style>
</head>

<body>
    {{-- Halaman per item --}}
    @foreach ($items as $item)
        @php
            $itemNo = explode(' ', $item['label'])[1];
            $detail = $do->deliveryOrderReceiptDetails->firstWhere('item_no', $itemNo);
            $description = isset($detail->description) ? \Illuminate\Support\Str::limit($detail->description, 20) : '-';
            $poNo = optional($do->purchaseOrderTerbits)->purchase_order_no ?? '-';
            $receivedBy = \Illuminate\Support\Str::limit(optional($do->receivedBy)->name ?? '-', 7);
            $tanggal = \Carbon\Carbon::parse($do->received_date)->format('d/m/Y');
            $locationRaw = $detail?->is_different_location
                ? optional($detail->locations)->name ?? 'Lokasi Beda (Tidak diketahui)'
                : optional($do->locations)->name ?? 'Lokasi Utama (Tidak diketahui)';
            $location = \Illuminate\Support\Str::limit($locationRaw, 20);
        @endphp

        <div class="page">
            <table
                style="width: 100%; border-collapse: collapse; font-size: 10px; font-family: Helvetica; font-weight: bold; color: black;">
                <tr>
                    <td colspan="3" style="text-align: left; text-decoration: underline; padding-bottom: 2px;">
                        LABEL MATERIAL - DO RECEIPT
                    </td>
                    <td style="text-align: right;">
                        <img src="{{ $logo }}" style="height: 15px;">
                    </td>
                </tr>
                <tr>
                    <td>PO No</td>
                    <td style="text-align: center;">:</td>
                    <td>{{ $poNo }}</td>
                    <td rowspan="7" style="text-align: center;">
                        <img src="{{ $item['qr'] }}" style="width: 90px; height: 90px;">
                    </td>
                </tr>
                <tr>
                    <td>DO No</td>
                    <td style="text-align: center;">:</td>
                    <td>{{ $do->nomor_do }}</td>
                </tr>
                <tr>
                    <td style="width: 40%;">Tgl Terima</td>
                    <td style="text-align: center;">:</td>
                    <td>{{ $tanggal }}</td>
                </tr>
                <tr>
                    <td>Item No</td>
                    <td style="text-align: center;">:</td>
                    <td>{{ $detail->item_no ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Material Code</td>
                    <td style="text-align: center;">:</td>
                    <td>{{ $detail->material_code ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Qty Diterima</td>
                    <td style="text-align: center;">:</td>
                    <td>
                        {{ $detail ? $detail->quantity . ' ' . $detail->uoi : '-' }}
                    </td>
                </tr>
                <tr>
                    <td colspan="1" style="padding-top: 2px;">Diterima Oleh</td>
                    <td style="text-align: center;">:</td>
                    <td>{{ $receivedBy }}</td>
                </tr>
                <tr>
                    <td colspan="1" style="padding-top: 2px;">Deskripsi</td>
                    <td style="text-align: center;">:</td>
                    <td colspan="5">{{ $description }}</td>
                </tr>
                <tr>
                    <td colspan="1" style="padding-top: 2px;">Lokasi</td>
                    <td style="text-align: center;">:</td>
                    <td colspan="5">{{ $location }}</td>
                    <td></td>
                </tr>

            </table>
        </div>
    @endforeach


    {{-- Halaman 1: QR utama DO --}}
    <div class="page">
        <table style="border-collapse: collapse; border: 1px solid black; width: 100%; max-width: 100%;">
            <tr>
                <td colspan="4" style="padding-bottom: 4px;">
                    <div
                        style="border: 1px solid rgb(0, 0, 0); padding: 4px 8px; font-size: 10px; text-align: center; background-color: black;">
                        <strong style="color: rgb(255, 255, 255)">WAJIB DITEMPEL DI MAP DOKUMEN!</strong>
                    </div>
                </td>
            </tr>
            <tr>
                <td rowspan="5" style="width: 100px; text-align: center; padding-right: 10px;">
                    <img src="{{ $qrDo }}" style="width: 90px; height: 90px;">
                </td>
                <td>PO No</td>
                <td style="text-align: center;">:</td>
                <td>{{ $do->purchaseOrderTerbits->purchase_order_no ?? '-' }}</td>
            </tr>
            <tr>
                <td style="width: 35%;">DO No</td>
                <td style="text-align: center;">:</td>
                <td>{{ $do->nomor_do }}</td>
            </tr>
            <tr>
                <td>Tanggal Terima</td>
                <td style="text-align: center;">:</td>
                <td>{{ \Carbon\Carbon::parse($do->received_date)->format('d/m/Y') ?? '-' }}</td>
            </tr>
            <tr>
                <td>Total Item</td>
                <td style="text-align: center;">:</td>
                <td>{{ $do->deliveryOrderReceiptDetails->count() }} Item</td>
            </tr>
            <tr>
                <td>Diterima Oleh</td>
                <td style="text-align: center;">:</td>
                <td>{{ $receivedBy }}</td>
            </tr>
            <tr>
                <td colspan="4" style="text-align: right; padding-top: 6px;">
                    <img src="{{ $logo }}" style="height: 15px;">
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
