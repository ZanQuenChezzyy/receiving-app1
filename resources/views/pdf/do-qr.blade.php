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
            font-family: Helvetica;
            font-weight: bold;
            color: black;
            line-height: 1.1;
        }

        td {
            padding: 2px;
            vertical-align: top;
        }

        /* Stabilkan layout & beri ruang lebih ke kolom nilai */
        .label-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .label-table .lbl {
            width: 22%;
        }

        /* semula 26% → kasih ruang ke nilai */
        .label-table .col {
            width: 3%;
            text-align: center;
        }

        .label-table .val {
            width: auto;
            white-space: normal;
            word-break: normal;
            overflow-wrap: break-word;
        }

        .label-table .qr {
            width: 40px;
            text-align: center;
            padding-left: 4px;
        }

        /* semula 52px */

        /* Nilai penting jangan dipecah karakter per karakter */
        .no-wrap {
            white-space: nowrap;
            word-break: normal;
            overflow-wrap: normal;
        }

        /* PO No lebih besar */
        .po-large {
            font-size: 14px;
            letter-spacing: .2px;
        }

        /* QR lebih kecil lagi supaya tidak “nyenggol” nilai */
        .qr-mini {
            width: 34px;
            height: 34px;
            object-fit: contain;
        }

        .desc-oneline {
            white-space: nowrap;
            font-size: 9.5px;
            letter-spacing: .1px;
        }

        .no-wrap {
            white-space: nowrap;
        }

        /* semula 42x42 */
    </style>
</head>

<body>
    {{-- Halaman per item --}}
    @foreach ($items as $item)
        @php
            $itemNo = explode(' ', $item['label'])[1] ?? null;
            $detail = $do->deliveryOrderReceiptDetails->firstWhere('item_no', $itemNo);

            $poNo = optional($do->purchaseOrderTerbits)->purchase_order_no ?? '-';
            $material = $detail->material_code ?? '-';
            $receivedBy = optional($do->receivedBy)->name ?? '-';
            $qtyReceived = $detail ? $detail->quantity . ' ' . $detail->uoi : '-';

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

            // Lokasi (tetap boleh wrap)
            $locationRaw = $detail?->is_different_location
                ? optional($detail->locations)->name ?? 'Lokasi Beda (Tidak diketahui)'
                : optional($do->locations)->name ?? 'Lokasi Utama (Tidak diketahui)';
            $location = $locationRaw;

            // ==== FIX: bersihkan newline agar tidak break di tengah ====
            $rawDesc = (string) ($detail->description ?? '-');
            $descFlat = preg_replace('/\s+/u', ' ', str_replace(["\r\n", "\n", "\r", "\t"], ' ', $rawDesc));
            $desc25 = mb_substr($descFlat, 0, 25);
            // 2) Escape HTML lalu ubah spasi ke &nbsp; supaya TIDAK wrap
            $descOneLine = str_replace(' ', '&nbsp;', e($desc25));
        @endphp

        <div class="page">
            <table class="label-table">
                <colgroup>
                    <col class="lbl">
                    <col class="col">
                    <col class="val">
                    <col class="qr">
                </colgroup>

                <tr>
                    <td colspan="3" style="text-align:left; text-decoration:underline; padding-bottom:2px;">
                        LABEL MATERIAL - DO RECEIPT
                    </td>
                    <td style="text-align:right;">
                        <img src="{{ $logo }}" style="height: 15px;">
                    </td>
                </tr>

                <tr>
                    <td>PO No</td>
                    <td class="col">:</td>
                    <td class="val po-large no-wrap">{{ $poNo }}</td>
                    <td class="qr" rowspan="8">
                        <img src="{{ $item['qr'] }}" class="qr-mini">
                    </td>
                </tr>

                <tr>
                    <td>Item No</td>
                    <td class="col">:</td>
                    <td class="val no-wrap">{{ $detail->item_no ?? '-' }}</td>
                </tr>

                <tr>
                    <td>Lokasi</td>
                    <td class="col">:</td>
                    <td class="val">{{ $location }}</td>
                </tr>

                <tr>
                    <td>Material Code</td>
                    <td class="col">:</td>
                    <td class="val no-wrap">{{ $material }}</td>
                </tr>

                {{-- DESKRIPSI: sudah dibersihkan dari newline, boleh wrap penuh sampai tepi kanan --}}
                <tr>
                    <td>Deskripsi</td>
                    <td class="col">:</td>
                    <td class="val no-wrap">
                        <span class="desc-oneline">{!! $descOneLine !!}</span>
                    </td>
                </tr>

                <tr>
                    <td>Qty Diterima</td>
                    <td class="col">:</td>
                    <td class="val no-wrap">{{ $qtyReceived }}</td>
                </tr>

                <tr>
                    <td>Diterima Oleh</td>
                    <td class="col">:</td>
                    <td class="val no-wrap">{{ $receivedBy }}</td>
                </tr>

                <tr>
                    <td>DO No</td>
                    <td class="col">:</td>
                    <td class="val no-wrap">{{ $do->nomor_do }}</td>
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

    {{-- Halaman 2: PO QR Detail DO --}}
    <div class="page">
        <table style="width: 100%; max-width: 100%; border-collapse: collapse; table-layout: fixed;">
            <colgroup>
                <col style="width: 30px;"> {{-- QR --}}
                <col style="width: 38%;"> {{-- Label --}}
                <col style="width: 4%;"> {{-- Colon --}}
                <col style="width: auto;"> {{-- Value --}}
            </colgroup>

            <tr>
                {{-- QR span semua baris (1 judul + 6 detail + 1 footer = 8). Sesuaikan jika jumlah baris berubah. --}}
                <td rowspan="2" style="text-align: center; padding-right: 10px; vertical-align: top;">
                    <img src="{{ $qrDo }}" style="width: 45px; height: 45px;">
                </td>

                {{-- Judul menempati 3 kolom selain QR --}}
                <td colspan="3" style="padding-bottom: 4px;">
                    <div
                        style="border: 1px solid #000; padding: 4px 8px; font-size: 18px; text-align: center; background-color: #000;">
                        <strong style="color: #fff;">
                            {{ $do->purchaseOrderTerbits->purchase_order_no ?? '-' }} | {{ $tahun }}
                        </strong>
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
                <td class="no-wrap">{{ \Carbon\Carbon::parse($do->received_date)->format('d/m/Y') ?? '-' }}</td>
            </tr>

            <tr>
                <td class="no-wrap">Total Item</td>
                <td style="text-align: center;">:</td>
                <td class="no-wrap">{{ $do->deliveryOrderReceiptDetails->count() }} Item</td>
            </tr>

            <tr>
                <td class="no-wrap">Diterima Oleh</td>
                <td style="text-align: center;">:</td>
                <td class="no-wrap">{{ $receivedBy }}</td>
            </tr>

            <tr>
                <td colspan="3" style="text-align: left; padding-top: 6px;">
                    <img src="{{ $logo }}" style="height: 15px;">
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
