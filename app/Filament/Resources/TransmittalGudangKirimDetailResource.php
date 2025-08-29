<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\MIR;
use App\Filament\Resources\TransmittalGudangKirimDetailResource\Pages;
use App\Filament\Resources\TransmittalGudangKirimDetailResource\RelationManagers;
use App\Models\TransmittalGudangKirimDetail;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class TransmittalGudangKirimDetailResource extends Resource
{
    protected static ?string $model = TransmittalGudangKirimDetail::class;
    protected static ?string $cluster = MIR::class;
    protected static ?string $label = 'Detail Kirim';
    protected static ?string $navigationGroup = 'Transmittal Gudang Kirim';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $activeNavigationIcon = 'heroicon-s-document-text';
    protected static ?int $navigationSort = 4;
    protected static ?string $slug = 'transmittal-gudang-kirim-detail';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::count();
        return $count < 1 ? 'danger' : 'success';
    }

    protected static ?string $navigationBadgeTooltip = 'Total Detail';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('transmittal_gudang_kirim_id')
                    ->relationship('transmittalGudangKirim', 'id')
                    ->required(),
                Forms\Components\Select::make('goods_receipt_slip_detail_id')
                    ->relationship('goodsReceiptSlipDetail', 'id')
                    ->required(),
                Forms\Components\TextInput::make('item_no')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('material_code')
                    ->maxLength(20),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('uoi')
                    ->required()
                    ->maxLength(5),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('3s')
            ->modifyQueryUsing(
                fn(Builder $query) => $query
                    ->withCount('transmittalGudangTerimaDetails as terima_count')
                    ->addSelect(DB::raw("
                        CASE
                            WHEN (select count(*) from transmittal_gudang_terima_details
                                where transmittal_gudang_kirim_details.id = transmittal_gudang_terima_details.transmittal_gudang_kirim_detail_id) = 0
                            THEN 'Outstanding'
                            ELSE 'Sudah Terima'
                        END as status_group
                    "))
                    ->orderBy('terima_count', 'asc')
                    ->orderBy('created_at', 'desc')
            )
            // === GROUPING ===
            ->groups([
                Group::make('transmittalGudangKirim.code')
                    ->label('Code 105')
                    ->collapsible(),

                Group::make('status_group')
                    ->label('Status')
                    ->collapsible()
                    ->orderQueryUsing(fn(Builder $query, string $direction) => $query),
            ])
            ->defaultGroup('status_group')
            ->searchPlaceholder('Cari Code 105 / material / UoI...')
            ->columns([
                TextColumn::make('status_group')
                    ->label('Status')
                    ->badge()
                    ->icon(fn(string $state) => $state === 'Outstanding'
                        ? 'heroicon-o-clock'
                        : 'heroicon-o-check-circle')
                    ->color(fn(string $state) => $state === 'Outstanding' ? 'warning' : 'success')
                    ->toggleable(),
                // Transmittal (badge + searchable)
                TextColumn::make('transmittalGudangKirim.code')
                    ->label('Code 105')
                    ->icon('heroicon-o-qr-code')
                    ->color('primary')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Item No
                TextColumn::make('item_no')
                    ->label('Item')
                    ->color('primary')
                    ->prefix('Item ')
                    ->sortable()
                    ->toggleable(),

                // Material Code
                TextColumn::make('material_code')
                    ->label('Kode Material')
                    ->icon('heroicon-o-qr-code')
                    ->badge()
                    ->color('info')
                    ->tooltip(fn($state) => $state)
                    ->searchable()
                    ->toggleable(),

                // Deskripsi (dari relasi, jika ada)
                TextColumn::make('goodsReceiptSlipDetail.description')
                    ->label('Deskripsi')
                    ->icon('heroicon-o-document-text')
                    ->wrap()
                    ->limit(60)
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('quantity')
                    ->label('Quantity')
                    ->icon('heroicon-o-cube')
                    ->badge()
                    ->color(fn($state) => ((float) $state) > 0 ? 'success' : 'danger')
                    ->alignRight()
                    ->suffix(fn($record) => $record->uoi) // 🔑 tampilkan UoI setelah qty
                    ->sortable()
                    ->toggleable(),

                // UoI (opsional, bisa disembunyikan karena sudah ditampilkan di Qty)
                TextColumn::make('uoi')
                    ->label('UoI')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diupdate')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            // === FILTERS ===
            ->filters([
                // Filter Transmittal (relasi)
                SelectFilter::make('transmittal_gudang_kirim_id')
                    ->label('Transmittal')
                    ->relationship('transmittalGudangKirim', 'code')
                    ->searchable()
                    ->preload(),

                // Filter UoI
                SelectFilter::make('uoi')
                    ->label('UoI')
                    ->options(fn() => TransmittalGudangKirimDetail::query()
                        ->whereNotNull('uoi')
                        ->distinct()
                        ->orderBy('uoi')
                        ->pluck('uoi', 'uoi')
                        ->all()),
            ], layout: Tables\Enums\FiltersLayout::Dropdown)

            // === ACTIONS ===
            ->actions([
                // Tables\Actions\ViewAction::make()
                //     ->label('Detail')
                //     ->icon('heroicon-o-eye')
                //     ->slideOver()
                //     ->modalWidth('3xl'),

                // Tables\Actions\EditAction::make()
                //     ->label('Ubah')
                //     ->icon('heroicon-o-pencil-square')
                //     ->slideOver(),
            ])

            // === BULK ACTIONS ===
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ])

            // === EMPTY STATE ===
            ->emptyStateHeading('Belum ada detail transmittal')
            ->emptyStateDescription('Detail pengiriman akan muncul setelah transmittal dibuat.')
            ->emptyStateIcon('heroicon-o-cube');
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
            'index' => Pages\ListTransmittalGudangKirimDetails::route('/'),
            // 'create' => Pages\CreateTransmittalGudangKirimDetail::route('/create'),
            // 'view' => Pages\ViewTransmittalGudangKirimDetail::route('/{record}'),
            // 'edit' => Pages\EditTransmittalGudangKirimDetail::route('/{record}/edit'),
        ];
    }
}
