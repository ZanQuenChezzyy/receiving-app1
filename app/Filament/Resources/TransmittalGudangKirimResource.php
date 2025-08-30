<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\MIR;
use App\Filament\Resources\TransmittalGudangKirimResource\Pages;
use App\Filament\Resources\TransmittalGudangKirimResource\RelationManagers;
use App\Models\TransmittalGudangKirim;
use App\Forms\Components\BarcodeInput;
use Filament\Actions\StaticAction;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
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
use Illuminate\Support\Facades\Auth;

class TransmittalGudangKirimResource extends Resource
{
    protected static ?string $model = TransmittalGudangKirim::class;
    protected static ?string $cluster = MIR::class;
    protected static ?string $label = 'Kirim';
    protected static ?string $navigationGroup = 'Transmittal Gudang Kirim';
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected static ?string $activeNavigationIcon = 'heroicon-s-arrow-up-tray';
    protected static ?int $navigationSort = 3;
    protected static ?string $slug = 'transmittal-gudang-kirim';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::count();
        return $count < 1 ? 'danger' : 'success';
    }

    protected static ?string $navigationBadgeTooltip = 'Total Kirim';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Dokumen')
                    ->description('Detail utama pengiriman gudang.')
                    ->schema([
                        Forms\Components\DatePicker::make('tanggal_kirim')
                            ->label('Tanggal Kirim')
                            ->placeholder('Pilih Tanggal')
                            ->displayFormat('l, d F Y')
                            ->default(now())
                            ->native(false)
                            ->required(),

                        BarcodeInput::make('code') // ← ganti TextInput jadi BarcodeInput
                            ->label('Nomor GRS (105)')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (!$state) {
                                    $set('goods_receipt_slip_id', null);
                                    $set('transmittalGudangKirimDetails', []);
                                    return;
                                }

                                $grs = \App\Models\GoodsReceiptSlip::with('goodsReceiptSlipDetails')
                                    ->where('code_105', $state)
                                    ->first();

                                if ($grs) {
                                    $set('goods_receipt_slip_id', $grs->id);

                                    $items = $grs->goodsReceiptSlipDetails->map(fn($detail) => [
                                        'goods_receipt_slip_detail_id' => $detail->id,
                                        'item_no' => $detail->item_no,
                                        'material_code' => $detail->material_code,
                                        'description' => $detail->description,
                                        'uoi' => $detail->uoi,
                                        'quantity' => $detail->quantity,
                                    ])->toArray();

                                    $set('transmittalGudangKirimDetails', $items);
                                } else {
                                    $set('goods_receipt_slip_id', null);
                                    $set('transmittalGudangKirimDetails', []);
                                    \Filament\Notifications\Notification::make()
                                        ->title("GRS dengan code_105 [$state] tidak ditemukan")
                                        ->danger()
                                        ->send();
                                }
                            }),

                        Forms\Components\Hidden::make('goods_receipt_slip_id'),

                        Forms\Components\Select::make('warehouse_location_id')
                            ->label('Lokasi Gudang')
                            ->relationship('warehouseLocation', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('dikirim_oleh')
                            ->label('Dikirim Oleh')
                            ->relationship('dikirimOleh', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(Auth::user()->id)
                            ->columnSpan(1),

                        Hidden::make('created_by')
                            ->default(Auth::user()->id)
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Detail Barang')
                    ->description('Daftar item yang dikirim ke gudang tujuan.')
                    ->schema([
                        Forms\Components\Repeater::make('transmittalGudangKirimDetails')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Forms\Components\Hidden::make('goods_receipt_slip_detail_id'),

                                Forms\Components\TextInput::make('item_no')
                                    ->label('No. Item')
                                    ->numeric()
                                    ->required()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('material_code')
                                    ->label('Kode Material')
                                    ->maxLength(20)
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('description')
                                    ->label('Deskripsi')
                                    ->required()
                                    ->columnSpan(4),

                                Forms\Components\TextInput::make('quantity')
                                    ->label('Qty Kirim')
                                    ->numeric()
                                    ->required()
                                    ->prefixIcon('heroicon-o-cube')
                                    ->suffix(fn($get) => $get('uoi'))
                                    ->columnSpan(2),

                                Forms\Components\Hidden::make('uoi'),
                            ])
                            ->columns(8)
                            ->minItems(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Cari kode / lokasi / pengirim...')
            ->columns([
                // Kode Transmittal
                Tables\Columns\TextColumn::make('code')
                    ->label('Code GRS (105)')
                    ->icon('heroicon-m-document-text')
                    ->iconPosition('before')
                    ->color('primary')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Kode disalin')
                    ->copyMessageDuration(1500)
                    ->tooltip(fn($state) => "Kode: {$state}")
                    ->description(fn($record) => 'No. PO: ' . optional(
                        $record->goodsReceiptSlip?->purchaseOrderTerbit
                    )?->purchase_order_no, position: 'below')
                    ->toggleable(),

                // Tanggal Kirim (dengan deskripsi umur/human diff)
                TextColumn::make('tanggal_kirim')
                    ->label('Tgl. Kirim')
                    ->date('d M Y')
                    ->sortable(),

                // Lokasi Gudang
                TextColumn::make('warehouseLocation.name')
                    ->label('Lokasi Gudang')
                    ->icon('heroicon-o-building-office-2')
                    ->wrap()
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                // Pengirim (user)
                TextColumn::make('dikirimOleh.name')
                    ->label('Dikirim Oleh')
                    ->icon('heroicon-o-user-circle')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                // Dibuat oleh + waktu
                TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->icon('heroicon-o-user')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diupdate')
                    ->since() // tampil relatif (x minutes ago)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            // === GROUPING ===
            ->groups([
                Group::make('warehouseLocation.name')
                    ->label('Lokasi Gudang')
                    ->collapsible(),
                Group::make('tanggal_kirim')
                    ->label('Bulan Kirim')
                    ->date()
                    ->collapsible(),
            ])
            ->defaultGroup('warehouseLocation.name')

            // === FILTERS ===
            ->filters([
                // Lokasi gudang (relasi)
                SelectFilter::make('warehouse_location_id')
                    ->label('Lokasi Gudang')
                    ->relationship('warehouseLocation', 'name')
                    ->searchable()
                    ->preload(),

                // Pengirim (relasi user)
                SelectFilter::make('dikirim_oleh')
                    ->label('Dikirim Oleh')
                    ->relationship('dikirimOleh', 'name')
                    ->searchable()
                    ->preload(),
            ], layout: Tables\Enums\FiltersLayout::Dropdown) // filter tampil di atas tabel

            // === ACTIONS ===
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-o-eye')
                    ->label('Detail')
                    ->modalHeading('Detail Transmittal')
                    ->slideOver(), // UX lebih enak

                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->label('Ubah')
                    ->slideOver(),

                // Aksi custom: lihat item (modal kecil)
                Tables\Actions\Action::make('lihat_items')
                    ->icon('heroicon-o-list-bullet')
                    ->label('Items')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(fn(StaticAction $action) => $action->label('Tutup'))
                    ->modalHeading('Item Pengiriman')
                    ->modalDescription()
                    ->modalWidth('5xl')
                    ->infolist([
                        // Ringkasan cepat (di header modal)
                        \Filament\Infolists\Components\Grid::make()
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('ringkasan.total_item')
                                    ->label('Total Item')
                                    ->inlineLabel()
                                    ->state(fn($record) => $record->transmittalGudangKirimDetails->count())
                                    ->suffix(' Item')
                                    ->badge()
                                    ->color('info')
                                    ->icon('heroicon-o-list-bullet'),

                                // Total Qty
                                \Filament\Infolists\Components\TextEntry::make('ringkasan.total_qty')
                                    ->label('Total Qty')
                                    ->inlineLabel()
                                    ->state(fn($record) => \Illuminate\Support\Number::format(
                                        (float) $record->transmittalGudangKirimDetails->sum('quantity')
                                    ))
                                    ->badge()
                                    ->color('primary')
                                    ->icon('heroicon-o-cube'),
                            ])
                            ->columns(2),

                        // Daftar item (rapih & interaktif)
                        \Filament\Infolists\Components\RepeatableEntry::make('transmittalGudangKirimDetails')
                            ->label('Daftar Item')
                            ->columns(12)
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('item_no')
                                    ->label('No.')
                                    ->prefix('Item ')
                                    ->color('primary')
                                    ->columnSpan(3),

                                \Filament\Infolists\Components\TextEntry::make('material_code')
                                    ->label('Kode Material')
                                    ->badge()
                                    ->copyable()
                                    ->copyMessage('Kode material disalin.')
                                    ->copyMessageDuration(1500)
                                    ->icon('heroicon-o-qr-code')
                                    ->color('primary')
                                    ->tooltip(fn($state) => $state ?? '')
                                    ->columnSpan(3),

                                \Filament\Infolists\Components\TextEntry::make('uoi')
                                    ->label('UoI')
                                    ->badge()
                                    ->color('gray')
                                    ->icon('heroicon-o-beaker')
                                    ->columnSpan(3),

                                \Filament\Infolists\Components\TextEntry::make('quantity')
                                    ->label('Qty')
                                    ->icon('heroicon-o-cube')
                                    ->badge()
                                    ->formatStateUsing(fn($state) => \Illuminate\Support\Number::format((float) $state))
                                    ->color(fn($state) => ((float) $state) > 0 ? 'success' : 'danger')
                                    ->tooltip(fn($state) => 'Qty: ' . $state)
                                    ->columnSpan(3),

                                \Filament\Infolists\Components\TextEntry::make('description')
                                    ->label('Deskripsi')
                                    ->icon('heroicon-o-document-text')
                                    ->prose()
                                    ->columnSpan(12),
                            ]),
                    ])
                    ->visible(fn($record) => $record?->transmittalGudangKirimDetails?->count() > 0),
            ])

            // === BULK ACTIONS ===
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])

            // === EMPTY STATE ===
            ->emptyStateHeading('Belum ada Transmittal')
            ->emptyStateDescription('Silakan buat transmittal baru untuk mulai mengirim dokumen ke gudang.')
            ->emptyStateIcon('heroicon-o-document-plus');
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
            'index' => Pages\ListTransmittalGudangKirims::route('/'),
            'create' => Pages\CreateTransmittalGudangKirim::route('/create'),
            'view' => Pages\ViewTransmittalGudangKirim::route('/{record}'),
            'edit' => Pages\EditTransmittalGudangKirim::route('/{record}/edit'),
        ];
    }
}
