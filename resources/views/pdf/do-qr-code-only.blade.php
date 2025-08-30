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
            font-family: Helvetica;
            font-weight: bold;
            color: black;
            line-height: 1.1;
        }

        td {
            padding: 2px;
            vertical-align: top;
        }

        /* tambahan untuk halaman 2 supaya konsisten  */
        .no-wrap {
            white-space: nowrap;
        }
    </style>
</head>

<body>
    @php
        $poNo = optional($do->purchaseOrderTerbits)->purchase_order_no ?? '-';
        $tanggal = $do->received_date ? \Carbon\Carbon::parse($do->received_date)->format('d/m/Y') : '-';
        $receivedBy = \Illuminate\Support\Str::limit(optional($do->receivedBy)->name ?? '-', 7);

        // === Tahun (konsisten dengan do-qr.blade.php) ===
        $tahun = '-';
        $poDate = optional($do->purchaseOrderTerbits)->date_create;
        if ($poDate) {
            try {
                $tahun = \Carbon\Carbon::parse($poDate)->format('Y');
            } catch (\Throwable $e) {
            }
        }
        if ($tahun === '-' && $do->received_date) {
            try {
                $tahun = \Carbon\Carbon::parse($do->received_date)->format('Y');
            } catch (\Throwable $e) {
            }
        }
    @endphp

    {{-- Halaman 1: QR utama DO (tetap) --}}
    <div class="page">
        <table style="border-collapse: collapse; border: 1px solid black; width: 100%; max-width: 100%;">
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

    {{-- Halaman 2: PO QR Detail DO (baru, konsisten dengan do-qr.blade.php) --}}
    <div class="page">
        <table style="width: 100%; max-width: 100%; border-collapse: collapse; table-layout: fixed;">
            <colgroup>
                <col style="width: 30px;"> {{-- QR --}}
                <col style="width: 38%;"> {{-- Label --}}
                <col style="width: 4%;"> {{-- Colon --}}
                <col style="width: auto;"> {{-- Value --}}
            </colgroup>

            <tr>
                {{-- QR span 2 baris pertama --}}
                <td rowspan="2" style="text-align: center; padding-right: 10px; vertical-align: top;">
                    <img src="{{ $qrDo }}" style="width: 45px; height: 45px;">
                </td>

                {{-- Judul (PO No | Tahun) --}}
                <td colspan="3" style="padding-bottom: 4px;">
                    <div
                        style="border: 1px solid #000; padding: 4px 8px; font-size: 18px; text-align: center; background-color: #000;">
                        <strong style="color: #fff;">{{ $poNo }} | {{ $tahun }}</strong>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="no-wrap">Tahap</td>
                <td style="text-align: center;">:</td>
                <td class="no-wrap">{{ $do->tahapan ?? 'Tidak Ada' }}</td>
            </tr>

            <tr>
                <td class="no-wrap">Nomor DO</td>
                <td style="text-align: center;">:</td>
                <td class="no-wrap">{{ $do->nomor_do }}</td>
            </tr>

            <tr>
                <td class="no-wrap">Tanggal Terima</td>
                <td style="text-align: center;">:</td>
                <td class="no-wrap">{{ $tanggal }}</td>
            </tr>

            <tr>
                <td class="no-wrap">Total Item</td>
                <td style="text-align: center;">:</td>
                <td class="no-wrap">{{ $do->deliveryOrderReceiptDetails->count() }} Item</td>
            </tr>

            <tr>
                <td class="no-wrap">Diterima Oleh</td>
                <td style="text-align: center;">:</td>
                <td class="no-wrap">{{ optional($do->receivedBy)->name ?? '-' }}</td>
            </tr>

            <tr>
                <td colspan="3" style="text-align: left; padding-top: 6px;">
                    <img src="{{ $logo }}" style="height: 15px;">
                    <div style="font-size: 7px; margin-top: 2px;">
                        QR Dicetak Menggunakan Sistem<br>
                        ALEX MOKONDO (RECEIVING)
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
