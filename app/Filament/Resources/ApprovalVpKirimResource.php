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
use Illuminate\Support\Facades\DB;

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
                        Grid::make(2)->schema([
                            DatePicker::make('tanggal_kirim')
                                ->label('Tanggal Kirim')
                                ->displayFormat('l, d F Y')
                                ->native(false)
                                ->prefixIcon('heroicon-o-calendar-days')
                                ->default(now())
                                ->required(),

                            TextInput::make('code')
                                ->label('Kode Dokumen (Scan QR)')
                                ->placeholder('Contoh: 5000001269086PLJ072514072025')
                                ->prefixIcon('heroicon-o-qr-code')
                                ->autofocus() // autofocus standar
                                ->live(debounce: 500) // hindari query tiap ketik
                                ->required()
                                ->minLength(15)
                                ->unique(
                                    table: ApprovalVpKirim::class,
                                    column: 'code',
                                    ignoreRecord: true
                                )
                                // RULE: jika 14 digit, larang & jelaskan jenis (103/105/124)
                                ->rule(fn() => function (string $attribute, $value, \Closure $fail) {
                                    $v = trim((string) $value);
                                    if ($v === '')
                                        return;

                                    // Jika 14 digit → bukan Kode Dokumen DO
                                    if (preg_match('/^\d{14}$/', $v)) {
                                        $is103 = DB::table('transmittal_kirims')->where('code_103', $v)->exists();
                                        $is105 = DB::table('goods_receipt_slips')->where('code_105', $v)->exists();
                                        $is124 = DB::table('return_delivery_to_vendors')->where('code_124', $v)->exists();

                                        if ($is103) {
                                            $fail('Nilai ini adalah Kode 103 (QC), bukan Kode Dokumen DO.');
                                            return;
                                        }
                                        if ($is105) {
                                            $fail('Nilai ini adalah Kode 105 (GRS), bukan Kode Dokumen DO.');
                                            return;
                                        }
                                        if ($is124) {
                                            $fail('Nilai ini adalah Kode 124 (RDTV), bukan Kode Dokumen DO.');
                                            return;
                                        }

                                        $fail('Nilai 14 digit terdeteksi. Itu bukan Kode Dokumen DO.');
                                        return;
                                    }
                                })
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $code = trim((string) $state);

                                    // Reset ketika kosong
                                    if ($code === '') {
                                        $set('items', []);
                                        return;
                                    }

                                    // Jangan query kalau terlalu pendek
                                    if (mb_strlen($code) < 15) {
                                        Notification::make()
                                            ->title('Kode Dokumen terlalu pendek')
                                            ->body('Minimal 15 karakter. 14 digit adalah Kode 103/105/124 dan bukan Kode Dokumen DO.')
                                            ->warning()
                                            ->send();
                                        $set('items', []);
                                        return;
                                    }

                                    // Jika user tetap memasukkan 14 digit via paste → bersihkan & beri tahu
                                    if (preg_match('/^\d{14}$/', $code)) {
                                        $set('code', null);
                                        Notification::make()
                                            ->title('Bukan Kode Dokumen DO')
                                            ->body('Nilai 14 digit terdeteksi (103/105/124). Masukkan Kode Dokumen DO yang benar.')
                                            ->danger()
                                            ->send();
                                        $set('items', []);
                                        return;
                                    }

                                    // Tarik data dari GRS & RDTV berdasarkan code (DO code)
                                    $items = [];

                                    $grs = GoodsReceiptSlip::with('goodsReceiptSlipDetails')
                                        ->where('code', $code) // DO code di GRS
                                        ->first();

                                    if ($grs) {
                                        $items = array_merge($items, $grs->goodsReceiptSlipDetails->map(fn($i) => [
                                            'status' => '105',
                                            'item_no' => $i->item_no,
                                            'material_code' => $i->material_code ?? '-',
                                            'description' => $i->description,
                                            'quantity' => $i->quantity,
                                            'uoi' => $i->uoi,
                                        ])->toArray());
                                    }

                                    $rdtv = ReturnDeliveryToVendor::with('returnDeliveryToVendorDetails')
                                        ->where('code', $code) // DO code di RDTV
                                        ->first();

                                    if ($rdtv) {
                                        $items = array_merge($items, $rdtv->returnDeliveryToVendorDetails->map(fn($i) => [
                                            'status' => '124',
                                            'item_no' => $i->item_no,
                                            'material_code' => $i->material_code ?? '-',
                                            'description' => $i->description,
                                            'quantity' => $i->quantity,
                                            'uoi' => $i->uoi,
                                        ])->toArray());
                                    }

                                    if (empty($items)) {
                                        Notification::make()
                                            ->title('Kode dokumen tidak ditemukan')
                                            ->body('Tidak ada data GRS/RDTV dengan Kode Dokumen tersebut.')
                                            ->danger()
                                            ->send();
                                        $set('items', []);
                                        return;
                                    }

                                    $set('items', $items);
                                })
                                // Autofocus ekstra (andal) dengan Alpine (berguna setelah redirect create→create)
                                ->extraAttributes([
                                    'x-ref' => 'codeInput',
                                    'x-init' => '$nextTick(() => { ($el.tagName==="INPUT"?$el:$el.querySelector("input"))?.focus() })',
                                ]),

                            Hidden::make('created_by')->default(Auth::id()),
                        ]),
                    ]),

                Section::make('Daftar Item')
                    ->icon('heroicon-o-list-bullet')
                    ->description('Item akan otomatis terisi setelah scan kode dokumen.')
                    ->schema([
                        Repeater::make('items')
                            ->label('')
                            // Jika Anda menyimpan ke kolom JSON 'items'
                            ->dehydrated(true)
                            ->minItems(1)      // WAJIB minimal 1 item
                            ->required()       // validasi form
                            ->schema([
                                TextInput::make('item_no')->label('Item No')->disabled()->dehydrated(),
                                TextInput::make('material_code')->label('Material Code')->disabled()->dehydrated(),
                                TextInput::make('description')->label('Description')->disabled()->dehydrated(),
                                TextInput::make('status')->label('Status')->disabled()->dehydrated(),
                                TextInput::make('quantity')->label('Quantity')->disabled()->dehydrated(),
                                TextInput::make('uoi')->label('UOI')->disabled()->dehydrated(),
                            ])
                            ->columns(6)
                            ->default([])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->afterStateHydrated(function ($state, callable $set, callable $get) {
                                // Re-hydrate items jika user kembali ke form dengan state code
                                $code = trim((string) ($get('code') ?? ''));
                                if ($code === '' || mb_strlen($code) < 15) {
                                    return;
                                }

                                $items = [];

                                $grs = GoodsReceiptSlip::with('goodsReceiptSlipDetails')
                                    ->where('code', $code)
                                    ->first();

                                if ($grs) {
                                    $items = array_merge($items, $grs->goodsReceiptSlipDetails->map(fn($i) => [
                                        'status' => '105',
                                        'item_no' => $i->item_no,
                                        'material_code' => $i->material_code ?? '-',
                                        'description' => $i->description,
                                        'quantity' => $i->quantity,
                                        'uoi' => $i->uoi,
                                    ])->toArray());
                                }

                                $rdtv = ReturnDeliveryToVendor::with('returnDeliveryToVendorDetails')
                                    ->where('code', $code)
                                    ->first();

                                if ($rdtv) {
                                    $items = array_merge($items, $rdtv->returnDeliveryToVendorDetails->map(fn($i) => [
                                        'status' => '124',
                                        'item_no' => $i->item_no,
                                        'material_code' => $i->material_code ?? '-',
                                        'description' => $i->description,
                                        'quantity' => $i->quantity,
                                        'uoi' => $i->uoi,
                                    ])->toArray());
                                }

                                if (!empty($items)) {
                                    $set('items', $items);
                                }
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
