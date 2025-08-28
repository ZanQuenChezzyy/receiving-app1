<?php

use App\Http\Controllers\QRCodeController;
use App\Http\Controllers\TransmittalKirimPrintController;
use Illuminate\Support\Facades\Route;

Route::get('/do-receipt/{id}/print-qr', [QRCodeController::class, 'print'])->name('do-receipt.print-qr');
Route::get('/do-receipt/print-qr', [QRCodeController::class, 'bulkPrint'])->name('qr.bulk.print');
Route::get('/do-receipt/{id}/code-only', [QRCodeController::class, 'printDoCodeOnly'])->name('do-receipt.print-qr-code-only');
Route::get('/do-receipt/code-only', [QRCodeController::class, 'bulkPrintDoCodeOnly'])->name('qr.bulk.print-code-only');
Route::get('/transmittal-kirim/cetak', [TransmittalKirimPrintController::class, 'bulkPrint'])->name('transmittal-kirim.bulk-print');
