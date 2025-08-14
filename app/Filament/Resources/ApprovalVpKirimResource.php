<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\GrsRdtv;
use App\Filament\Resources\ApprovalVpKirimResource\Pages;
use App\Filament\Resources\ApprovalVpKirimResource\RelationManagers;
use App\Models\ApprovalVpKirim;
use App\Models\DeliveryOrderReceipt;
use App\Models\GoodsReceiptSlip;
use App\Models\ReturnDeliveryToVendor;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class ApprovalVpKirimResource extends Resource
{
    protected static ?string $model = ApprovalVpKirim::class;
    protected static ?string $cluster = GrsRdtv::class;
    protected static ?string $label = 'Kirim';
    protected static ?string $navigationGroup = 'Approval VP';
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-on-square';
    protected static ?string $activeNavigationIcon = 'heroicon-s-arrow-up-on-square';
    protected static ?int $navigationSort = 5;
    protected static ?string $slug = 'approval-vp-kirim';
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::count();
        return $count < 1 ? 'danger' : 'info';
    }
    protected static ?string $navigationBadgeTooltip = 'Total Dokumen Kirim';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Kirim')
                    ->icon('heroicon-o-truck')
                    ->description('Isi tanggal kirim dan scan kode dokumen untuk menarik data otomatis.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('tanggal_kirim')
                                    ->label('Tanggal Kirim')
                                    ->displayFormat('l, d F Y')
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->default(now())
                                    ->required(),

                                TextInput::make('code')
                                    ->label('Kode Dokumen (Scan QR)')
                                    ->placeholder('Contoh: 5000001269086PLJ072514072025')
                                    ->prefixIcon('heroicon-o-qr-code')
                                    ->autoFocus()
                                    ->live()
                                    ->required()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get, \Filament\Forms\Get $form) {
                                        $items = [];

                                        // Cari di GRS
                                        $grs = GoodsReceiptSlip::with('goodsReceiptSlipDetails')
                                            ->where('code', $state)
                                            ->first();

                                        if ($grs) {
                                            $grsItems = $grs->goodsReceiptSlipDetails->map(function ($item) {
                                                return [
                                                    'status' => '105', // GRS
                                                    'item_no' => $item->item_no,
                                                    'material_code' => $item->material_code ?? '-',
                                                    'description' => $item->description,
                                                    'quantity' => $item->quantity,
                                                    'uoi' => $item->uoi,
                                                ];
                                            })->toArray();

                                            $items = array_merge($items, $grsItems);
                                        }

                                        // Cari di RDTV
                                        $rdtv = ReturnDeliveryToVendor::with('returnDeliveryToVendorDetails')
                                            ->where('code', $state)
                                            ->first();

                                        if ($rdtv) {
                                            $rdtvItems = $rdtv->returnDeliveryToVendorDetails->map(function ($item) {
                                                return [
                                                    'status' => '124', // RDTV
                                                    'item_no' => $item->item_no,
                                                    'material_code' => $item->material_code ?? '-',
                                                    'description' => $item->description,
                                                    'quantity' => $item->quantity,
                                                    'uoi' => $item->uoi,
                                                ];
                                            })->toArray();

                                            $items = array_merge($items, $rdtvItems);
                                        }

                                        if (empty($items)) {
                                            // Tampilkan notifikasi error di tempat
                                            Notification::make()
                                                ->title('Kode dokumen tidak ditemukan')
                                                ->danger()
                                                ->send();

                                            // Kosongkan repeater
                                            $set('items', []);

                                            return;
                                        }

                                        // Set ke repeater
                                        $set('items', $items);
                                    }),

                                Hidden::make('created_by')->default(Auth::id()),
                            ]),
                    ]),

                Section::make('Daftar Item')
                    ->icon('heroicon-o-list-bullet')
                    ->description('Item akan otomatis terisi setelah scan kode dokumen.')
                    ->schema([
                        Repeater::make('items')
                            ->label('')
                            ->schema([
                                TextInput::make('item_no')->label('Item No')->disabled()->columnSpan(1),
                                TextInput::make('material_code')->label('Material Code')->disabled()->columnSpan(1),
                                TextInput::make('description')->label('Description')->disabled()->columnSpan(4),
                                TextInput::make('status')->label('Status')->disabled()->columnSpan(1),
                                TextInput::make('quantity')->label('Quantity')->disabled()->columnSpan(1),
                                TextInput::make('uoi')->label('UOI')->disabled()->columnSpan(1),
                            ])
                            ->columns(9)
                            ->default([])
                            ->columnSpanFull()
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->afterStateHydrated(function ($state, callable $set, callable $get) {
                                $code = $get('code');

                                if (!$code) {
                                    return;
                                }

                                $items = [];

                                // Cari di GRS
                                $grs = GoodsReceiptSlip::with('goodsReceiptSlipDetails')
                                    ->where('code', $code)
                                    ->first();

                                if ($grs) {
                                    $grsItems = $grs->goodsReceiptSlipDetails->map(function ($item) {
                                        return [
                                            'status' => '105', // GRS
                                            'item_no' => $item->item_no,
                                            'material_code' => $item->material_code ?? '-',
                                            'description' => $item->description,
                                            'quantity' => $item->quantity,
                                            'uoi' => $item->uoi,
                                        ];
                                    })->toArray();

                                    $items = array_merge($items, $grsItems);
                                }

                                // Cari di RDTV
                                $rdtv = ReturnDeliveryToVendor::with('returnDeliveryToVendorDetails')
                                    ->where('code', $code)
                                    ->first();

                                if ($rdtv) {
                                    $rdtvItems = $rdtv->returnDeliveryToVendorDetails->map(function ($item) {
                                        return [
                                            'status' => '124', // RDTV
                                            'item_no' => $item->item_no,
                                            'material_code' => $item->material_code ?? '-',
                                            'description' => $item->description,
                                            'quantity' => $item->quantity,
                                            'uoi' => $item->uoi,
                                        ];
                                    })->toArray();

                                    $items = array_merge($items, $rdtvItems);
                                }

                                $set('items', $items);
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query->latest(); // Urut berdasarkan created_at DESC
            })
            ->groups([
                Group::make('tanggal_kirim')
                    ->label('Tanggal Kirim')
                    ->date()
            ])
            ->defaultGroup(
                Group::make('tanggal_kirim')
                    ->label('Tanggal Kirim')
                    ->date()
            )
            ->columns([
                TextColumn::make('tanggal_kirim')
                    ->label('Tanggal Kirim')
                    ->date('l, d F Y')
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('purchase_order_no')
                    ->label('No. PO')
                    ->icon('heroicon-s-document-text')
                    ->color('primary')
                    ->searchable()
                    ->getStateUsing(function ($record) {
                        // ambil DO berdasarkan code
                        $do = DeliveryOrderReceipt::where('do_code', $record->code)
                            ->with('purchaseOrderTerbits')
                            ->first();
                        return $do?->purchaseOrderTerbits?->purchase_order_no ?? '-';
                    })
                    ->description(function ($record) {
                        $do = DeliveryOrderReceipt::where('do_code', $record->code)
                            ->withCount('deliveryOrderReceiptDetails')
                            ->first();
                        return 'Total Item: ' . ($do->delivery_order_receipt_details_count ?? 0);
                    }),

                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->getStateUsing(function ($record) {
                        $do = DeliveryOrderReceipt::where('do_code', $record->code)->first();

                        if (!$do) {
                            return ['Tidak ditemukan DO'];
                        }

                        $grsCount = \App\Models\GoodsReceiptSlipDetail::whereHas('goodsReceiptSlip', function ($q) use ($do) {
                            $q->where('delivery_order_receipt_id', $do->id);
                        })
                            ->count();

                        $rdtvCount = \App\Models\ReturnDeliveryToVendorDetail::whereHas('returnDeliveryToVendor', function ($q) use ($do) {
                            $q->where('delivery_order_receipt_id', $do->id);
                        })
                            ->count();

                        $parts = [];
                        $parts[] = $grsCount > 0 ? "105: {$grsCount} Item" : '105: Tidak ada';
                        $parts[] = $rdtvCount > 0 ? "124: {$rdtvCount} Item" : '124: Tidak ada';

                        return $parts; // array
                    })
                    ->listWithLineBreaks()
                    ->limitList(1)
                    ->expandableLimitedList()
                    ->bulleted()
                    ->alignLeft()
                    ->disabledClick()
                    ->color('info'),

                TextColumn::make('createdBy.name')
                    ->label('Dibuat Oleh')
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-s-user'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Terakhir Update')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                ])
                    ->icon('heroicon-o-ellipsis-horizontal-circle')
                    ->color('info')
                    ->tooltip('Aksi')
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
            'index' => Pages\ListApprovalVpKirims::route('/'),
            'create' => Pages\CreateApprovalVpKirim::route('/create'),
            'view' => Pages\ViewApprovalVpKirim::route('/{record}'),
            'edit' => Pages\EditApprovalVpKirim::route('/{record}/edit'),
        ];
    }
}
