<?php

namespace App\Http\Controllers;

use App\Models\TransmittalKirim;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class TransmittalKirimPrintController extends Controller
{
    public function bulkPrint(Request $request)
    {
        $ids = $request->get('records', []);

        $transmittals = TransmittalKirim::with([
            'users',
            'deliveryOrderReceipts.purchaseOrderTerbits',
            'deliveryOrderReceipts.locations',
            'deliveryOrderReceipts.deliveryOrderReceiptDetails',
        ])->whereIn('id', $ids)
            ->get()
            ->groupBy(function ($item) {
                return \Carbon\Carbon::parse($item->tanggal_kirim)->format('Y-m-d');
            });

        $pdf = Pdf::loadView('pdf.transmittal-kirim', [
            'groupedTransmittals' => $transmittals,
            'printedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('transmittal-kirim.pdf');
    }
}
