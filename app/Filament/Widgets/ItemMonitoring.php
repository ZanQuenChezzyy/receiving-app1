<?php

namespace App\Filament\Widgets;

use App\Models\DeliveryOrderReceipt;
use Carbon\Carbon;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Http;

class ItemMonitoring extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';
    public function table(Table $table): Table
    {
        return $table
            ->heading('Monitoring Dokumen Receiving')
            ->query(
                DeliveryOrderReceipt::query()
                    ->with([
                        'purchaseOrderTerbits',
                        'transmittalKirims.transmittalKembaliDetails.transmittalKembali',
                        'goodsReceiptSlips.goodsReceiptSlipDetails',
                        'returnDeliveryToVendors.returnDeliveryToVendorDetails',
                    ])
                    ->leftJoin('transmittal_kirims', 'delivery_order_receipts.id', '=', 'transmittal_kirims.delivery_order_receipt_id')
                    ->leftJoin('goods_receipt_slips', 'delivery_order_receipts.id', '=', 'goods_receipt_slips.delivery_order_receipt_id')
                    ->leftJoin('return_delivery_to_vendors', 'delivery_order_receipts.id', '=', 'return_delivery_to_vendors.delivery_order_receipt_id')
                    ->select('delivery_order_receipts.*') // pastikan hanya kolom utama
                    ->orderByRaw("
                    CASE
                        WHEN transmittal_kirims.id IS NULL THEN 0
                        WHEN transmittal_kirims.id IS NOT NULL
                            AND (
                                SELECT COUNT(*)
                                FROM transmittal_kembali_details
                                WHERE transmittal_kembali_details.transmittal_kirim_id = transmittal_kirims.id
                            ) = 0
                        THEN 1
                        WHEN goods_receipt_slips.id IS NULL THEN 2
                        WHEN return_delivery_to_vendors.id IS NULL THEN 3
                        ELSE 4
                    END ASC
                ")
            )
            ->columns([
                IconColumn::make('status_103')
                    ->label('103')
                    ->getStateUsing(function ($record) {
                        // Ambil Transmittal Kirim pertama (jika ada)
                        $firstTransmittal = $record->transmittalKirims()->first();

                        if (!$firstTransmittal) {
                            return false; // Belum kirim
                        }

                        // Cek apakah transmittal kembali-nya sudah ada
                        return $firstTransmittal
                            ->transmittalKembaliDetails()
                            ->whereHas('transmittalKembali')
                            ->exists();
                    })
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
                    ->falseColor(fn($record) => match (true) {
                        $record->transmittalKirims()->exists() && $record->returnDeliveryToVendors()->exists() => 'gray',
                        !$record->transmittalKirims()->exists() && !$record->returnDeliveryToVendors()->exists() => 'danger',
                        default => 'danger',
                    }),

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
                    ->falseColor(fn($record) => match (true) {
                        $record->transmittalKirims()->exists() && $record->goodsReceiptSlips()->exists() => 'gray',
                        !$record->transmittalKirims()->exists() && !$record->goodsReceiptSlips()->exists() => 'danger',
                        default => 'danger', // kondisi lain, misalnya hanya salah satu true
                    }),

                TextColumn::make('purchaseOrderTerbits.purchase_order_no')
                    ->label('No. PO')
                    ->color('primary')
                    ->icon('heroicon-s-document-text'),

                TextColumn::make('tanggal_proses')
                    ->label('Tanggal Proses')
                    ->getStateUsing(function ($record) {
                        $tanggalTerima = $record->received_date
                            ? Carbon::parse($record->received_date)->format('d/m/Y')
                            : 'Belum diterima';

                        $firstTransmittal = collect($record->transmittalKirims)->first();
                        $tanggalKirim = $firstTransmittal?->tanggal_kirim;
                        $tanggalKirimFormatted = $tanggalKirim ? Carbon::parse($tanggalKirim)->format('d/m/Y') : 'Belum dikirim';

                        $firstKembaliDetail = collect($firstTransmittal?->transmittalKembaliDetails)->first();
                        $tanggalKembali = $firstKembaliDetail?->transmittalKembali?->tanggal_kembali;
                        $tanggalKembaliFormatted = $tanggalKembali ? Carbon::parse($tanggalKembali)->format('d/m/Y') : 'Belum kembali';

                        $tanggalGRS = collect($record->goodsReceiptSlips)->first()?->tanggal_terbit;
                        $tanggalRDTV = collect($record->returnDeliveryToVendors)->first()?->tanggal_terbit;

                        // Format tanggal
                        $tanggalGRSFormatted = $tanggalGRS ? Carbon::parse($tanggalGRS)->format('d/m/Y') : null;
                        $tanggalRDTVFormatted = $tanggalRDTV ? Carbon::parse($tanggalRDTV)->format('d/m/Y') : null;

                        // Logic teks yang lebih informatif
                        $textGRS = $tanggalGRSFormatted
                            ? '105 GRS: ' . $tanggalGRSFormatted
                            : ($tanggalRDTVFormatted ? '105 GRS: Tidak GRS' : '105 GRS: Belum GRS');

                        $textRDTV = $tanggalRDTVFormatted
                            ? '124 RDTV: ' . $tanggalRDTVFormatted
                            : ($tanggalGRSFormatted ? '124 RDTV: Tidak RDTV' : '124 RDTV: Belum RDTV');

                        return [
                            'Terima: ' . $tanggalTerima,
                            '103 Kirim: ' . $tanggalKirimFormatted,
                            '103 Kembali: ' . $tanggalKembaliFormatted,
                            $textGRS,
                            $textRDTV,
                        ];
                    })
                    ->listWithLineBreaks()
                    ->limitList(1)
                    ->expandableLimitedList()
                    ->bulleted()
                    ->alignLeft()
                    ->disabledClick()
                    ->wrap()
                    ->color(function ($record) {
                        $received = $record->received_date;
                        $kirim = collect($record->transmittalKirims)->first()?->tanggal_kirim;
                        $kembali = collect(collect($record->transmittalKirims)->first()?->transmittalKembaliDetails)->first()?->transmittalKembali?->tanggal_kembali;
                        $grs = collect($record->goodsReceiptSlips)->first()?->tanggal_terbit;
                        $rdtv = collect($record->returnDeliveryToVendors)->first()?->tanggal_terbit;

                        // Semua kosong
                        if (!$received && !$kirim && !$kembali && !$grs && !$rdtv) {
                            return 'gray';
                        }

                        // Jika sudah sampai 103 kembali dan salah satu GRS atau RDTV sudah ada
                        if ($received && $kirim && $kembali && ($grs || $rdtv)) {
                            return 'success';
                        }

                        // Jika sudah sampai 103 kembali tapi GRS & RDTV belum ada
                        if ($received && $kirim && $kembali && !$grs && !$rdtv) {
                            return 'danger';
                        }

                        // Selain itu (masih proses)
                        return 'warning';
                    }),

                TextColumn::make('lead_time_terima_ke_istek')
                    ->label('Status QC')
                    ->icon('heroicon-s-arrow-right-circle')
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        $tanggalTerima = $record->received_date;
                        $tanggalKirim = collect($record->transmittalKirims)->first()?->tanggal_kirim;

                        if (!$tanggalTerima) {
                            return 'Belum diterima';
                        }

                        if ($tanggalKirim) {
                            return 'Sudah dikirim';
                        }

                        $start = Carbon::parse($tanggalTerima);
                        $end = now();

                        // Ambil hari libur nasional dari API
                        try {
                            $response = Http::withOptions(['verify' => false])
                                ->get('https://api-harilibur.vercel.app/api');
                            $holidays = collect($response->json())->pluck('holiday_date')->toArray();
                        } catch (\Exception $e) {
                            $holidays = [];
                        }

                        $networkDays = 0;
                        $current = $start->copy();

                        while ($current->lte($end)) {
                            if (!$current->isWeekend() && !in_array($current->format('Y-m-d'), $holidays)) {
                                $networkDays++;
                            }
                            $current->addDay();
                        }

                        return "{$networkDays} hari (Belum dikirim)";
                    })
                    ->color(function ($state) {
                        if (str_contains($state, 'Belum diterima')) {
                            return 'gray';
                        }

                        if ($state === 'Sudah dikirim') {
                            return 'success';
                        }

                        $days = (int) filter_var($state, FILTER_SANITIZE_NUMBER_INT);

                        return match (true) {
                            $days <= 2 => 'warning',
                            $days > 2 => 'danger',
                            default => 'gray',
                        };
                    }),


                TextColumn::make('lead_time_transmittal')
                    ->label('Leadtime QC')
                    ->icon('heroicon-s-clock')
                    ->color(fn($state) => match (true) {
                        str_contains($state, 'hari') && (int) filter_var($state, FILTER_SANITIZE_NUMBER_INT) <= 2 => 'success',
                        str_contains($state, 'hari') && (int) filter_var($state, FILTER_SANITIZE_NUMBER_INT) <= 5 => 'warning',
                        str_contains($state, 'hari') => 'danger',
                        default => 'gray',
                    })
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        $tanggalKirim = optional($record->transmittalKirims)->first()?->tanggal_kirim;
                        $tanggalKembali = optional($record->transmittalKirims)->first()?->transmittalKembaliDetails->first()?->transmittalKembali?->tanggal_kembali;

                        if (!$tanggalKirim)
                            return 'Belum dikirim';
                        if (!$tanggalKembali)
                            return 'Belum kembali';

                        $start = Carbon::parse($tanggalKirim);
                        $end = Carbon::parse($tanggalKembali);

                        try {
                            $response = Http::withOptions(['verify' => false])
                                ->get('https://api-harilibur.vercel.app/api');
                            $holidays = collect($response->json())->pluck('holiday_date')->toArray();
                        } catch (\Exception $e) {
                            $holidays = [];
                        }

                        $networkDays = 0;
                        $current = $start->copy();
                        while ($current->lte($end)) {
                            if (!$current->isWeekend() && !in_array($current->format('Y-m-d'), $holidays)) {
                                $networkDays++;
                            }
                            $current->addDay();
                        }

                        return "{$networkDays} hari";
                    })
                    ->color(fn($state) => match (true) {
                        str_contains($state, 'Belum') => 'danger',
                        is_numeric(filter_var($state, FILTER_SANITIZE_NUMBER_INT)) && (int) filter_var($state, FILTER_SANITIZE_NUMBER_INT) <= 2 => 'success',
                        is_numeric(filter_var($state, FILTER_SANITIZE_NUMBER_INT)) && (int) filter_var($state, FILTER_SANITIZE_NUMBER_INT) <= 5 => 'warning',
                        is_numeric(filter_var($state, FILTER_SANITIZE_NUMBER_INT)) => 'success', // sudah selesai tapi lewat 5 hari
                        default => 'gray',
                    }),

                TextColumn::make('lead_time_completion')
                    ->label('Leadtime GRS/RDTV')
                    ->icon('heroicon-s-calendar-days')
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        $tanggalTerima = $record->received_date;
                        $tanggalGRS = collect($record->goodsReceiptSlips)->first()?->tanggal_terbit;
                        $tanggalRDTV = collect($record->returnDeliveryToVendors)->first()?->tanggal_terbit;

                        if (!$tanggalTerima) {
                            return 'Belum diterima';
                        }

                        if (!$tanggalGRS && !$tanggalRDTV) {
                            return 'Belum GRS/RDTV';
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
                            if (!$current->isWeekend() && !in_array($current->format('Y-m-d'), $holidays)) {
                                $networkDays++;
                            }

                            $current->addDay();
                        }

                        return "{$networkDays} hari";
                    })
                    ->color(fn($state) => match (true) {
                        str_contains($state, 'Belum') => 'danger',
                        is_numeric(filter_var($state, FILTER_SANITIZE_NUMBER_INT)) && (int) filter_var($state, FILTER_SANITIZE_NUMBER_INT) <= 2 => 'success',
                        is_numeric(filter_var($state, FILTER_SANITIZE_NUMBER_INT)) && (int) filter_var($state, FILTER_SANITIZE_NUMBER_INT) <= 5 => 'warning',
                        is_numeric(filter_var($state, FILTER_SANITIZE_NUMBER_INT)) => 'success',
                        default => 'gray',
                    }),
            ])
            ->recordAction('view')
            ->filters([
                TernaryFilter::make('masih_proses')
                    ->label('Status Proses')
                    ->placeholder('Semua')
                    ->trueLabel('Masih Proses')
                    ->falseLabel('Sudah Selesai')
                    ->queries(
                        true: fn(Builder $query) =>
                        $query->whereDoesntHave('goodsReceiptSlips')
                            ->whereDoesntHave('returnDeliveryToVendors'),

                        false: fn(Builder $query) =>
                        $query->where(function ($query) {
                            $query->whereHas('goodsReceiptSlips')
                                ->orWhereHas('returnDeliveryToVendors');
                        }),

                        blank: fn(Builder $query) => $query
                    )
                    ->native(false),

                TernaryFilter::make('status_103')
                    ->label('Status 103')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah')
                    ->falseLabel('Belum')
                    ->queries(
                        true: fn(Builder $query) => $query->whereHas('transmittalKirims'),
                        false: fn(Builder $query) => $query->whereDoesntHave('transmittalKirims'),
                        blank: fn(Builder $query) => $query,
                    )
                    ->native(false),

                TernaryFilter::make('status_105')
                    ->label('Status 105')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah')
                    ->falseLabel('Belum')
                    ->queries(
                        true: fn(Builder $query) => $query->whereHas('goodsReceiptSlips'),
                        false: fn(Builder $query) => $query->whereDoesntHave('goodsReceiptSlips'),
                        blank: fn(Builder $query) => $query,
                    )
                    ->native(false),

                TernaryFilter::make('status_124')
                    ->label('Status 124')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah')
                    ->falseLabel('Belum')
                    ->queries(
                        true: fn(Builder $query) => $query->whereHas('returnDeliveryToVendors'),
                        false: fn(Builder $query) => $query->whereDoesntHave('returnDeliveryToVendors'),
                        blank: fn(Builder $query) => $query,
                    )
                    ->native(false),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->filtersTriggerAction(
                fn(Action $action) => $action
                    ->button()
                    ->label('Filter'),
            )
            ->actions([
                ViewAction::make()
                    ->label('Detail')
                    ->button()
                    ->infolist(fn(DeliveryOrderReceipt $record) => [
                        Grid::make(2)->schema([
                            Section::make('Status Proses')
                                ->description('Menampilkan status proses penerimaan dan transmittal dari dokumen pengadaan berdasarkan nomor DO.')
                                ->collapsible()
                                ->schema([
                                    Grid::make(3)
                                        ->schema([
                                            TextEntry::make('purchaseOrderTerbits.purchase_order_no')
                                                ->label('No. PO'),

                                            TextEntry::make('nomor_do')
                                                ->label('No. DO')
                                                ->getStateUsing(fn($record) => $record->nomor_do ?? '-'),

                                            TextEntry::make('receivedBy.name')
                                                ->label('Diterima Oleh')
                                                ->getStateUsing(fn($record) => $record->receivedBy->name ?? '-'),

                                            TextEntry::make('received_date')
                                                ->label('Tanggal Diterima')
                                                ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->translatedFormat('l, d F Y') : 'Belum diterima'),

                                            TextEntry::make('transmittalKirims.0.tanggal_kirim')
                                                ->label('Tanggl Kirim QC')
                                                ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->translatedFormat('l, d F Y') : 'Belum dikirim')
                                                ->placeholder('Belum dikirim'),

                                            TextEntry::make('transmittalKirims.0.transmittalKembaliDetails.0.transmittalKembali.tanggal_kembali')
                                                ->label('Tanggal Kembali QC')
                                                ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->translatedFormat('l, d F Y') : 'Belum kembali')
                                                ->placeholder('Belum kembali'),
                                        ]),
                                ])
                                ->columns(2),
                        ]),

                        Section::make('Lead Time')
                            ->description('Detail durasi proses dokumen')
                            ->collapsed()
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        TextEntry::make('lead_time_terima')
                                            ->label('Status QC')
                                            ->getStateUsing(function ($record) {
                                                $start = $record->received_date;
                                                $end = optional($record->transmittalKirims)->first()?->tanggal_kirim;
                                                $result = static::hitungHariKerja($start, $end);
                                                return is_numeric($result) ? "{$result} hari" : $result;
                                            }),

                                        TextEntry::make('lead_time_kirim_kembali')
                                            ->label('Leadtime QC')
                                            ->getStateUsing(function ($record) {
                                                $start = optional($record->transmittalKirims)->first()?->tanggal_kirim;
                                                $end = optional($record->transmittalKirims)->first()?->transmittalKembaliDetails->first()?->transmittalKembali?->tanggal_kembali;
                                                $result = static::hitungHariKerja($start, $end);
                                                return is_numeric($result) ? "{$result} hari" : $result;
                                            }),

                                        TextEntry::make('lead_time_completion')
                                            ->label('Leadtime GRS/RDTV')
                                            ->getStateUsing(function ($record) {
                                                $start = $record->received_date;
                                                $end = collect([
                                                    $record->goodsReceiptSlips->first()?->tanggal_terbit,
                                                    $record->returnDeliveryToVendors->first()?->tanggal_terbit
                                                ])->filter()->sort()->first();
                                                $result = static::hitungHariKerja($start, $end);
                                                return is_numeric($result) ? "{$result} hari" : $result;
                                            }),
                                    ])
                            ]),

                        Section::make('105 - Goods Receipt Slip (GRS)')
                            ->collapsed()
                            ->description('Item yang sudah masuk GRS')
                            ->schema([
                                RepeatableEntry::make('goodsReceiptSlips')
                                    ->label('')
                                    ->schema([
                                        TextEntry::make('tanggal_terbit')
                                            ->label('Tanggal Terbit GRS')
                                            ->formatStateUsing(fn($state) => Carbon::parse($state)->translatedFormat('l, d F Y')),

                                        RepeatableEntry::make('goodsReceiptSlipDetails')
                                            ->label('Item GRS')
                                            ->getStateUsing(fn($record) => $record->goodsReceiptSlipDetails)
                                            ->schema([
                                                Grid::make(3)
                                                    ->schema([
                                                        TextEntry::make('item_no')->label('Item No'),
                                                        TextEntry::make('material_code')->label('Material Code'),
                                                        TextEntry::make('description')->label('Deskripsi')->limit(20),
                                                    ]),
                                            ]),
                                    ])
                                    ->getStateUsing(fn($record) => $record->goodsReceiptSlips)
                                    ->hidden(fn($record) => $record->goodsReceiptSlips->isEmpty()),

                                TextEntry::make('')
                                    ->label('')
                                    ->default('Tidak ada item GRS')
                                    ->visible(fn($record) => $record->goodsReceiptSlips->isEmpty())
                                    ->color('danger'),
                            ]),

                        Section::make('124 - Return Delivery to Vendor (RDTV)')
                            ->collapsed()
                            ->description('Item yang dikembalikan ke vendor')
                            ->schema([
                                RepeatableEntry::make('returnDeliveryToVendors')
                                    ->label('')
                                    ->schema([
                                        TextEntry::make('tanggal_terbit')
                                            ->label('Tanggal Terbit RDTV')
                                            ->formatStateUsing(fn($state) => Carbon::parse($state)->translatedFormat('l, d F Y')),

                                        RepeatableEntry::make('returnDeliveryToVendorDetails')
                                            ->label('Item RDTV')
                                            ->getStateUsing(fn($record) => $record->returnDeliveryToVendorDetails)
                                            ->schema([
                                                Grid::make(3)
                                                    ->schema([
                                                        TextEntry::make('item_no')->label('Item No'),
                                                        TextEntry::make('material_code')->label('Material Code'),
                                                        TextEntry::make('description')->label('Deskripsi')->limit(20),
                                                    ])
                                            ]),
                                    ])
                                    ->getStateUsing(fn($record) => $record->returnDeliveryToVendors)
                                    ->hidden(fn($record) => $record->returnDeliveryToVendors->isEmpty()),

                                TextEntry::make('')
                                    ->label('')
                                    ->default('Tidak ada item RDTV')
                                    ->visible(fn($record) => $record->returnDeliveryToVendors->isEmpty())
                                    ->color('danger'),
                            ]),
                    ])
            ], position: ActionsPosition::AfterCells);
    }

    protected static function hitungHariKerja($start, $end): string|int
    {
        if (!$start || !$end) {
            return 'Tidak Ada';
        }

        try {
            $response = Http::withOptions(['verify' => false])
                ->get('https://api-harilibur.vercel.app/api');
            $holidays = collect($response->json())->pluck('holiday_date')->toArray();
        } catch (\Exception $e) {
            $holidays = [];
        }

        $start = Carbon::parse($start);
        $end = Carbon::parse($end);

        $networkDays = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            if (!$current->isWeekend() && !in_array($current->format('Y-m-d'), $holidays)) {
                $networkDays++;
            }
            $current->addDay();
        }

        return $networkDays;
    }
}
