<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\GrsRdtv;
use App\Filament\Resources\GoodsReceiptSlipDetailResource\Pages;
use App\Filament\Resources\GoodsReceiptSlipDetailResource\RelationManagers;
use App\Models\GoodsReceiptSlipDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GoodsReceiptSlipDetailResource extends Resource
{
    protected static ?string $model = GoodsReceiptSlipDetail::class;
    protected static ?string $cluster = GrsRdtv::class;
    protected static ?string $label = 'Detail Dokumen GRS';
    protected static ?string $navigationGroup = 'Goods Receipt Slip (GRS)';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $activeNavigationIcon = 'heroicon-s-clipboard-document-list';
    protected static ?int $navigationSort = 2;
    protected static ?string $slug = 'detail-dokumen-grs';
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::count();
        return $count < 1 ? 'danger' : 'info';
    }
    protected static ?string $navigationBadgeTooltip = 'Total Detail GRS';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('goods_receipt_slip_id')
                    ->relationship('goodsReceiptSlip', 'id')
                    ->required(),
                Forms\Components\Select::make('delivery_order_receipt_id')
                    ->relationship('deliveryOrderReceipt', 'id')
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
                Group::make('goodsReceiptSlip.deliveryOrderReceipts.purchaseOrderTerbits.purchase_order_no')
                    ->label('Nomor PO')
                    ->titlePrefixedWithLabel()
                    ->collapsible(),
            ])
            ->defaultGroup(
                Group::make('goodsReceiptSlip.deliveryOrderReceipts.purchaseOrderTerbits.purchase_order_no')
            )
            ->columns([
                Tables\Columns\TextColumn::make('goodsReceiptSlip.deliveryOrderReceipts.purchaseOrderTerbits.purchase_order_no')
                    ->label('Nomor PO & Kode 105')
                    ->description(fn($record) => 'Kode 105: ' . ($record->goodsReceiptSlip->code_105 ?? '-'))
                    ->icon('heroicon-s-document-text')
                    ->color('primary')
                    ->searchable(),

                Tables\Columns\TextColumn::make('item_no')
                    ->label('Item No.')
                    ->prefix('Item ')
                    ->sortable()
                    ->color('info'),

                Tables\Columns\TextColumn::make('material_code')
                    ->label('Kode Material')
                    ->searchable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity')
                    ->formatStateUsing(function ($state, $record) {
                        return "{$state} {$record->uoi}";
                    })
                    ->icon('heroicon-s-cube')
                    ->color('warning')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->icon('heroicon-s-calendar')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // (opsional: filter by PO, tanggal, dsb)
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
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
            'index' => Pages\ListGoodsReceiptSlipDetails::route('/'),
        ];
    }
}
