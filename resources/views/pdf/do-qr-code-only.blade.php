<!DOCTYPE html>
<html>

<head>
    <title>QR Code DO - Code Only</title>
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
        }

        table {
            font-size: 10px;
            width: 100%;
            border-collapse: collapse;
            font-weight: bold;
            color: black;
        }

        td {
            padding: 2px;
        }
    </style>
</head>

<body>
    @php
        $poNo = optional($do->purchaseOrderTerbits)->purchase_order_no ?? '-';
        $tanggal = $do->received_date ? \Carbon\Carbon::parse($do->received_date)->format('d/m/Y') : '-';
        $receivedBy = \Illuminate\Support\Str::limit(optional($do->receivedBy)->name ?? '-', 7);
    @endphp

    <div class="page">
        <table style="border: 1px solid black;">
            <tr>
                <td colspan="4" style="padding-bottom: 4px;">
                    <div
                        style="border: 1px solid #000; padding: 4px 8px; font-size: 10px; text-align: center; background: #000;">
                        <strong style="color: #fff">WAJIB DITEMPEL DI MAP DOKUMEN!</strong>
                    </div>
                </td>
            </tr>
            <tr>
                <td rowspan="5" style="width: 100px; text-align: center; padding-right: 10px;">
                    <img src="{{ $qrDo }}" style="width: 90px; height: 90px;">
                </td>
                <td>PO No</td>
                <td style="text-align: center;">:</td>
                <td>{{ $poNo }}</td>
            </tr>
            <tr>
                <td style="width: 35%;">DO No</td>
                <td style="text-align: center;">:</td>
                <td>{{ $do->nomor_do }}</td>
            </tr>
            <tr>
                <td>Tanggal Terima</td>
                <td style="text-align: center;">:</td>
                <td>{{ $tanggal }}</td>
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
