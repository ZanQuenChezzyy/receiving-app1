<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryOrderReceiptResource\Pages;
use App\Filament\Resources\DeliveryOrderReceiptResource\RelationManagers;
use App\Models\DeliveryOrderReceipt;
use App\Models\DeliveryOrderReceiptDetail;
use App\Models\PurchaseOrderTerbit;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Grouping\Group as GroupingGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class DeliveryOrderReceiptResource extends Resource
{
    protected static ?string $model = DeliveryOrderReceipt::class;
    protected static ?string $label = 'Penerimaan DO';
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $activeNavigationIcon = 'heroicon-s-truck';
    protected static ?string $navigationGroup = 'Receiving';
    protected static ?string $slug = 'penerimaan-do';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::count();
        return $count < 1 ? 'danger' : 'success';
    }

    protected static ?string $navigationBadgeTooltip = 'Total DO yang telah diterima';

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
                                        ->relationship(
                                            'purchaseOrderTerbits',
                                            'purchase_order_no',
                                            fn($query) => $query
                                                ->selectRaw('MIN(id) as id, purchase_order_no')
                                                ->groupBy('purchase_order_no')
                                                ->orderBy('purchase_order_no')
                                        )
                                        ->searchable()
                                        ->live()
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            $set('deliveryOrderReceiptDetails', [
                                                ['item_no' => null, 'is_different_location' => false, 'is_qty_tolerance' => false]
                                            ]);
                                        })
                                        ->required(),

                                    Forms\Components\TextInput::make('nomor_do')
                                        ->label('Nomor Delivery Order (DO)')
                                        ->placeholder('Masukkan Nomor DO')
                                        ->minLength(2)
                                        ->maxLength(15)
                                        ->required(),

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

                                    Forms\Components\TextInput::make('tahapan')
                                        ->label('Tahapan')
                                        ->placeholder('Masukkan Tahapan')
                                        ->maxLength(100)
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

                                            $po = PurchaseOrderTerbit::find($poId, ['purchase_order_no']);
                                            if (!$po) {
                                                return new HtmlString('<em>Data PO tidak ditemukan.</em>');
                                            }

                                            $items = PurchaseOrderTerbit::query()
                                                ->where('purchase_order_no', $po->purchase_order_no)
                                                ->get(['item_no', 'material_code', 'description', 'qty_po', 'uoi']);

                                            if ($items->isEmpty()) {
                                                return new HtmlString('<em>Tidak ada item untuk PO ini.</em>');
                                            }

                                            $html = '';
                                            foreach ($items as $item) {
                                                $materialCode = $item->material_code ?: '<em>None</em>';
                                                $shortDesc = Str::limit($item->description, 35, '...');
                                                $html .= "• Item {$item->item_no} - {$materialCode} - {$item->qty_po} {$item->uoi} : {$shortDesc}<br>";
                                            }

                                            return new HtmlString($html);
                                        })
                                ]),
                        ]),
                        Forms\Components\Section::make('Item Diterima')
                            ->description('Masukkan detail item yang diterima dalam pengiriman ini.')
                            ->schema([
                                Repeater::make('deliveryOrderReceiptDetails')
                                    ->label('Detail Penerimaan')
                                    ->relationship()
                                    ->schema([
                                        Grid::make(Auth::user()->hasRole('Administrator') ? 3 : 2)
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

                                                        $item = PurchaseOrderTerbit::where('purchase_order_no', function ($query) use ($poId) {
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
                                                Group::make()
                                                    ->schema([
                                                        Forms\Components\Toggle::make('is_different_location')
                                                            ->label('Beda Lokasi?')
                                                            ->helperText('Jika lokasi berbeda dari lokasi utama.')
                                                            ->onColor('primary')
                                                            ->reactive()
                                                            ->default(false),

                                                        Forms\Components\Toggle::make('is_qty_tolerance')
                                                            ->label('Toleransi Qty?')
                                                            ->helperText('Jika Qty diterima lebih dari Qty PO.')
                                                            ->onColor('primary')
                                                            ->reactive()
                                                            ->hidden(!Auth::user()->hasRole('Administrator'))
                                                            ->default(false),
                                                    ])->columns(Auth::user()->hasRole('Administrator') ? 2 : 1)
                                                    ->columnSpan(Auth::user()->hasRole('Administrator') ? 2 : 1),

                                                Grid::make(5)
                                                    ->schema([
                                                        TextInput::make('quantity')
                                                            ->label('Jumlah Diterima')
                                                            ->placeholder('Qty Diterima')
                                                            ->numeric()
                                                            ->suffix(fn(callable $get) => $get('uoi') ?? '')
                                                            ->columnSpan(fn(callable $get) => $get('is_different_location') ? 2 : 5)
                                                            ->helperText(function (callable $get, $record) {
                                                                $itemNo = $get('item_no');
                                                                $poId = $get('../../purchase_order_terbit_id');
                                                                $uoi = $get('uoi') ?? '';
                                                                $excludeId = $record?->id;

                                                                [$qtyPo, $qtyReceived] = DeliveryOrderReceiptDetail::getQtyPoAndReceived($poId, $itemNo, $excludeId);
                                                                $sisa = max(0, $qtyPo - $qtyReceived);

                                                                $colorDiterima = $qtyReceived == 0 ? '#6b7280' : '#16a34a'; // Tailwind 'text-green-600'
                                                                $colorSisa = $sisa == 0 ? '#6b7280' : '#dc2626'; // gray-500 jika sisa 0, red-600 jika masih ada sisa
                                                    
                                                                return new HtmlString("
                                                                    <span style='color: {$colorDiterima}; font-weight: 500;'>Diterima: {$qtyReceived} {$uoi}</span><br>
                                                                    <span style='color: {$colorSisa}; font-weight: 500;'>Sisa: {$sisa} {$uoi}</span>
                                                                ");
                                                            })
                                                            ->rules([
                                                                fn(Get $get, $record): \Closure =>
                                                                function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                                                    $itemNo = $get('item_no');
                                                                    $poId = $get('../../purchase_order_terbit_id');
                                                                    $excludeId = $record?->id;
                                                                    $isTolerance = $get('is_qty_tolerance') ?? false;

                                                                    // Skip validation if tolerance is allowed
                                                                    if ($isTolerance) {
                                                                        return;
                                                                    }

                                                                    [$qtyPo, $qtyReceived] = DeliveryOrderReceiptDetail::getQtyPoAndReceived($poId, $itemNo, $excludeId);
                                                                    $sisa = max(0, $qtyPo - $qtyReceived);

                                                                    if ($value > $sisa) {
                                                                        $fail("Jumlah melebihi sisa PO. Maksimum sisa: {$sisa}");
                                                                    }
                                                                }
                                                            ])
                                                            ->required(),
                                                        Forms\Components\Select::make('location_id')
                                                            ->label('Lokasi Item')
                                                            ->relationship('locations', 'name')
                                                            ->preload()
                                                            ->searchable()
                                                            ->nullable()
                                                            ->columnSpan(3)
                                                            ->visible(fn($get) => $get('is_different_location') ?? true),
                                                    ]),
                                                Forms\Components\Hidden::make('material_code'),
                                                Forms\Components\Hidden::make('description'),
                                                Forms\Components\Hidden::make('uoi'),
                                            ]),
                                    ])
                                    ->defaultItems(1)
                                    ->label('')
                                    ->disabled(fn(callable $get) => !$get('purchase_order_terbit_id'))
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
            ->modifyQueryUsing(function (Builder $query) {
                return $query->latest(); // urutkan berdasarkan created_at DESC
            })
            ->groups([
                GroupingGroup::make('purchaseOrderTerbits.purchase_order_no')
                    ->label('Nomor PO'),
                GroupingGroup::make('received_date')
                    ->label('Tanggal Terima')
                    ->date(),
            ])
            ->defaultGroup(
                GroupingGroup::make('received_date')
                    ->label('Tanggal Terima')
                    ->date(),
            )
            ->columns([
                Tables\Columns\TextColumn::make('purchaseOrderTerbits.purchase_order_no')
                    ->label('Nomor PO & DO')
                    ->sortable()
                    ->searchable()
                    ->color('primary')
                    ->icon('heroicon-s-document-text')
                    ->description(fn($record) => 'No. DO: ' . ($record->nomor_do ?? '-')),

                Tables\Columns\TextColumn::make('received_date')
                    ->label('Tanggal Terima')
                    ->date('l, d F Y')
                    ->sortable()
                    ->color('gray'),

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
                                $materialCode = $detail->material_code ?: 'None';
                                $shortDesc = Str::limit($detail->description, 5); // untuk tampilan list
                                return "Item {$detail->item_no} - {$materialCode} - {$detail->quantity} {$detail->uoi} - {$shortDesc}";
                            })
                            ->toArray();
                    })
                    ->tooltip(function ($record) {
                        if (!$record->deliveryOrderReceiptDetails?->count()) {
                            return null;
                        }

                        return $record->deliveryOrderReceiptDetails
                            ->map(function ($detail) {
                                $materialCode = $detail->material_code ?: 'None';
                                return "{$detail->description}";
                            })
                            ->implode("\n");
                    })
                    ->expandableLimitedList(),

                Tables\Columns\TextColumn::make('locations.name')
                    ->label('Lokasi')
                    ->badge()
                    ->color('info')
                    ->limit(15)
                    ->tooltip(fn($record) => $record->locations->name)
                    ->searchable(),

                Tables\Columns\TextColumn::make('receivedBy.name')
                    ->label('Diterima Oleh')
                    ->color('warning')
                    ->icon('heroicon-s-user')
                    ->limit(15)
                    ->searchable(),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Dibuat Oleh')
                    ->color('primary')
                    ->icon('heroicon-s-user')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('tahapan')
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
                Action::make('Cetak')
                    ->label('Cetak')
                    ->url(fn($record) => route('do-receipt.print-qr', $record->id))
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->button()
                    ->openUrlInNewTab(),
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
                BulkAction::make('cetak_qr')
                    ->label('Cetak Dipilih')
                    ->icon('heroicon-o-printer')
                    ->action(function (Collection $records) {
                        $ids = $records->pluck('id')->toArray();
                        return redirect()->route('qr.bulk.print', ['ids' => implode(',', $ids)]);
                    })
                    ->color('gray')
                    ->deselectRecordsAfterCompletion()
                    ->openUrlInNewTab(),
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
