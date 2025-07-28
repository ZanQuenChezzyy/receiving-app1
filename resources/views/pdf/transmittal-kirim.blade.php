<!DOCTYPE html>
<html>

<head>
    <meta content="text/html; charset=UTF-8" http-equiv="content-type">
    <style type="text/css">
        @import url('https://fonts.googleapis.com/css2?family=Quattrocento+Sans:wght@400;700&display=swap');

        @page {
            size: A4 portrait;
            margin: 10mm;
        }

        body,
        p,
        span,
        td,
        th {
            font-family: 'Quattrocento Sans', sans-serif;
            font-size: 5pt;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            word-wrap: break-word;
        }

        th,
        td {
            padding: 2pt 5pt;
            border: 1pt solid #000;
            vertical-align: middle;
        }

        th {
            background-color: #f1a984;
            font-weight: bold;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .text-justify {
            text-align: justify;
        }

        /* Optional: jaga agar judul tetap besar & konsisten */
        h1,
        h2,
        h3,
        h4 {
            margin: 0;
            color: #0f4761;
            font-family: 'Play', sans-serif;
        }

        h1 {
            font-size: 20pt;
            padding: 18pt 0 4pt;
        }

        h2 {
            font-size: 16pt;
            padding: 8pt 0 4pt;
        }

        h3 {
            font-size: 14pt;
        }

        h4 {
            font-size: 11pt;
            font-style: italic;
        }

        .title {
            font-size: 28pt;
            color: #000;
        }

        .subtitle {
            font-size: 14pt;
            color: #595959;
        }

        /* Utility */
        .small-bold {
            font-weight: bold;
            font-size: 8pt;
        }

        .small {
            font-size: 6pt;
        }

        td,
        th {
            padding: 2pt 5pt;
            border: 0.3pt solid #000;
            vertical-align: middle;
            white-space: normal;
            overflow: visible;
            text-overflow: unset;
        }

        td:nth-child(6) {
            max-width: 200px;
            word-break: break-word;
        }
    </style>
</head>

<body>
    <br>
    <br>

    <p style="font-weight: bold;"><span style="font-size: 7pt">TANGGAL CETAK :
            {{ $printedAt->format('d/m/Y H:i:s') }}</span></p>
    <br>

    @foreach ($groupedTransmittals as $tanggalKirim => $transmittals)
        <div @if (!$loop->last) style="page-break-after: always;" @endif>
            <table>
                <tr>
                    <td class="text-center" style="font-weight: bold">
                        <p><span style="font-size: 6pt">TRANSMITTAL</span></p>
                        <p><span style="font-size: 6pt">DOCUMENT PENGAJUAN QUALITY CONTROL</span></p>
                    </td>
                    <td class="text-right" style="font-weight: bold">
                        <span style="font-size: 6pt">DIKIRIM OLEH:</span>
                    </td>
                    <td class="text-center" style="font-weight: bold">
                        <span
                            style="font-size: 6pt">{{ $transmittals->pluck('users.name')->unique()->join(', ') }}</span>
                    </td>
                </tr>
                <tr>
                    <td class="text-center" style="font-weight: bold; font-size: 6pt;">RECEIVING --> ISTEK</td>
                    <td class="text-right" style="font-weight: bold;">
                        <span style="font-size: 6pt">DITERIMA OLEH:</span>
                    </td>
                    <td class="text-center" style="font-weight: bold;font-size: 6pt;"><span>-</span></td>
                </tr>
            </table>

            <br>

            <table>
                <thead>
                    <tr>
                        <th style="width: 2%;">NO.</th>
                        <th style="width: 8%;">TANGGAL KIRIM</th>
                        <th style="width: 6%;">PO NO.</th>
                        <th style="width: 6%;">DOCUMENT GR</th>
                        <th style="width: 3%;">ITEM PO</th>
                        <th style="width: 5%;">MATERIAL CODE</th>
                        <th style="width: 25%;">DESCRIPTION</th>
                        <th style="width: 4%;">QTY RECEIVED</th>
                        <th style="width: 3%;">UOI</th>
                        <th style="width: 15%;">LOKASI</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $previousPoNo = null;
                        $no = 0;
                    @endphp
                    @foreach ($transmittals as $transmittal)
                        @php
                            $receipt = $transmittal->deliveryOrderReceipts;
                            $details = $receipt?->deliveryOrderReceiptDetails ?? collect();
                            $poNo = $receipt?->purchaseOrderTerbits?->purchase_order_no ?? '-';
                            $location = $receipt?->locations?->name ?? '-';
                            $receivedDate = optional($receipt?->received_date)->format('d/m/Y') ?? '-';
                            $tanggalKirimFormat = \Carbon\Carbon::parse($transmittal->tanggal_kirim)->format('d/m/Y');
                        @endphp

                        @if ($poNo !== $previousPoNo)
                            @php
                                $no++;
                                $previousPoNo = $poNo;
                            @endphp
                        @endif

                        @foreach ($details as $detail)
                            <tr>
                                <td class="text-center">{{ $no }}</td>
                                <td class="text-center">{{ $tanggalKirimFormat }}</td>
                                <td class="text-center">{{ $poNo }}</td>
                                <td class="text-center">{{ substr($transmittal->code_103, 0, 10) }}</td>
                                <td class="text-center">{{ $detail->item_no }}</td>
                                <td class="text-center">{{ $detail->material_code ?? '-' }}</td>
                                <td class="text-left">{{ $detail->description }}</td>
                                <td class="text-center">{{ number_format($detail->quantity) }}</td>
                                <td class="text-center">{{ $detail->uoi }}</td>
                                <td class="text-center">{{ $location }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</body>

</html>
