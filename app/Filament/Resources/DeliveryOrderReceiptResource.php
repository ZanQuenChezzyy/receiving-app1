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
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

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
                            Forms\Components\Section::make('Informasi Penerimaan Delivery Order')
                                ->description('Masukkan informasi utama dari Delivery Order')
                                ->schema([
                                    Select::make('purchase_order_terbit_id')
                                        ->label('Nomor Purchase Order')
                                        ->placeholder('Pilih Nomor PO')
                                        ->searchable()
                                        ->live()
                                        ->required()
                                        ->columnSpanFull()
                                        ->options(
                                            fn(string $search = '') =>
                                            \App\Models\PurchaseOrderTerbit::query()
                                                ->selectRaw('MIN(id) as id, purchase_order_no')
                                                ->groupBy('purchase_order_no')
                                                ->when(
                                                    $search,
                                                    fn($query) =>
                                                    $query->where('purchase_order_no', 'like', "%{$search}%")
                                                )
                                                ->orderBy('purchase_order_no')
                                                ->limit(50)
                                                ->pluck('purchase_order_no', 'id')
                                        ),

                                    Forms\Components\DatePicker::make('received_date')
                                        ->label('Tanggal Terima DO')
                                        ->placeholder('Pilih Tanggal Terima')
                                        ->displayFormat('l, d F Y')
                                        ->native(false)
                                        ->required(),

                                    Forms\Components\Select::make('location_id')
                                        ->label('Lokasi Item')
                                        ->placeholder('Pilih Lokasi')
                                        ->relationship('locations', 'name')
                                        ->preload()
                                        ->searchable()
                                        ->required(),

                                    Forms\Components\Select::make('received_by')
                                        ->label('Diterima Oleh')
                                        ->placeholder('Pilih Penerima')
                                        ->relationship('receivedBy', 'name')
                                        ->default(Auth::user()->id)
                                        ->preload()
                                        ->searchable()
                                        ->required(),

                                    Forms\Components\hidden::make('created_by')
                                        ->default(Auth::user()->id),

                                    Forms\Components\Select::make('stage_id')
                                        ->label('Tahapan')
                                        ->relationship('stages', 'name')
                                        ->preload()
                                        ->searchable()
                                        ->nullable(),
                                ])
                                ->columnSpan(1)
                                ->columns(2),

                            Forms\Components\Section::make('Informasi Item pada PO')
                                ->description('Berikut adalah informasi item yang terkait dengan penerimaan ini.')
                                ->schema([
                                    Forms\Components\Placeholder::make('daftar_item_po')
                                        ->label('Item pada PO Terpilih')
                                        ->reactive()
                                        ->content(function (callable $get) {
                                            $poId = $get('purchase_order_terbit_id');
                                            if (!$poId) {
                                                return new HtmlString('<em>Silakan pilih nomor PO terlebih dahulu.</em>');
                                            }

                                            $po = \App\Models\PurchaseOrderTerbit::find($poId, ['purchase_order_no']);
                                            if (!$po) {
                                                return new HtmlString('<em>Data PO tidak ditemukan.</em>');
                                            }

                                            $items = \App\Models\PurchaseOrderTerbit::query()
                                                ->where('purchase_order_no', $po->purchase_order_no)
                                                ->get(['item_no', 'material_code', 'description', 'qty_po', 'uoi']);

                                            if ($items->isEmpty()) {
                                                return new HtmlString('<em>Tidak ada item untuk PO ini.</em>');
                                            }

                                            $html = '';
                                            foreach ($items as $item) {
                                                $shortDesc = Str::limit($item->description, 20, '...');
                                                $html .= "• Item {$item->item_no} - {$item->material_code} - {$item->qty_po} {$item->uoi} : {$shortDesc}<br>";
                                            }

                                            return new HtmlString($html);
                                        }),
                                ]),
                        ]),
                        Forms\Components\Section::make('Item Diterima')
                            ->description('Masukkan detail item yang diterima dalam pengiriman ini.')
                            ->schema([
                                Repeater::make('deliveryOrderReceiptDetails')
                                    ->label('Detail Penerimaan')
                                    ->relationship()
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('item_no')
                                                    ->label('No Item')
                                                    ->placeholder('Pilih No Item')
                                                    ->options([
                                                        '10' => '10',
                                                        '20' => '20',
                                                        '30' => '30',
                                                        '40' => '40',
                                                        '50' => '50',
                                                        '60' => '60',
                                                        '70' => '70',
                                                        '80' => '80',
                                                        '90' => '90',
                                                        '100' => '100',
                                                        '110' => '110',
                                                        '120' => '120',
                                                        '130' => '130',
                                                        '140' => '140',
                                                        '150' => '150',
                                                        '160' => '160',
                                                        '170' => '170',
                                                        '180' => '180',
                                                        '190' => '190',
                                                        '200' => '200',
                                                        '210' => '210',
                                                        '220' => '220',
                                                        '230' => '230',
                                                        '240' => '240',
                                                        '250' => '250',
                                                    ])
                                                    ->native(false)
                                                    ->searchable()
                                                    ->required()
                                                    ->columnSpan(1)
                                                    ->reactive()
                                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                        $poId = $get('../../purchase_order_terbit_id'); // naik 2 level ke parent form
                                                        if (!$poId || !$state)
                                                            return;

                                                        $item = \App\Models\PurchaseOrderTerbit::where('purchase_order_no', function ($query) use ($poId) {
                                                            $query->select('purchase_order_no')
                                                                ->from('purchase_order_terbits')
                                                                ->where('id', $poId)
                                                                ->limit(1);
                                                        })
                                                            ->where('item_no', $state)
                                                            ->first(['material_code', 'description', 'uoi']);

                                                        if ($item) {
                                                            $set('material_code', $item->material_code);
                                                            $set('description', $item->description);
                                                            $set('uoi', $item->uoi);
                                                        }
                                                    }),
                                                Forms\Components\Toggle::make('is_different_location')
                                                    ->label('Beda Lokasi?')
                                                    ->helperText('Aktifkan jika lokasi item berbeda dari lokasi utama penerimaan.')
                                                    ->onColor('primary')
                                                    ->reactive()
                                                    ->default(false),
                                                Forms\Components\TextInput::make('quantity')
                                                    ->label('Jumlah Diterima')
                                                    ->placeholder('Jumlah Diterima')
                                                    ->numeric()
                                                    ->suffix(fn(callable $get) => $get('uoi') ?? '')
                                                    ->required(),
                                                Forms\Components\Select::make('location_id')
                                                    ->label('Lokasi Barang')
                                                    ->relationship('locations', 'name')
                                                    ->searchable()
                                                    ->nullable()
                                                    ->visible(fn($get) => $get('is_different_location') ?? true),
                                                Forms\Components\hidden::make('material_code'),
                                                Forms\Components\hidden::make('description'),
                                                Forms\Components\hidden::make('uoi'),
                                            ]),
                                    ])
                                    ->defaultItems(1)
                                    ->label('')
                                    ->addActionLabel('Tambah Item')
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
                Tables\Columns\TextColumn::make('purchaseOrderTerbits.purchase_order_no')
                    ->label('No PO')
                    ->sortable()
                    ->searchable()
                    ->color('primary')
                    ->icon('heroicon-o-document-text'),

                Tables\Columns\TextColumn::make('deliveryOrderReceiptDetails.item_no')
                    ->label('Item Diterima')
                    ->bulleted()
                    ->limitList(1)
                    ->disabledClick()
                    ->getStateUsing(function ($record) {
                        if (!$record->deliveryOrderReceiptDetails?->count()) {
                            return ['-'];
                        }
                        return $record->deliveryOrderReceiptDetails
                            ->map(function ($detail) {
                                return "Item {$detail->item_no} - {$detail->material_code} - {$detail->quantity} {$detail->uoi} - " .
                                    Str::limit($detail->description, 5);
                            })
                            ->toArray();
                    })
                    ->expandableLimitedList(),

                Tables\Columns\TextColumn::make('locations.name')
                    ->label('Lokasi')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('received_date')
                    ->label('Tanggal Terima')
                    ->date('l, d F Y')
                    ->sortable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('receivedBy.name')
                    ->label('Diterima Oleh')
                    ->color('warning')
                    ->icon('heroicon-o-user')
                    ->searchable(),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Dibuat Oleh')
                    ->color('primary')
                    ->icon('heroicon-o-user')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('stages.name')
                    ->label('Tahapan')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
