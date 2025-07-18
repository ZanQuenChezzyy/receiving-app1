<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrderReceipt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QRCodeController extends Controller
{
    public function print($id)
    {
        $do = DeliveryOrderReceipt::with('deliveryOrderReceiptDetails')->findOrFail($id);

        // QR utama hanya nomor DO → PNG base64
        $nomorPo = $do->purchaseOrderTerbits->purchase_order_no;
        $nomorDo = preg_replace('/[^A-Za-z0-9]/', '', $do->nomor_do); // Hapus "/", "\" dll
        $tanggal = \Carbon\Carbon::parse($do->received_date)->format('dmY'); // contoh: 15072025

        $qrContent = $nomorPo . $nomorDo . $tanggal; // Gabungan string

        $qrDo = base64_encode(QrCode::size(200)->generate($qrContent));
        $qrDo = 'data:image/png;base64,' . $qrDo;

        $logoPath = public_path('img/logo-pupuk-kaltim.png');
        $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));

        // QR per item
        $items = $do->deliveryOrderReceiptDetails->map(function ($item) use ($do) {
            $qr = base64_encode(QrCode::size(200)->generate("{$do->nomor_do}-{$item->item_no}"));
            return [
                'label' => "Item {$item->item_no}",
                'qr' => 'data:image/png;base64,' . $qr,
            ];
        });

        $pdf = Pdf::loadView('pdf.do-qr', [
            'qrDo' => $qrDo,
            'items' => $items,
            'do' => $do,
            'logo' => $logoBase64,
        ])->setPaper([2 * 25.4, 3 * 25.4], 'landscape');

        return $pdf->stream('QR-DO-' . str_replace(['/', '\\'], '_', $do->nomor_do) . '.pdf');
    }

    public function bulkPrint(Request $request)
    {
        $ids = explode(',', $request->get('ids'));
        $dos = DeliveryOrderReceipt::with(['deliveryOrderReceiptDetails', 'purchaseOrderTerbits', 'receivedBy', 'locations'])->findMany($ids);

        $data = [];

        foreach ($dos as $do) {
            $nomorPo = $do->purchaseOrderTerbits->purchase_order_no;
            $nomorDo = preg_replace('/[^A-Za-z0-9]/', '', $do->nomor_do);
            $tanggal = \Carbon\Carbon::parse($do->received_date)->format('dmY');

            $qrContent = $nomorPo . $nomorDo . $tanggal;
            $qrDo = 'data:image/png;base64,' . base64_encode(QrCode::size(200)->generate($qrContent));

            $items = $do->deliveryOrderReceiptDetails->map(function ($item) use ($do) {
                $qr = base64_encode(QrCode::size(200)->generate("{$do->nomor_do}-{$item->item_no}"));
                return [
                    'label' => "Item {$item->item_no}",
                    'qr' => 'data:image/png;base64,' . $qr,
                ];
            });

            $data[] = [
                'do' => $do,
                'qrDo' => $qrDo,
                'items' => $items,
            ];
        }

        $logoPath = public_path('img/logo-pupuk-kaltim.png');
        $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));

        $pdf = Pdf::loadView('pdf.bulk-do-qr', [
            'records' => $data,
            'logo' => $logoBase64,
        ])->setPaper([2 * 25.4, 3 * 25.4], 'landscape');

        return $pdf->stream('Bulk-QR-DO.pdf');
    }

}
