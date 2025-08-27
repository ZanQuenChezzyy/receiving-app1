<?php

namespace App\Filament\Widgets;

use App\Models\ApprovalVpKembali;
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
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ItemMonitoring extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';
    protected static ?array $cachedHolidays = null;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Monitoring Dokumen Receiving')
            ->deferLoading()
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->query(
                DeliveryOrderReceipt::query()
                    ->select('delivery_order_receipts.*')

                    // ⬇️ pakai selectRaw dengan alias yang eksplisit
                    ->selectRaw("
            EXISTS (
                SELECT 1
                FROM transmittal_kirims tk
                WHERE tk.delivery_order_receipt_id = delivery_order_receipts.id
            ) AS has_kirim,

            EXISTS (
                SELECT 1
                FROM transmittal_kirims tk
                JOIN transmittal_kembali_details tkd ON tkd.transmittal_kirim_id = tk.id
                JOIN transmittal_kembalis tkk ON tkk.id = tkd.transmittal_kembali_id
                WHERE tk.delivery_order_receipt_id = delivery_order_receipts.id
            ) AS has_kembali,

            EXISTS (
                SELECT 1
                FROM goods_receipt_slips grs
                WHERE grs.delivery_order_receipt_id = delivery_order_receipts.id
            ) AS has_grs,

            EXISTS (
                SELECT 1
                FROM return_delivery_to_vendors rdtv
                WHERE rdtv.delivery_order_receipt_id = delivery_order_receipts.id
            ) AS has_rdtv,

            (SELECT MIN(tk.tanggal_kirim)
             FROM transmittal_kirims tk
             WHERE tk.delivery_order_receipt_id = delivery_order_receipts.id) AS tgl_kirim_qc,

            (SELECT MIN(tkk.tanggal_kembali)
             FROM transmittal_kirims tk
             JOIN transmittal_kembali_details tkd ON tkd.transmittal_kirim_id = tk.id
             JOIN transmittal_kembalis tkk ON tkk.id = tkd.transmittal_kembali_id
             WHERE tk.delivery_order_receipt_id = delivery_order_receipts.id) AS tgl_kembali_qc,

            (SELECT MIN(grs.tanggal_terbit)
             FROM goods_receipt_slips grs
             WHERE grs.delivery_order_receipt_id = delivery_order_receipts.id) AS tgl_grs,

            (SELECT MIN(rdtv.tanggal_terbit)
             FROM return_delivery_to_vendors rdtv
             WHERE rdtv.delivery_order_receipt_id = delivery_order_receipts.id) AS tgl_rdtv
        ")

                    ->with([
                        'purchaseOrderTerbits:id,purchase_order_no',
                        'receivedBy:id,name',
                    ])

                    // aman: ORDER BY pakai alias di atas
                    ->orderByRaw("
            CASE
                WHEN has_kirim = 0 THEN 0
                WHEN has_kirim = 1 AND has_kembali = 0 THEN 1
                WHEN has_grs = 0 THEN 2
                WHEN has_rdtv = 0 THEN 3
                ELSE 4
            END ASC
        ")
            )
            ->columns([
                IconColumn::make('status_103')
                    ->label('103')
                    ->state(fn($record): bool => (bool) ($record->has_kirim && $record->has_kembali))
                    ->boolean()
                    ->trueIcon('heroicon-s-check-circle')
                    ->falseIcon('heroicon-s-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                IconColumn::make('status_105')
                    ->label('105')
                    ->state(fn($record): bool => (bool) $record->has_grs)
                    ->boolean()
                    ->trueIcon('heroicon-s-check-circle')
                    ->falseIcon('heroicon-s-x-circle')
                    ->trueColor('success')
                    ->falseColor(fn(bool $state, $record) => match (true) {
                        $record->has_kirim && $record->has_rdtv => 'gray',
                        !$record->has_kirim && !$record->has_rdtv => 'danger',
                        default => 'danger',
                    }),

                IconColumn::make('status_124')
                    ->label('124')
                    ->state(fn($record): bool => (bool) $record->has_rdtv)
                    ->boolean()
                    ->trueIcon('heroicon-s-check-circle')
                    ->falseIcon('heroicon-s-x-circle')
                    ->trueColor('success')
                    ->falseColor(fn(bool $state, $record) => match (true) {
                        $record->has_kirim && $record->has_grs => 'gray',
                        !$record->has_kirim && !$record->has_grs => 'danger',
                        default => 'danger',
                    }),

                TextColumn::make('purchaseOrderTerbits.purchase_order_no')
                    ->label('No. PO')
                    ->color('primary')
                    ->icon('heroicon-s-document-text'),

                TextColumn::make('tahapan')
                    ->label('Tahapan')
                    ->placeholder('Tidak Ada')
                    ->color('info')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('tanggal_proses')
                    ->label('Tanggal Proses')
                    ->state(function ($record) {
                        $terima = $record->received_date ? Carbon::parse($record->received_date)->format('d/m/Y') : 'Belum diterima';
                        $kirim = $record->tgl_kirim_qc ? Carbon::parse($record->tgl_kirim_qc)->format('d/m/Y') : 'Belum dikirim';
                        $kembali = $record->tgl_kembali_qc ? Carbon::parse($record->tgl_kembali_qc)->format('d/m/Y') : 'Belum kembali';
                        $grs = $record->tgl_grs ? Carbon::parse($record->tgl_grs)->format('d/m/Y') : null;
                        $rdtv = $record->tgl_rdtv ? Carbon::parse($record->tgl_rdtv)->format('d/m/Y') : null;

                        $textGRS = $grs ? "105 GRS: $grs" : ($rdtv ? '105 GRS: Tidak GRS' : '105 GRS: Belum GRS');
                        $textRDTV = $rdtv ? "124 RDTV: $rdtv" : ($grs ? '124 RDTV: Tidak RDTV' : '124 RDTV: Belum RDTV');

                        return [
                            "Terima: $terima",
                            "103 Kirim: $kirim",
                            "103 Kembali: $kembali",
                            $textGRS,
                            $textRDTV,
                        ];
                    })
                    ->listWithLineBreaks()
                    ->limitList(1)
                    ->expandableLimitedList()
                    ->bulleted()
                    ->disabledClick()
                    ->wrap()
                    ->color(function ($record) {
                        $received = (bool) $record->received_date;
                        $kirim = (bool) $record->tgl_kirim_qc;
                        $kembali = (bool) $record->tgl_kembali_qc;
                        $grs = (bool) $record->tgl_grs;
                        $rdtv = (bool) $record->tgl_rdtv;

                        if (!$received && !$kirim && !$kembali && !$grs && !$rdtv)
                            return 'gray';
                        if ($received && $kirim && $kembali && ($grs || $rdtv))
                            return 'success';
                        if ($received && $kirim && $kembali && !$grs && !$rdtv)
                            return 'danger';
                        return 'warning';
                    }),

                // ========== Lead time ==========
                TextColumn::make('lead_time_terima_ke_istek')
                    ->label('Status QC')
                    ->icon('heroicon-s-arrow-right-circle')
                    ->alignCenter()
                    ->state(function ($record) {
                        if (!$record->received_date)
                            return 'Pending';
                        if ($record->tgl_kirim_qc)
                            return 'Selesai';
                        $days = static::hitungHariKerja($record->received_date, now());
                        return "{$days} hari (Pending)";
                    })
                    ->color(function (string $state) {
                        if (str_contains($state, 'Pending'))
                            return 'gray';
                        if ($state === 'Selesai')
                            return 'success';
                        $days = (int) filter_var($state, FILTER_SANITIZE_NUMBER_INT);
                        return $days <= 2 ? 'warning' : 'danger';
                    }),

                TextColumn::make('lead_time_transmittal')
                    ->label('Leadtime QC')
                    ->icon('heroicon-s-clock')
                    ->alignCenter()
                    ->state(function ($record) {
                        if (!$record->tgl_kirim_qc)
                            return 'Belum dikirim';
                        if (!$record->tgl_kembali_qc)
                            return 'Belum kembali';
                        $days = static::hitungHariKerja($record->tgl_kirim_qc, $record->tgl_kembali_qc);
                        return "{$days} hari";
                    })
                    ->color(function (string $state) {
                        if (str_contains($state, 'Belum'))
                            return 'danger';
                        $days = (int) filter_var($state, FILTER_SANITIZE_NUMBER_INT);
                        return $days <= 2 ? 'success' : ($days <= 5 ? 'warning' : 'success');
                    }),

                TextColumn::make('lead_time_completion')
                    ->label('Leadtime GRS/RDTV')
                    ->icon('heroicon-s-calendar-days')
                    ->alignCenter()
                    ->state(function ($record) {
                        if (!$record->received_date)
                            return 'Belum diterima';
                        if (!$record->tgl_grs && !$record->tgl_rdtv)
                            return 'Belum GRS/RDTV';
                        $end = $record->tgl_grs ?? $record->tgl_rdtv;
                        $days = static::hitungHariKerja($record->received_date, $end);
                        return "{$days} hari";
                    })
                    ->color(function (string $state) {
                        if (str_contains($state, 'Belum'))
                            return 'danger';
                        $days = (int) filter_var($state, FILTER_SANITIZE_NUMBER_INT);
                        return $days <= 2 ? 'success' : ($days <= 5 ? 'warning' : 'success');
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
                        true: fn(Builder $q) => $q->where('has_grs', 0)->where('has_rdtv', 0),
                        false: fn(Builder $q) => $q->where(fn($qq) => $qq->where('has_grs', 1)->orWhere('has_rdtv', 1)),
                        blank: fn(Builder $q) => $q
                    )
                    ->native(false),

                TernaryFilter::make('status_103')
                    ->label('Status 103')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah')
                    ->falseLabel('Belum')
                    ->queries(
                        true: fn(Builder $q) => $q->where('has_kirim', 1),
                        false: fn(Builder $q) => $q->where('has_kirim', 0),
                        blank: fn(Builder $q) => $q,
                    )
                    ->native(false),

                TernaryFilter::make('status_105')
                    ->label('Status 105')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah')
                    ->falseLabel('Belum')
                    ->queries(
                        true: fn(Builder $q) => $q->where('has_grs', 1),
                        false: fn(Builder $q) => $q->where('has_grs', 0),
                        blank: fn(Builder $q) => $q,
                    )
                    ->native(false),

                TernaryFilter::make('status_124')
                    ->label('Status 124')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah')
                    ->falseLabel('Belum')
                    ->queries(
                        true: fn(Builder $q) => $q->where('has_rdtv', 1),
                        false: fn(Builder $q) => $q->where('has_rdtv', 0),
                        blank: fn(Builder $q) => $q,
                    )
                    ->native(false),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->filtersTriggerAction(fn(Action $a) => $a->button()->label('Filter'))
            ->actions([
                Action::make('view')
                    ->label('Detail')
                    ->button()
                    ->color('gray')
                    ->icon('heroicon-m-eye')
                    ->mountUsing(function (DeliveryOrderReceipt $record) {
                        $record->loadMissing([
                            'transmittalKirims.transmittalKembaliDetails.transmittalKembali:id,tanggal_kembali',
                            'goodsReceiptSlips:id,delivery_order_receipt_id,tanggal_terbit',
                            'goodsReceiptSlips.goodsReceiptSlipDetails:id,goods_receipt_slip_id,item_no,material_code,description',
                            'returnDeliveryToVendors:id,delivery_order_receipt_id,tanggal_terbit',
                            'returnDeliveryToVendors.returnDeliveryToVendorDetails:id,return_delivery_to_vendor_id,item_no,material_code,description',
                        ]);
                    })
                    ->infolist(fn(DeliveryOrderReceipt $record) => [
                        Section::make('Detail Dokumen')
                            ->description('Informasi dasar dokumen pengadaan.')
                            ->collapsed()
                            ->schema([
                                Grid::make(3)->schema([
                                    TextEntry::make('purchaseOrderTerbits.purchase_order_no')->label('No. PO'),

                                    TextEntry::make('nomor_do')
                                        ->label('No. DO')
                                        ->state(fn(DeliveryOrderReceipt $record) => $record->nomor_do ?? '-'),

                                    TextEntry::make('receivedBy.name')
                                        ->label('Diterima Oleh')
                                        ->state(fn(DeliveryOrderReceipt $record) => $record->receivedBy->name ?? '-'),
                                ]),
                            ]),

                        Section::make('Tanggal Proses')
                            ->description('Menampilkan status proses penerimaan dan transmittal dokumen.')
                            ->collapsible()
                            ->schema([
                                Grid::make(3)->schema([
                                    TextEntry::make('received_date')
                                        ->label('Tanggal Diterima')
                                        ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->translatedFormat('l, d F Y') : 'Belum diterima'),

                                    TextEntry::make('tgl_kirim_qc')
                                        ->label('Tanggal Kirim QC')
                                        ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->translatedFormat('l, d F Y') : 'Belum dikirim'),

                                    TextEntry::make('tgl_kembali_qc')
                                        ->label('Tanggal Kembali QC')
                                        ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->translatedFormat('l, d F Y') : 'Belum kembali'),

                                    TextEntry::make('tanggal_kirim_approval_vp')
                                        ->label('Tanggal Kirim Approval VP')
                                        ->state(function (DeliveryOrderReceipt $record) {
                                            $tgl = \App\Models\ApprovalVpKirim::where('code', $record->do_code)->value('tanggal_kirim');
                                            return $tgl ? Carbon::parse($tgl)->translatedFormat('l, d F Y') : 'Belum dikirim';
                                        }),

                                    TextEntry::make('tanggal_kembali_approval_vp')
                                        ->label('Tanggal Kembali Approval VP')
                                        ->state(function (DeliveryOrderReceipt $record) {
                                            $tgl = ApprovalVpKembali::query()
                                                ->whereIn('id', function ($q) use ($record) {
                                                    $q->select('approval_vp_kembali_id')
                                                        ->from('approval_vp_kembali_details')
                                                        ->where('code', $record->do_code);
                                                })
                                                ->value('tanggal_kembali');
                                            return $tgl ? Carbon::parse($tgl)->translatedFormat('l, d F Y') : 'Belum dikirim';
                                        }),
                                ]),
                            ]),

                        Section::make('Lead Time')
                            ->description('Detail durasi proses dokumen')
                            ->collapsed()
                            ->schema([
                                Grid::make(4)->schema([
                                    TextEntry::make('lead_time_terima')
                                        ->label('Status QC')
                                        ->state(function (DeliveryOrderReceipt $record) {
                                            $res = static::hitungHariKerja($record->received_date, $record->tgl_kirim_qc);
                                            return is_numeric($res) ? "{$res} hari" : $res;
                                        }),

                                    TextEntry::make('lead_time_kirim_kembali')
                                        ->label('Leadtime QC')
                                        ->state(function (DeliveryOrderReceipt $record) {
                                            $res = static::hitungHariKerja($record->tgl_kirim_qc, $record->tgl_kembali_qc);
                                            return is_numeric($res) ? "{$res} hari" : $res;
                                        }),

                                    TextEntry::make('lead_time_completion')
                                        ->label('Leadtime GRS/RDTV')
                                        ->state(function (DeliveryOrderReceipt $record) {
                                            $end = collect([$record->tgl_grs, $record->tgl_rdtv])->filter()->sort()->first();
                                            $res = static::hitungHariKerja($record->received_date, $end);
                                            return is_numeric($res) ? "{$res} hari" : $res;
                                        }),

                                    TextEntry::make('lead_time_vp')
                                        ->label('Leadtime Approval VP')
                                        ->state(function (DeliveryOrderReceipt $record) {
                                            $kirim = \App\Models\ApprovalVpKirim::whereHas(
                                                'approvalVpKembaliDetails',
                                                fn($q) => $q->whereHas('approvalVpKembali')
                                            )->first();

                                            $res = static::hitungHariKerja(
                                                $kirim?->tanggal_kirim,
                                                $kirim?->approvalVpKembaliDetails->first()?->approvalVpKembali?->tanggal_kembali
                                            );

                                            return is_numeric($res) ? "{$res} hari" : $res;
                                        }),
                                ]),
                            ]),

                        Section::make('105 - Goods Receipt Slip (GRS)')
                            ->collapsed()
                            ->description('Item yang sudah masuk GRS')
                            ->schema([
                                RepeatableEntry::make('goodsReceiptSlips')
                                    ->label('')
                                    ->state(fn(DeliveryOrderReceipt $record) => $record->goodsReceiptSlips)   // gunakan $record
                                    ->schema([
                                        TextEntry::make('tanggal_terbit')
                                            ->label('Tanggal Terbit GRS')
                                            ->formatStateUsing(fn($state) => Carbon::parse($state)->translatedFormat('l, d F Y')),

                                        RepeatableEntry::make('goodsReceiptSlipDetails')
                                            ->label('Item GRS')
                                            ->state(fn($record) => $record->goodsReceiptSlipDetails) // tetap gunakan $record (bukan $r)
                                            ->schema([
                                                Grid::make(3)->schema([
                                                    TextEntry::make('item_no')->label('Item No'),
                                                    TextEntry::make('material_code')->label('Material Code'),
                                                    TextEntry::make('description')->label('Deskripsi')->limit(20),
                                                ]),
                                            ]),
                                    ])
                                    ->hidden(fn(DeliveryOrderReceipt $record) => $record->goodsReceiptSlips->isEmpty()),

                                TextEntry::make('')
                                    ->label('')
                                    ->default('Tidak ada item GRS')
                                    ->visible(fn(DeliveryOrderReceipt $record) => $record->goodsReceiptSlips->isEmpty())
                                    ->color('danger'),
                            ]),

                        Section::make('124 - Return Delivery to Vendor (RDTV)')
                            ->collapsed()
                            ->description('Item yang dikembalikan ke vendor')
                            ->schema([
                                RepeatableEntry::make('returnDeliveryToVendors')
                                    ->label('')
                                    ->state(fn(DeliveryOrderReceipt $record) => $record->returnDeliveryToVendors)
                                    ->schema([
                                        TextEntry::make('tanggal_terbit')
                                            ->label('Tanggal Terbit RDTV')
                                            ->formatStateUsing(fn($state) => Carbon::parse($state)->translatedFormat('l, d F Y')),

                                        RepeatableEntry::make('returnDeliveryToVendorDetails')
                                            ->label('Item RDTV')
                                            ->state(fn($record) => $record->returnDeliveryToVendorDetails)
                                            ->schema([
                                                Grid::make(3)->schema([
                                                    TextEntry::make('item_no')->label('Item No'),
                                                    TextEntry::make('material_code')->label('Material Code'),
                                                    TextEntry::make('description')->label('Deskripsi')->limit(20),
                                                ]),
                                            ]),
                                    ])
                                    ->hidden(fn(DeliveryOrderReceipt $record) => $record->returnDeliveryToVendors->isEmpty()),

                                TextEntry::make('')
                                    ->label('')
                                    ->default('Tidak ada item RDTV')
                                    ->visible(fn(DeliveryOrderReceipt $record) => $record->returnDeliveryToVendors->isEmpty())
                                    ->color('danger'),
                            ]),
                    ]),
            ], position: ActionsPosition::AfterCells);
    }

    protected static function getHolidays(): array
    {
        if (static::$cachedHolidays !== null) {
            return static::$cachedHolidays;
        }

        $year = now()->year;
        $key = "holidays:id:$year";

        static::$cachedHolidays = Cache::remember($key, now()->endOfYear()->diffInSeconds(), function () {
            try {
                $res = Http::withOptions(['verify' => false])->timeout(4)->get('https://api-harilibur.vercel.app/api');
                return collect($res->json())->pluck('holiday_date')->toArray();
            } catch (\Throwable $e) {
                return [];
            }
        });

        return static::$cachedHolidays;
    }

    protected static function hitungHariKerja($start, $end): string|int
    {
        if (!$start || !$end)
            return 'Tidak Ada';

        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->startOfDay();
        if ($end->lt($start))
            return 0;

        $holidays = static::getHolidays();

        $days = 0;
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            if (!$cursor->isWeekend() && !in_array($cursor->format('Y-m-d'), $holidays, true)) {
                $days++;
            }
            $cursor->addDay();
        }

        return $days;
    }
}
