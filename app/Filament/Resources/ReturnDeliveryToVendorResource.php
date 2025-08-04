<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\GrsRdtv;
use App\Filament\Resources\ReturnDeliveryToVendorResource\Pages;
use App\Filament\Resources\ReturnDeliveryToVendorResource\RelationManagers;
use App\Models\ReturnDeliveryToVendor;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class ReturnDeliveryToVendorResource extends Resource
{
    protected static ?string $model = ReturnDeliveryToVendor::class;
    protected static ?string $cluster = GrsRdtv::class;
    protected static ?string $label = 'Dokumen RDTV';
    protected static ?string $navigationGroup = 'Return Delivery to Vendor (RDTV)';
    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';
    protected static ?string $activeNavigationIcon = 'heroicon-s-arrow-uturn-left';
    protected static ?int $navigationSort = 3;
    protected static ?string $slug = 'dokumen-rdtv';
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::count();
        return $count < 1 ? 'danger' : 'info';
    }
    protected static ?string $navigationBadgeTooltip = 'Total Dokumen RDTV';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Scan & Informasi Retur')
                    ->icon('heroicon-o-qr-code')
                    ->description('Scan kode QR dari Delivery Order untuk menarik data secara otomatis.')
                    ->schema([
                        Grid::make(3)->schema([
                            DatePicker::make('tanggal_terbit')
                                ->label('Tanggal Terbit')
                                ->placeholder('Pilih Tanggal Terbit')
                                ->displayFormat('l, d F Y')
                                ->native(false)
                                ->required()
                                ->default(now())
                                ->prefixIcon('heroicon-o-calendar'),

                            TextInput::make('code')
                                ->label('Kode Dokumen (Scan QR)')
                                ->placeholder('Contoh: 5000001269086PLJ072514072025')
                                ->prefixIcon('heroicon-o-qr-code')
                                ->required()
                                ->autoFocus()
                                ->unique(ignoreRecord: true)
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $do = \App\Models\DeliveryOrderReceipt::with('deliveryOrderReceiptDetails')->where('do_code', $state)->first();

                                    if (!$do) {
                                        $set('returnDeliveryToVendorDetails', []);
                                        $set('delivery_order_receipt_id', null);
                                        return;
                                    }

                                    $set('delivery_order_receipt_id', $do->id);

                                    // Ambil semua item yang sudah pernah di-GRS berdasarkan DO ini
                                    $grsDetails = \App\Models\GoodsReceiptSlipDetail::whereHas('goodsReceiptSlip', function ($q) use ($do) {
                                        $q->where('delivery_order_receipt_id', $do->id);
                                    })->get();

                                    // Hitung total qty GRS per item_no
                                    $grsGrouped = $grsDetails->groupBy('item_no')->map(function ($items) {
                                        return $items->sum('quantity');
                                    });

                                    // Buat list item sisa
                                    $details = $do->deliveryOrderReceiptDetails->map(function ($item) use ($grsGrouped) {
                                        $qtyInDO = $item->quantity;
                                        $qtyInGRS = $grsGrouped[$item->item_no] ?? 0;
                                        $sisaQty = $qtyInDO - $qtyInGRS;

                                        if ($sisaQty <= 0)
                                            return null; // skip kalau tidak ada sisa

                                        return [
                                            'item_no' => $item->item_no,
                                            'material_code' => $item->material_code,
                                            'description' => $item->description,
                                            'quantity' => $sisaQty,
                                            'uoi' => $item->uoi,
                                        ];
                                    })->filter()->values(); // filter null, reset index

                                    $set('returnDeliveryToVendorDetails', $details->toArray());
                                }),

                            TextInput::make('code_124')
                                ->label('Kode 124')
                                ->placeholder('Contoh: 5006550097')
                                ->prefixIcon('heroicon-o-qr-code')
                                ->required(),

                            Hidden::make('delivery_order_receipt_id')->required(),
                            Hidden::make('created_by')->default(Auth::user()->id),
                        ])
                    ]),

                Section::make('Daftar Item Retur')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->description('Item ditarik otomatis dari Delivery Order setelah scan QR.')
                    ->schema([
                        Repeater::make('returnDeliveryToVendorDetails')
                            ->label('')
                            ->relationship()
                            ->schema([
                                TextInput::make('item_no')->label('Item No')->disabled()->dehydrated(),
                                TextInput::make('material_code')->label('Kode Material')->disabled()->dehydrated(),
                                TextInput::make('description')->label('Deskripsi')->disabled()->dehydrated(),
                                TextInput::make('quantity')->label('Quantity')->numeric()->disabled()->dehydrated(),
                                TextInput::make('uoi')->label('UoI')->disabled()->dehydrated(),
                            ])
                            ->columns(5)
                            ->default([])
                            ->addable(false)
                            ->reorderable(false)
                            ->columnSpanFull()
                    ]),

                Section::make('Catatan Tambahan')
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        Textarea::make('keterangan')
                            ->label('Keterangan (Opsional)')
                            ->rows(3)
                            ->placeholder('Tambahkan catatan jika perlu...')
                            ->autoSize()
                            ->columnSpanFull(),
                    ]),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->withCount('returnDeliveryToVendorDetails')
                    ->withSum('returnDeliveryToVendorDetails', 'quantity')
                    ->latest();
            })
            ->groups([
                Group::make('tanggal_terbit')
                    ->label('Tanggal Terbit')
                    ->date()
                    ->collapsible(),
            ])
            ->defaultGroup(
                Group::make('tanggal_terbit')
                    ->label('Tanggal Terbit')
                    ->date()
                    ->collapsible(),
            )
            ->columns([
                TextColumn::make('tanggal_terbit')
                    ->label('Tanggal Terbit')
                    ->date('l, d F Y')
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('deliveryOrderReceipts.purchaseOrderTerbits.purchase_order_no')
                    ->label('Nomor PO')
                    ->icon('heroicon-s-document-text')
                    ->description(fn($record) => 'Kode 124: ' . ($record->code_124 ?? '-'))
                    ->color('primary')
                    ->searchable(),

                TextColumn::make('return_delivery_to_vendor_details_count')
                    ->label('Total Item')
                    ->badge()
                    ->suffix(' item')
                    ->color('danger')
                    ->icon('heroicon-s-cube')
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->label('Grand Total')
                            ->suffix(' item')
                    ),

                TextColumn::make('createdBy.name')
                    ->label('Dibuat Oleh')
                    ->badge()
                    ->icon('heroicon-s-user')
                    ->color('warning'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->tooltip('Lihat detail dokumen'),
                    Tables\Actions\EditAction::make()
                        ->tooltip('Edit data dokumen'),
                ])
                    ->icon('heroicon-o-ellipsis-horizontal-circle')
                    ->color('info')
                    ->tooltip('Aksi lainnya'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
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
            'index' => Pages\ListReturnDeliveryToVendors::route('/'),
            'create' => Pages\CreateReturnDeliveryToVendor::route('/create'),
            'view' => Pages\ViewReturnDeliveryToVendor::route('/{record}'),
            'edit' => Pages\EditReturnDeliveryToVendor::route('/{record}/edit'),
        ];
    }
}
