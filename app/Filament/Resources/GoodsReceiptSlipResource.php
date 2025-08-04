<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\GrsRdtv;
use App\Filament\Resources\GoodsReceiptSlipResource\Pages;
use App\Filament\Resources\GoodsReceiptSlipResource\RelationManagers;
use App\Models\DeliveryOrderReceipt;
use App\Models\DeliveryOrderReceiptDetail;
use App\Models\GoodsReceiptSlip;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class GoodsReceiptSlipResource extends Resource
{
    protected static ?string $model = GoodsReceiptSlip::class;
    protected static ?string $cluster = GrsRdtv::class;
    protected static ?string $label = 'Dokumen GRS';
    protected static ?string $navigationGroup = 'Goods Receipt Slip (GRS)';
    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $activeNavigationIcon = 'heroicon-s-document-check';
    protected static ?int $navigationSort = 1;
    protected static ?string $slug = 'dokumen-grs';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::count();
        return $count < 1 ? 'danger' : 'success';
    }

    protected static ?string $navigationBadgeTooltip = 'Total Dokumen GRS';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Scan & Informasi GRS')
                    ->icon('heroicon-o-qr-code')
                    ->description('Scan kode QR dari Delivery Order untuk menarik data secara otomatis.')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                DatePicker::make('tanggal_terbit')
                                    ->label('Tanggal Terbit')
                                    ->displayFormat('l, d F Y')
                                    ->native(false)
                                    ->live()
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->placeholder('Pilih Tanggal Terbit')
                                    ->default(now())
                                    ->required(),

                                TextInput::make('code')
                                    ->label('Kode Dokumen (Scan QR)')
                                    ->placeholder('Contoh: 5000001269086PLJ072514072025')
                                    ->prefixIcon('heroicon-o-qr-code')
                                    ->autofocus()
                                    ->live()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $deliveryOrder = DeliveryOrderReceipt::with('deliveryOrderReceiptDetails')
                                            ->where('do_code', $state)
                                            ->first();

                                        if (!$deliveryOrder) {
                                            $set('goodsReceiptSlipDetails', []);
                                            $set('delivery_order_receipt_id', null);
                                            return;
                                        }

                                        $set('delivery_order_receipt_id', $deliveryOrder->id);

                                        $details = $deliveryOrder->deliveryOrderReceiptDetails->map(function ($item) {

                                            return [
                                                'item_no' => $item->item_no,
                                                'delivery_order_receipt_id' => $item->delivery_order_receipt_id,
                                                'material_code' => $item->material_code,
                                                'description' => $item->description,
                                                'quantity' => $item->quantity,
                                                'uoi' => $item->uoi,
                                            ];
                                        });

                                        $set('goodsReceiptSlipDetails', $details->toArray());
                                    }),

                                TextInput::make('code_105')
                                    ->label('Kode 105')
                                    ->placeholder('Contoh: 5006550097')
                                    ->prefixIcon('heroicon-o-qr-code')
                                    ->required(),

                                Hidden::make('delivery_order_receipt_id')->required(),
                                Hidden::make('created_by')->default(Auth::user()->id),
                            ]),
                    ]),

                Section::make('Daftar Item GRS')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->description('Item ditarik otomatis dari Delivery Order setelah scan QR.')
                    ->schema([
                        Repeater::make('goodsReceiptSlipDetails')
                            ->label('')
                            ->relationship()
                            ->schema([
                                TextInput::make('item_no')->label('Item No')->disabled()->dehydrated(),
                                TextInput::make('material_code')->label('Kode Material')->disabled()->dehydrated(),
                                TextInput::make('description')->label('Deskripsi')->disabled()->dehydrated(),
                                TextInput::make('quantity')->label('Quantity')->required()->numeric()->disabled()->dehydrated(),
                                TextInput::make('uoi')->label('UoI')->disabled()->dehydrated(),
                            ])
                            ->columns(5)
                            ->default([])
                            ->addable(false)
                            ->reorderable(false)
                            ->columnSpanFull(),
                    ]),

                Section::make('Catatan Tambahan')
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        Textarea::make('keterangan')
                            ->label('Keterangan (Opsional)')
                            ->placeholder('Tambahkan catatan jika diperlukan...')
                            ->rows(3)
                            ->autoSize()
                            ->columnSpanFull(),
                    ]),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->withCount('goodsReceiptSlipDetails')
                    ->withSum('goodsReceiptSlipDetails', 'quantity')
                    ->latest(); // ini tetap untuk urutkan DESC
            })
            ->groups([
                Group::make('tanggal_terbit')
                    ->label('Tanggal Terbit')
                    ->date(),
            ])
            ->defaultGroup(
                Group::make('tanggal_terbit')
                    ->label('Tanggal Terbit')
                    ->date(),
            )
            ->columns([
                TextColumn::make('tanggal_terbit')
                    ->label('Tanggal Terbit')
                    ->date('l, d F Y')
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('deliveryOrderReceipts.purchaseOrderTerbits.purchase_order_no')
                    ->label('Nomor PO & Kode 105')
                    ->icon('heroicon-s-document-text')
                    ->color('primary')
                    ->searchable()
                    ->description(fn($record) => 'Kode 105: ' . ($record->code_105 ?? '-')),

                TextColumn::make('goods_receipt_slip_details_count')
                    ->label('Total Item')
                    ->badge()
                    ->suffix(' item')
                    ->color('success')
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
                    ->numeric()
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
            ->filters([
                // Tambahkan filter jika perlu
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->tooltip('Lihat detail slip'),
                    Tables\Actions\EditAction::make()
                        ->tooltip('Edit data slip'),
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
            'index' => Pages\ListGoodsReceiptSlips::route('/'),
            'create' => Pages\CreateGoodsReceiptSlip::route('/create'),
            'view' => Pages\ViewGoodsReceiptSlip::route('/{record}'),
            'edit' => Pages\EditGoodsReceiptSlip::route('/{record}/edit'),
        ];
    }
}
