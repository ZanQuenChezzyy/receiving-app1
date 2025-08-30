<!DOCTYPE html>
<html>

<head>
    <title>QR Code DO Bulk</title>
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

        /* === Konsisten dengan do-qr.blade.php: QR di kanan, kecil, tanpa inline style === */
        .label-table .qr {
            width: 40px;
            text-align: center;
            padding-left: 4px;
        }

        /* Nilai penting jangan dipecah karakter-per-karakter */
        .no-wrap {
            white-space: nowrap;
            word-break: normal;
            overflow-wrap: normal;
        }

        /* PO No lebih besar (sama seperti do-qr.blade.php) */
        .po-large {
            font-size: 14px;
            letter-spacing: .2px;
        }

        /* QR kecil agar tidak ganggu layout (sama dengan do-qr.blade.php) */
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
    </style>
</head>

<body>

    @foreach ($records as $record)
        @php
            /** @var \App\Models\DeliveryOrderReceipt $do */
            $do = $record['do'];
            $qrDo = $record['qrDo'];
            $items = $record['items'];

            $poNo = optional($do->purchaseOrderTerbits)->purchase_order_no ?? '-';
            $tanggal = $do->received_date ? \Carbon\Carbon::parse($do->received_date)->format('d/m/Y') : '-';
            $receivedBy = optional($do->receivedBy)->name ?? '-';

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

        {{-- Halaman per item (kecil) --}}
        @foreach ($do->deliveryOrderReceiptDetails as $index => $detail)
            @php
                $qrItem = $items[$index]['qr'] ?? '';

                // Lokasi konsisten (tanpa limit)
                $locationRaw = $detail->is_different_location
                    ? optional($detail->locations)->name ?? 'Lokasi Beda (Tidak diketahui)'
                    : optional($do->locations)->name ?? 'Lokasi Utama (Tidak diketahui)';
                $location = $locationRaw;

                // Deskripsi: bersihkan newline/tabs → single line, POTONG 25 huruf, &nbsp; agar satu baris
                $rawDesc = (string) ($detail->description ?? '-');
                $descFlat = preg_replace('/\s+/u', ' ', str_replace(["\r\n", "\n", "\r", "\t"], ' ', $rawDesc));
                $desc25 = mb_substr($descFlat, 0, 25);
                $descOneLine = str_replace(' ', '&nbsp;', e($desc25));

                $qtyReceived = $detail ? $detail->quantity . ' ' . $detail->uoi : '-';
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
                        {{-- === QR di kanan, konsisten dengan do-qr.blade.php === --}}
                        <td class="qr" rowspan="8">
                            <img src="{{ $qrItem }}" class="qr-mini">
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
                        <td class="val no-wrap">{{ $detail->material_code ?? '-' }}</td>
                    </tr>

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

        {{-- Halaman 1: QR utama DO (besar) --}}
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
                    {{-- QR span 2 baris pertama (sesuai do-qr.blade.php) --}}
                    <td rowspan="2" style="text-align: center; padding-right: 10px; vertical-align: top;">
                        <img src="{{ $qrDo }}" style="width: 45px; height: 45px;">
                    </td>

                    {{-- Judul (PO No | Tahun) --}}
                    <td colspan="3" style="padding-bottom: 4px;">
                        <div
                            style="border: 1px solid #000; padding: 4px 8px; font-size: 18px; text-align: center; background-color: #000;">
                            <strong style="color: #fff;">
                                {{ $poNo }} | {{ $tahun }}
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
                    <td class="no-wrap">{{ $receivedBy }}</td>
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
    @endforeach

</body>

</html>
