<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransmittalResource\Pages;
use App\Filament\Resources\TransmittalResource\RelationManagers;
use App\Models\DeliveryOrderReceipt;
use App\Models\Transmittal;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class TransmittalResource extends Resource
{
    protected static ?string $model = Transmittal::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Scan & Informasi DO')
                    ->icon('heroicon-o-qr-code')
                    ->description('Scan kode QR dari Delivery Order untuk menarik data otomatis.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('code')
                                    ->label('Kode Dokumen (Scan QR)')
                                    ->placeholder('Contoh: 5000001269086PLJ072514072025')
                                    ->prefixIcon('heroicon-o-finger-print')
                                    ->autofocus()
                                    ->live()
                                    ->required()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $receipt = DeliveryOrderReceipt::with('deliveryOrderReceiptDetails.locations')
                                            ->where('do_code', $state)
                                            ->first();

                                        if ($receipt) {
                                            $set('delivery_order_receipt_id', $receipt->id);

                                            $items = $receipt->deliveryOrderReceiptDetails->map(fn($item) => [
                                                'item_no' => $item->item_no,
                                                'description' => $item->description,
                                                'quantity' => $item->quantity,
                                                'uoi' => $item->uoi,
                                                'location' => optional($item->locations)->name,
                                            ])->toArray();

                                            $set('items', $items);
                                        } else {
                                            $set('delivery_order_receipt_id', null);
                                            $set('items', []);
                                        }
                                    }),


                                DatePicker::make('tanggal_kirim')
                                    ->label('Tanggal Kirim')
                                    ->displayFormat('d M Y')
                                    ->native(false)
                                    ->live()
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->placeholder('Pilih Tanggal Kirim')
                                    ->required(),

                                DatePicker::make('tanggal_kembali')
                                    ->label('Tanggal Kembali')
                                    ->displayFormat('d M Y')
                                    ->native(false)
                                    ->live()
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->placeholder('Pilih Tanggal Kembali')
                                    ->nullable()
                                    ->disabled(fn(string $context) => $context === 'create'),

                                TextInput::make('code_istek')
                                    ->label('Kode Istek')
                                    ->placeholder('Diisi setelah Tanggal Kembali')
                                    ->prefixIcon('heroicon-o-document-text')
                                    ->columnSpanFull()
                                    ->nullable()
                                    ->visible(
                                        fn(Get $get) =>
                                        filled($get('code')) &&
                                        filled($get('tanggal_kirim')) &&
                                        filled($get('tanggal_kembali'))
                                    ),
                                Hidden::make('delivery_order_receipt_id')->required(),
                                Hidden::make('created_by')->default(Auth::user()->id),
                            ]),
                    ]),

                Section::make('Daftar Item dalam Delivery Order')
                    ->icon('heroicon-o-list-bullet')
                    ->description('Item ditarik otomatis setelah scan QR.')
                    ->schema([
                        Repeater::make('items')
                            ->label('')
                            ->schema([
                                TextInput::make('item_no')->label('Item No')->disabled(),
                                TextInput::make('description')->label('Deskripsi')->disabled(),
                                TextInput::make('quantity')->label('Qty')->disabled(),
                                TextInput::make('uoi')->label('Satuan')->disabled(),
                                TextInput::make('location')->label('Lokasi')->disabled(),
                            ])
                            ->columns(5)
                            ->default([])
                            ->columnSpanFull()
                            ->addable(false)
                            ->afterStateHydrated(function (callable $set, ?\App\Models\Transmittal $record) {
                                if (!$record || !$record->deliveryOrderReceipt) {
                                    $set([], 'items');
                                    return;
                                }

                                $items = $record->deliveryOrderReceipt->deliveryOrderReceiptDetails->map(fn($item) => [
                                    'item_no' => $item->item_no,
                                    'description' => $item->description,
                                    'quantity' => $item->quantity,
                                    'uoi' => $item->uoi,
                                    'location' => optional($item->locations)->name,
                                ])->toArray();

                                $set('items', $items);
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('deliveryOrderReceipt.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_kirim')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_kembali')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code_istek')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_by')
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
            'index' => Pages\ListTransmittals::route('/'),
            'create' => Pages\CreateTransmittal::route('/create'),
            'view' => Pages\ViewTransmittal::route('/{record}'),
            'edit' => Pages\EditTransmittal::route('/{record}/edit'),
        ];
    }
}
