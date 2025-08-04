<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\GrsRdtv;
use App\Filament\Resources\ReturnDeliveryToVendorDetailResource\Pages;
use App\Filament\Resources\ReturnDeliveryToVendorDetailResource\RelationManagers;
use App\Models\ReturnDeliveryToVendorDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ReturnDeliveryToVendorDetailResource extends Resource
{
    protected static ?string $model = ReturnDeliveryToVendorDetail::class;
    protected static ?string $cluster = GrsRdtv::class;
    protected static ?string $label = 'Detail Dokumen RDTV';
    protected static ?string $navigationGroup = 'Return Delivery to Vendor (RDTV)';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document';
    protected static ?string $activeNavigationIcon = 'heroicon-s-clipboard-document';
    protected static ?int $navigationSort = 4;
    protected static ?string $slug = 'detail-dokumen-rdtv';
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::count();
        return $count < 1 ? 'danger' : 'info';
    }
    protected static ?string $navigationBadgeTooltip = 'Total Detail RDTV';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('return_delivery_to_vendor_id')
                    ->relationship('returnDeliveryToVendor', 'id')
                    ->required(),
                Forms\Components\TextInput::make('item_no')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->maxLength(10),
                Forms\Components\TextInput::make('material_code')
                    ->maxLength(20),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('uoi')
                    ->maxLength(5),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->groups([
                Group::make('returnDeliveryToVendor.deliveryOrderReceipts.purchaseOrderTerbits.purchase_order_no')
                    ->label('Nomor PO')
                    ->titlePrefixedWithLabel()
                    ->collapsible(),
            ])
            ->defaultGroup(
                Group::make('returnDeliveryToVendor.deliveryOrderReceipts.purchaseOrderTerbits.purchase_order_no')
            )
            ->columns([
                TextColumn::make('returnDeliveryToVendor.deliveryOrderReceipts.purchaseOrderTerbits.purchase_order_no')
                    ->label('Nomor PO')
                    ->description(fn($record) => 'Kode 124: ' . ($record->ReturnDeliveryToVendor->code_124 ?? '-'))
                    ->icon('heroicon-s-document-text')
                    ->color('primary')
                    ->searchable(),

                TextColumn::make('item_no')
                    ->label('Item No.')
                    ->prefix('Item ')
                    ->sortable()
                    ->color('info'),

                TextColumn::make('material_code')
                    ->label('Kode Material')
                    ->searchable()
                    ->color('gray'),

                TextColumn::make('quantity')
                    ->label('Quantity')
                    ->formatStateUsing(fn($state, $record) => "{$state} {$record->uoi}")
                    ->icon('heroicon-s-cube')
                    ->color('warning')
                    ->badge()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->icon('heroicon-s-calendar')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // tambahkan filter jika dibutuhkan
            ])
            ->actions([
                // tambahkan actions jika diperlukan
            ])
            ->bulkActions([
                // tambahkan bulk actions jika diperlukan
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
            'index' => Pages\ListReturnDeliveryToVendorDetails::route('/'),
        ];
    }
}
