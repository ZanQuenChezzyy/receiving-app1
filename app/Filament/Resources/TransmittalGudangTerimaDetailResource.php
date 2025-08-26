<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\MIR;
use App\Filament\Resources\TransmittalGudangTerimaDetailResource\Pages;
use App\Filament\Resources\TransmittalGudangTerimaDetailResource\RelationManagers;
use App\Models\TransmittalGudangTerimaDetail;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Number;

class TransmittalGudangTerimaDetailResource extends Resource
{
    protected static ?string $model = TransmittalGudangTerimaDetail::class;
    protected static ?string $cluster = MIR::class;
    protected static ?string $label = 'Detail Terima';
    protected static ?string $navigationGroup = 'Transmittal Gudang Terima';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $activeNavigationIcon = 'heroicon-s-document-text';
    protected static ?int $navigationSort = 6;
    protected static ?string $slug = 'transmittal-gudang-terima-detail';

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
                Forms\Components\Select::make('transmittal_gudang_terima_id')
                    ->relationship('transmittalGudangTerima', 'id')
                    ->required(),
                Forms\Components\Select::make('transmittal_gudang_kirim_detail_id')
                    ->relationship('transmittalGudangKirimDetail', 'id')
                    ->required(),
                Forms\Components\TextInput::make('qty_diterima')
                    ->required()
                    ->numeric(),
                Forms\Components\Textarea::make('catatan')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Cari transmittal / material / deskripsi...')
            ->columns([
                // Transmittal (kode) + deskripsi nomor GRS 105
                TextColumn::make('transmittalGudangTerima.transmittalGudangKirim.code')
                    ->label('Transmittal')
                    ->icon('heroicon-o-qr-code')
                    ->color('primary')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('transmittalGudangKirimDetail.item_no')
                    ->label('Item')
                    ->color('primary')
                    ->prefix('Item ')
                    ->sortable()
                    ->toggleable(),

                // Material code (dari relasi sumber)
                TextColumn::make('transmittalGudangKirimDetail.material_code')
                    ->label('Kode Material')
                    ->icon('heroicon-o-qr-code')
                    ->badge()
                    ->color('info')
                    ->copyable()
                    ->copyMessage('Kode material disalin')
                    ->copyMessageDuration(1200)
                    ->tooltip(fn($state) => $state)
                    ->searchable()
                    ->toggleable(),

                // Deskripsi material
                TextColumn::make('transmittalGudangKirimDetail.description')
                    ->label('Deskripsi')
                    ->icon('heroicon-o-document-text')
                    ->limit(80)
                    ->searchable()
                    ->toggleable(),

                // UoI
                TextColumn::make('transmittalGudangKirimDetail.uoi')
                    ->label('UoI')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                // Qty kirim (sumber) — tampilan
                TextColumn::make('transmittalGudangKirimDetail.quantity')
                    ->label('Quantity Kirim')
                    ->icon('heroicon-o-truck')
                    ->badge()
                    ->formatStateUsing(fn($state) => Number::format((float) $state))
                    ->suffix(fn($record) => $record->transmittalGudangKirimDetail?->uoi) // 🔑 prefix/suffix UoI
                    ->alignRight()
                    ->toggleable(isToggledHiddenByDefault: false),

                // Qty diterima (yang disimpan di detail terima)
                TextColumn::make('qty_diterima')
                    ->label('Quantity Terima')
                    ->icon('heroicon-o-cube')
                    ->badge()
                    ->formatStateUsing(fn($state) => Number::format((float) $state))
                    ->color(fn($state) => ((float) $state) > 0 ? 'success' : 'danger')
                    ->suffix(fn($record) => $record->transmittalGudangKirimDetail?->uoi) // 🔑 prefix/suffix UoI
                    ->alignRight()
                    ->sortable()
                    ->toggleable(),

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

            // === GROUPING ===
            ->groups([
                Group::make('transmittalGudangTerima.transmittalGudangKirim.code')
                    ->label('Code 105')
                    ->collapsible(),
            ])
            ->defaultGroup('transmittalGudangTerima.transmittalGudangKirim.code')

            // === FILTERS ===
            ->filters([
                //
            ], layout: Tables\Enums\FiltersLayout::AboveContent)

            // === ACTIONS ===
            ->actions([
                //
            ])

            // === BULK ACTIONS ===
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])

            // === EMPTY STATE ===
            ->emptyStateHeading('Belum ada detail penerimaan')
            ->emptyStateDescription('Detail akan muncul setelah proses penerimaan dilakukan.')
            ->emptyStateIcon('heroicon-o-inbox');
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
            'index' => Pages\ListTransmittalGudangTerimaDetails::route('/'),
            'create' => Pages\CreateTransmittalGudangTerimaDetail::route('/create'),
            'view' => Pages\ViewTransmittalGudangTerimaDetail::route('/{record}'),
            'edit' => Pages\EditTransmittalGudangTerimaDetail::route('/{record}/edit'),
        ];
    }
}
