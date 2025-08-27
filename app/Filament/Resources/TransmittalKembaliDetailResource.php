<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\TransmittalIstek;
use App\Filament\Resources\TransmittalKembaliDetailResource\Pages;
use App\Filament\Resources\TransmittalKembaliDetailResource\RelationManagers;
use App\Models\TransmittalKembaliDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class TransmittalKembaliDetailResource extends Resource
{
    protected static ?string $model = TransmittalKembaliDetail::class;
    protected static ?string $cluster = TransmittalIstek::class;
    protected static ?string $label = 'Detail Dokumen';
    protected static ?string $navigationGroup = 'Dokumen Transmittal';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $activeNavigationIcon = 'heroicon-s-clipboard-document-check';
    protected static ?int $navigationSort = 3;
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::count() < 2 ? 'danger' : 'info';
    }
    protected static ?string $navigationBadgeTooltip = 'Total Detail Dokumen Istek';
    protected static ?string $slug = 'detail-dokumen-istek';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('transmittal_kembali_id')
                    ->relationship('transmittalKembali', 'id')
                    ->required(),
                Forms\Components\Select::make('transmittal_kirim_id')
                    ->relationship('transmittalKirim', 'id')
                    ->required(),
                Forms\Components\TextInput::make('do_receipt_detail_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(50),
                Forms\Components\TextInput::make('code_103')
                    ->required()
                    ->maxLength(15),
                Forms\Components\TextInput::make('total_item')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // Tunda fetch sampai tabel terlihat & batasi per halaman
            ->deferLoading()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)

            // Eager load relasi + pilih kolom minimal agar anti N+1
            ->modifyQueryUsing(function (Builder $query) {
                return $query
                    ->with([
                        'transmittalKirim:id,delivery_order_receipt_id,tanggal_kirim',
                        'transmittalKembali:id,tanggal_kembali',
                        'transmittalKirim.deliveryOrderReceipts:id,purchase_order_terbit_id,nomor_do',
                        'transmittalKirim.deliveryOrderReceipts.purchaseOrderTerbits:id,purchase_order_no',
                    ])
                    ->latest(); // created_at desc
            })

            ->columns([
                // TAMPIL
                TextColumn::make('transmittalKirim.deliveryOrderReceipts.purchaseOrderTerbits.purchase_order_no')
                    ->label('Nomor PO')
                    ->icon('heroicon-s-document-text')
                    ->color('primary')
                    ->searchable() // ikut global search
                    ->sortable()
                    ->description(fn($record) => 'Kode 103: ' . ($record->code_103 ?? '-')),

                TextColumn::make('total_item')
                    ->label('Total Item')
                    ->numeric()
                    ->sortable()
                    ->color('info')
                    ->suffix(' Item')
                    ->icon('heroicon-s-cube'),

                TextColumn::make('transmittalKirim.tanggal_kirim')
                    ->label('Tanggal Kirim')
                    ->dateTime('l, d F Y')
                    ->sortable()
                    ->color('gray'),

                TextColumn::make('transmittalKembali.tanggal_kembali')
                    ->label('Tanggal Kembali')
                    ->dateTime('l, d F Y')
                    ->sortable()
                    ->color('gray'),

                TextColumn::make('lead_time')
                    ->label('Lead Time (hari)')
                    ->icon('heroicon-s-clock')
                    ->color('warning')
                    ->sortable()
                    ->state(function ($record) {
                        $start = optional($record->transmittalKirim)->tanggal_kirim;
                        $end = optional($record->transmittalKembali)->tanggal_kembali;
                        if (!$start || !$end)
                            return '-';

                        $days = static::hitungHariKerja($start, $end); // ⬅️ pakai helper cached
                        return "{$days} hari";
                    }),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->icon('heroicon-m-calendar-days')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->icon('heroicon-o-arrow-path')
                    ->toggleable(isToggledHiddenByDefault: true),

                // SEARCH-ONLY (disembunyikan)
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('code_103')
                    ->label('Code 103')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('transmittalKirim.deliveryOrderReceipts.nomor_do')
                    ->label('No. DO')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                // (opsional) tambahkan filter kalau perlu, biar query makin ringan saat difilter
            ])

            ->actions([
                // …
            ])

            ->bulkActions([
                // …
            ]);
    }

    /**
     * Cache hari libur nasional per proses (dan per tahun via Cache) supaya tidak HTTP call per-row.
     */
    protected static ?array $cachedHolidays = null;

    protected static function getHolidays(): array
    {
        if (static::$cachedHolidays !== null) {
            return static::$cachedHolidays;
        }

        try {
            // cache sampai akhir tahun (gunakan Cache facade kalau mau persist ke storage)
            $res = Http::withOptions(['verify' => false])->timeout(4)->get('https://api-harilibur.vercel.app/api');
            static::$cachedHolidays = collect($res->json())
                ->pluck('holiday_date')
                ->toArray();
        } catch (\Throwable $e) {
            static::$cachedHolidays = [];
        }

        return static::$cachedHolidays;
    }

    protected static function hitungHariKerja($start, $end): int
    {
        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->startOfDay();

        if ($end->lt($start)) {
            return 0;
        }

        $holidays = static::getHolidays();

        $days = 0;
        $cur = $start->copy();
        while ($cur->lte($end)) {
            if (!$cur->isWeekend() && !in_array($cur->format('Y-m-d'), $holidays, true)) {
                $days++;
            }
            $cur->addDay();
        }

        return $days;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransmittalKembaliDetails::route('/'),
        ];
    }
}
