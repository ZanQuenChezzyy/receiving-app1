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
            ->modifyQueryUsing(function (Builder $query) {
                return $query->latest(); // urutkan berdasarkan created_at DESC
            })
            ->columns([
                TextColumn::make('transmittalKirim.deliveryOrderReceipts.purchaseOrderTerbits.purchase_order_no')
                    ->label('Nomor PO')
                    ->icon('heroicon-s-document-text')
                    ->color('primary')
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => 'Kode 103: ' . ($record->code_103 ?? '-')),

                Tables\Columns\TextColumn::make('total_item')
                    ->label('Total Item')
                    ->numeric()
                    ->sortable()
                    ->color('info')
                    ->suffix(' Item')
                    ->icon('heroicon-s-cube'),

                Tables\Columns\TextColumn::make('transmittalKirim.tanggal_kirim')
                    ->label('Tanggal Kirim')
                    ->dateTime('l, d F Y')
                    ->sortable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('transmittalKembali.tanggal_kembali')
                    ->label('Tanggal Kembali')
                    ->dateTime('l, d F Y')
                    ->sortable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('lead_time')
                    ->label('Lead Time (hari)')
                    ->icon('heroicon-s-clock')
                    ->color('warning')
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        $start = optional($record->transmittalKirim)->tanggal_kirim;
                        $end = optional($record->transmittalKembali)->tanggal_kembali;

                        if (!$start || !$end)
                            return '-';

                        $start = Carbon::parse($start);
                        $end = Carbon::parse($end);

                        // Ambil daftar hari libur dari API
                        $response = Http::withOptions([
                            'verify' => false, // ini menonaktifkan verifikasi SSL
                        ])->get('https://api-harilibur.vercel.app/api');
                        $holidays = collect($response->json())->pluck('holiday_date')->toArray();

                        $networkDays = 0;
                        $current = $start->copy();

                        while ($current->lte($end)) {
                            $isWeekend = $current->isWeekend(); // Sabtu/Minggu
                            $isHoliday = in_array($current->format('Y-m-d'), $holidays);

                            if (!$isWeekend && !$isHoliday) {
                                $networkDays++;
                            }

                            $current->addDay();
                        }

                        return "{$networkDays} hari";
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->icon('heroicon-o-calendar-days')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->icon('heroicon-o-arrow-path')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
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
