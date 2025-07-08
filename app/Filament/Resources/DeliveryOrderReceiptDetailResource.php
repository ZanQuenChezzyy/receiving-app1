<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryOrderReceiptDetailResource\Pages;
use App\Filament\Resources\DeliveryOrderReceiptDetailResource\RelationManagers;
use App\Models\DeliveryOrderReceiptDetail;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Grouping\Group as GroupingGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class DeliveryOrderReceiptDetailResource extends Resource
{
    protected static ?string $model = DeliveryOrderReceiptDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('delivery_order_receipt_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('item_no')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->numeric(),
                Forms\Components\Toggle::make('is_different_location')
                    ->required(),
                Forms\Components\TextInput::make('location_id')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultGroup(
                GroupingGroup::make('deliveryOrderReceipts.purchaseOrderTerbits.purchase_order_no')
                    ->label('Nomor PO')
            )
            ->columns([
                TextColumn::make('deliveryOrderReceipts.purchaseOrderTerbits.purchase_order_no')
                    ->label('Nomor PO')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-s-document-text')
                    ->color('primary')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('item_no')
                    ->label('Item')
                    ->sortable()
                    ->placeholder('None')
                    ->prefix('Item ')
                    ->width('80px') // atur lebar biar kompak
                    ->color('primary'),

                TextColumn::make('material_code')
                    ->label('Kode Material')
                    ->sortable()
                    ->width('80px') // atur lebar biar kompak
                    ->placeholder('None')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->sortable()
                    ->limit(20)
                    ->width('80px') // atur lebar biar kompak
                    ->placeholder('None'),

                TextColumn::make('quantity')
                    ->label('Qty')
                    ->placeholder('None')
                    ->suffix(fn($record) => ' ' . ($record->uoi ?? ''))
                    ->alignLeft()
                    ->width('80px') // atur lebar biar kompak
                    ->color('primary'),

                TextColumn::make('qty_po')
                    ->label('Qty PO')
                    ->getStateUsing(function ($record) {
                        $purchaseOrderNo = $record->deliveryOrderReceipts?->purchaseOrderTerbits?->purchase_order_no;

                        if (!$purchaseOrderNo || !$record->item_no) {
                            return '-';
                        }

                        $poItem = \App\Models\PurchaseOrderTerbit::where('purchase_order_no', $purchaseOrderNo)
                            ->where('item_no', $record->item_no)
                            ->first();

                        return $poItem?->qty_po ?? '-';
                    })
                    ->suffix(function ($record) {
                        $purchaseOrderNo = $record->deliveryOrderReceipts?->purchaseOrderTerbits?->purchase_order_no;

                        if (!$purchaseOrderNo || !$record->item_no) {
                            return '';
                        }

                        $poItem = \App\Models\PurchaseOrderTerbit::where('purchase_order_no', $purchaseOrderNo)
                            ->where('item_no', $record->item_no)
                            ->first();

                        return $poItem?->uoi ? ' ' . $poItem->uoi : '';
                    })
                    ->alignLeft()
                    ->width('80px') // atur lebar biar kompak
                    ->color('warning'),

                TextColumn::make('vendor_name')
                    ->label('Nama Vendor')
                    ->getStateUsing(function ($record) {
                        $full = $record->deliveryOrderReceipts?->purchaseOrderTerbits?->vendor_id_name ?? '';
                        return $full ? Str::after($full, '-') : '-';
                    })
                    ->color('success')
                    ->searchable()
                    ->alignLeft()
                    ->wrap(),

                TextColumn::make('lokasi')
                    ->label('Lokasi Item')
                    ->getStateUsing(function ($record) {
                        // Jika beda lokasi, tampilkan lokasi dari kolom 'locations'
                        if ($record->is_different_location) {
                            return $record->locations?->name ?? 'Lokasi Beda (Tidak diketahui)';
                        }

                        // Jika tidak beda lokasi, tampilkan lokasi dari relasi 'deliveryOrderReceipt.locations'
                        return $record->deliveryOrderReceipts?->locations?->name ?? 'Lokasi Utama (Tidak diketahui)';
                    })
                    ->badge()
                    ->alignLeft()
                    ->width('80px') // atur lebar biar kompak
                    ->color('info'),

                Tables\Columns\TextColumn::make('deliveryOrderReceipts.receivedBy.name')
                    ->label('Diterima Oleh')
                    ->color('warning')
                    ->icon('heroicon-s-user')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y, H:i')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color('gray'),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color('secondary'),
            ])
            ->filters([
                // Tambahkan jika ingin menyaring berdasarkan PO, lokasi, dll
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->color('info')
                        ->slideOver(),
                    DeleteAction::make()
                        ->requiresConfirmation(),
                ])
                    ->icon('heroicon-o-ellipsis-horizontal-circle')
                    ->color('primary')
                    ->tooltip('Aksi'),
            ], position: ActionsPosition::AfterCells)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDeliveryOrderReceiptDetails::route('/'),
        ];
    }
}
