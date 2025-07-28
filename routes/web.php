<?php

use App\Http\Controllers\QRCodeController;
use App\Http\Controllers\TransmittalKirimPrintController;
use Illuminate\Support\Facades\Route;

Route::get('/do-receipt/{id}/print-qr', [QRCodeController::class, 'print'])->name('do-receipt.print-qr');
Route::get('/do-receipt/print-qr', [QRCodeController::class, 'bulkPrint'])->name('qr.bulk.print');
Route::get('/transmittal-kirim/cetak', [TransmittalKirimPrintController::class, 'bulkPrint'])->name('transmittal-kirim.bulk-print');
