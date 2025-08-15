<?php

namespace App\Providers;

use App\Models\ApprovalVpKembaliDetail;
use App\Models\DeliveryOrderReceipt;
use App\Models\GoodsReceiptSlip;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Listener untuk hapus DO Receipt
        Event::listen('eloquent.deleted: ' . ApprovalVpKembaliDetail::class, function ($detail) {
            $approvalVpKembali = $detail->approvalVpKembali()->first();

            if ($approvalVpKembali && $approvalVpKembali->approvalVpKembaliDetails()->count() === 0) {
                $approvalVpKembali->delete();
            }
        });
    }
}
