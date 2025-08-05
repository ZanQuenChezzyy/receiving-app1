<?php

namespace App\Filament\Widgets;

use App\Models\DeliveryOrderReceipt;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Http;

class ItemMonitoring extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';
    public function table(Table $table): Table
    {
        return $table
            ->query(
                DeliveryOrderReceipt::query()->with([
                    'purchaseOrderTerbits',
                    'transmittalKirims.transmittalKembaliDetails.transmittalKembali',
                    'goodsReceiptSlips.goodsReceiptSlipDetails',
                    'returnDeliveryToVendors.returnDeliveryToVendorDetails',
                ])
            )
            ->columns([
                TextColumn::make('purchaseOrderTerbits.purchase_order_no')
                    ->label('No. PO')
                    ->color('primary')
                    ->icon('heroicon-s-document-text')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tanggal_proses')
                    ->label('Tanggal Proses')
                    ->getStateUsing(function ($record) {
                        $tanggalTerima = $record->received_date
                            ? Carbon::parse($record->received_date)->format('d/m/Y') : '-';

                        $tanggalKirim = optional($record->transmittalKirims->first()?->tanggal_kirim)
                            ? Carbon::parse($record->transmittalKirims->first()->tanggal_kirim)->format('d/m/Y') : '-';

                        $tanggalKembali = optional($record->transmittalKirims->first()?->transmittalKembaliDetails->first()?->transmittalKembali?->tanggal_kembali)
                            ? Carbon::parse($record->transmittalKirims->first()->transmittalKembaliDetails->first()->transmittalKembali->tanggal_kembali)->format('d/m/Y') : '-';

                        $tanggalGRS = optional($record->goodsReceiptSlips->first()?->tanggal_terbit)
                            ? Carbon::parse($record->goodsReceiptSlips->first()->tanggal_terbit)->format('d/m/Y') : '-';

                        $tanggalRDTV = optional($record->returnDeliveryToVendors->first()?->tanggal_terbit)
                            ? Carbon::parse($record->returnDeliveryToVendors->first()->tanggal_terbit)->format('d/m/Y') : '-';

                        return [
                            'Terima: ' . $tanggalTerima,
                            '103 Kirim: ' . $tanggalKirim,
                            '103 Kembali: ' . $tanggalKembali,
                            '105 GRS: ' . $tanggalGRS,
                            '124 RDTV: ' . $tanggalRDTV,
                        ];
                    })
                    ->listWithLineBreaks()
                    ->limitList(1)
                    ->expandableLimitedList()
                    ->bulleted()
                    ->alignLeft()
                    ->wrap(),


                TextColumn::make('lead_time_transmittal')
                    ->label('103 (Kirim → Kembali)')
                    ->icon('heroicon-s-clock')
                    ->color(fn($state) => match (true) {
                        str_contains($state, 'hari') && (int) filter_var($state, FILTER_SANITIZE_NUMBER_INT) <= 2 => 'success',
                        str_contains($state, 'hari') && (int) filter_var($state, FILTER_SANITIZE_NUMBER_INT) <= 5 => 'warning',
                        str_contains($state, 'hari') => 'danger',
                        default => 'gray',
                    })
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        $tanggalKirim = $record->transmittalKirims->first()?->tanggal_kirim;
                        $tanggalKembali = $record->transmittalKirims->first()?->transmittalKembaliDetails->first()?->transmittalKembali?->tanggal_kembali;

                        if (!$tanggalKirim || !$tanggalKembali) {
                            return '-';
                        }

                        $start = Carbon::parse($tanggalKirim);
                        $end = Carbon::parse($tanggalKembali);

                        // Ambil hari libur nasional dari API
                        try {
                            $response = Http::withOptions(['verify' => false])
                                ->get('https://api-harilibur.vercel.app/api');

                            $holidays = collect($response->json())
                                ->pluck('holiday_date')
                                ->toArray();
                        } catch (\Exception $e) {
                            // Jika gagal ambil hari libur, fallback ke hitung hari kerja biasa
                            $holidays = [];
                        }

                        $networkDays = 0;
                        $current = $start->copy();

                        while ($current->lte($end)) {
                            $isWeekend = $current->isWeekend();
                            $isHoliday = in_array($current->format('Y-m-d'), $holidays);

                            if (!$isWeekend && !$isHoliday) {
                                $networkDays++;
                            }

                            $current->addDay();
                        }

                        return "{$networkDays} hari";
                    }),

                TextColumn::make('lead_time_completion')
                    ->label('Dokumen (Terima → GRS/RDTV)')
                    ->icon('heroicon-s-calendar-days')
                    ->color(fn($state) => match (true) {
                        str_contains($state, 'hari') && (int) filter_var($state, FILTER_SANITIZE_NUMBER_INT) <= 2 => 'success',
                        str_contains($state, 'hari') && (int) filter_var($state, FILTER_SANITIZE_NUMBER_INT) <= 5 => 'warning',
                        str_contains($state, 'hari') => 'danger',
                        default => 'gray',
                    })
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        $tanggalTerima = $record->received_date;
                        $tanggalGRS = $record->goodsReceiptSlips->first()?->tanggal_terbit;
                        $tanggalRDTV = $record->returnDeliveryToVendors->first()?->tanggal_terbit;

                        if (!$tanggalTerima || (!$tanggalGRS && !$tanggalRDTV)) {
                            return '-';
                        }

                        $endDate = $tanggalGRS ?? $tanggalRDTV;
                        $start = Carbon::parse($tanggalTerima);
                        $end = Carbon::parse($endDate);

                        // Ambil hari libur nasional dari API
                        try {
                            $response = Http::withOptions(['verify' => false])
                                ->get('https://api-harilibur.vercel.app/api');

                            $holidays = collect($response->json())
                                ->pluck('holiday_date')
                                ->toArray();
                        } catch (\Exception $e) {
                            $holidays = [];
                        }

                        $networkDays = 0;
                        $current = $start->copy();

                        while ($current->lte($end)) {
                            $isWeekend = $current->isWeekend();
                            $isHoliday = in_array($current->format('Y-m-d'), $holidays);

                            if (!$isWeekend && !$isHoliday) {
                                $networkDays++;
                            }

                            $current->addDay();
                        }

                        return "{$networkDays} hari";
                    }),

                IconColumn::make('status_103')
                    ->label('103')
                    ->getStateUsing(
                        fn($record) =>
                        $record->transmittalKirims()->exists()
                    )
                    ->boolean()
                    ->trueIcon('heroicon-s-check-circle')
                    ->falseIcon('heroicon-s-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                IconColumn::make('status_105')
                    ->label('105')
                    ->getStateUsing(
                        fn($record) =>
                        $record->goodsReceiptSlips()->exists()
                    )
                    ->boolean()
                    ->trueIcon('heroicon-s-check-circle')
                    ->falseIcon('heroicon-s-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                IconColumn::make('status_124')
                    ->label('124')
                    ->getStateUsing(
                        fn($record) =>
                        $record->returnDeliveryToVendors()->exists()
                    )
                    ->boolean()
                    ->trueIcon('heroicon-s-check-circle')
                    ->falseIcon('heroicon-s-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ]);
    }
}
