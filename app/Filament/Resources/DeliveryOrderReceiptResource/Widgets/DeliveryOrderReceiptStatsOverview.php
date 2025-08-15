<?php

namespace App\Filament\Resources\DeliveryOrderReceiptResource\Widgets;

use App\Models\DeliveryOrderReceipt;
use App\Models\DeliveryOrderReceiptDetail;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class DeliveryOrderReceiptStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $monthName = Carbon::now()->translatedFormat('F Y');
        $dayName = Carbon::now()->translatedFormat('l');

        // DO 7 hari terakhir
        $doLast7Days = collect(range(6, 0))->map(function ($i) {
            return DeliveryOrderReceipt::whereDate('received_date', Carbon::today()->subDays($i))->count();
        });

        // DO Bulanan per minggu (4 minggu terakhir)
        $weeklyDO = collect(range(3, 0))->map(function ($i) {
            $start = Carbon::now()->subWeeks($i)->startOfWeek();
            $end = Carbon::now()->subWeeks($i)->endOfWeek();
            return DeliveryOrderReceipt::whereBetween('received_date', [$start, $end])->count();
        });

        // Total item DO bulan ini per minggu
        $weeklyItems = collect(range(3, 0))->map(function ($i) {
            $start = Carbon::now()->subWeeks($i)->startOfWeek();
            $end = Carbon::now()->subWeeks($i)->endOfWeek();
            return DeliveryOrderReceiptDetail::whereHas('deliveryOrderReceipts', function ($query) use ($start, $end) {
                $query->whereBetween('received_date', [$start, $end]);
            })->count();
        });

        return [
            Stat::make("DO Hari Ini: $dayName", DeliveryOrderReceipt::whereDate('received_date', $today)->count() . ' DO')
                ->description("Data DO yang diterima hari ini")
                ->color('success')
                ->icon('heroicon-m-calendar-days')
                ->chart($doLast7Days->toArray()),

            Stat::make("DO Bulan Ini: $monthName", DeliveryOrderReceipt::whereBetween('received_date', [$startOfMonth, now()])->count() . ' DO')
                ->description('Total DO yang diterima bulan ini')
                ->color('info')
                ->icon('heroicon-m-calendar-days')
                ->chart($weeklyDO->toArray()),

            Stat::make("Total Item Bulan Ini: $monthName", DeliveryOrderReceiptDetail::whereHas('deliveryOrderReceipts', function ($query) use ($startOfMonth) {
                $query->whereBetween('received_date', [$startOfMonth, now()]);
            })->count() . ' Item')
                ->description("Item DO yang diterima bulan ini")
                ->color('warning')
                ->icon('heroicon-o-cube')
                ->chart($weeklyItems->toArray()),
        ];
    }
}
