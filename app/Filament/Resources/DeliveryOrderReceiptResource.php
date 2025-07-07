<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryOrderReceiptResource\Pages;
use App\Filament\Resources\DeliveryOrderReceiptResource\RelationManagers;
use App\Models\DeliveryOrderReceipt;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class DeliveryOrderReceiptResource extends Resource
{
    protected static ?string $model = DeliveryOrderReceipt::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        Group::make([
                            Forms\Components\Section::make('Informasi Penerimaan DO')
                                ->description('Masukkan informasi utama dari Delivery Order')
                                ->schema([
                                    Select::make('purchase_order_terbit_id')
                                        ->label('Nomor Purchase Order')
                                        ->placeholder('Pilih Nomor PO')
                                        ->options(function () {
                                            return \App\Models\PurchaseOrderTerbit::query()
                                                ->selectRaw('MIN(id) as id, purchase_order_no')
                                                ->groupBy('purchase_order_no')
                                                ->orderBy('purchase_order_no')
                                                ->get()
                                                ->pluck('purchase_order_no', 'id');
                                        })
                                        ->columnSpanFull()
                                        ->searchable()
                                        ->live()
                                        ->preload()
                                        ->required(),

                                    Forms\Components\Select::make('location_id')
                                        ->label('Lokasi Barang')
                                        ->placeholder('Pilih Lokasi')
                                        ->relationship('locations', 'name')
                                        ->preload()
                                        ->searchable()
                                        ->required(),

                                    Forms\Components\DatePicker::make('received_date')
                                        ->label('Tanggal Diterima')
                                        ->native(false)
                                        ->required(),

                                    Forms\Components\Select::make('received_by')
                                        ->label('Diterima Oleh')
                                        ->placeholder('Pilih Penerima')
                                        ->relationship('users', 'name')
                                        ->default(Auth::user()->id)
                                        ->preload()
                                        ->searchable()
                                        ->required(),

                                    Forms\Components\Hidden::make('created_by')
                                        ->default(Auth::user()->id),

                                    Forms\Components\Select::make('stage_id')
                                        ->label('Tahapan')
                                        ->relationship('stages', 'name')
                                        ->searchable()
                                        ->nullable(),
                                ])
                                ->columnSpan(1)
                                ->columns(2),

                            Forms\Components\Section::make('informasi Item')
                                ->description('Berikut adalah informasi item yang terkait dengan penerimaan ini.')
                                ->schema([
                                    Forms\Components\Placeholder::make('daftar_item_po')
                                        ->label('Item pada PO Terpilih')
                                        ->reactive() // agar update saat PO berubah
                                        ->content(function (callable $get) {
                                            $poId = $get('purchase_order_terbit_id');
                                            if (!$poId) {
                                                return 'Silakan pilih nomor PO terlebih dahulu.';
                                            }

                                            $po = \App\Models\PurchaseOrderTerbit::find($poId);
                                            if (!$po) {
                                                return 'Data PO tidak ditemukan.';
                                            }

                                            $items = \App\Models\PurchaseOrderTerbit::query()
                                                ->where('purchase_order_no', $po->purchase_order_no)
                                                ->limit(15)
                                                ->get(['item_no', 'description']);

                                            if ($items->isEmpty()) {
                                                return 'Tidak ada item untuk PO ini.';
                                            }

                                            // Format sebagai teks biasa (tanpa HTML)
                                            return $items->map(function ($item) {
                                                return "• Item {$item->item_no} • {$item->description}  ";
                                            })->implode("<br>");
                                        }),
                                ]),
                        ]),


                        Forms\Components\Section::make('Detail Barang Diterima')
                            ->description('Masukkan detail item yang diterima dalam pengiriman ini.')
                            ->schema([
                                Repeater::make('deliveryOrderReceiptDetails')
                                    ->label('Detail Penerimaan')
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\TextInput::make('item_no')
                                            ->numeric()
                                            ->required()
                                            ->label('No Item'),

                                        Forms\Components\TextInput::make('quantity')
                                            ->numeric()
                                            ->required()
                                            ->label('Jumlah'),

                                        Forms\Components\Toggle::make('is_different_location')
                                            ->label('Lokasi Berbeda?')
                                            ->default(false),

                                        Forms\Components\Select::make('location_id')
                                            ->label('Lokasi Barang')
                                            ->relationship('locations', 'name')
                                            ->searchable()
                                            ->nullable(),
                                    ])
                                    ->defaultItems(1)
                                    ->columns(2),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('purchase_order_terbit_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('location_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('received_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('received_by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stage_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListDeliveryOrderReceipts::route('/'),
            'create' => Pages\CreateDeliveryOrderReceipt::route('/create'),
            'view' => Pages\ViewDeliveryOrderReceipt::route('/{record}'),
            'edit' => Pages\EditDeliveryOrderReceipt::route('/{record}/edit'),
        ];
    }
}
