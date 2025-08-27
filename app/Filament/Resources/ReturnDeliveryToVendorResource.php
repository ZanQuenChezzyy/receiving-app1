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
use Filament\Notifications\Notification;
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
use Illuminate\Support\Facades\DB;

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
                                ->prefixIcon('heroicon-m-calendar-days'),

                            TextInput::make('code')
                                ->label('Kode Dokumen (Scan QR)')
                                ->placeholder('Contoh: 5000001269086PLJ072514072025')
                                ->prefixIcon('heroicon-o-qr-code')
                                ->required()
                                ->autoFocus()
                                ->minLength(15)
                                ->unique(ignoreRecord: true)
                                ->live(debounce: 300)
                                // Tolak 14 digit murni (itu kode 124/105)
                                ->rule(fn() => function (string $attribute, $value, \Closure $fail) {
                                    $v = trim((string) $value);
                                    if ($v !== '' && preg_match('/^\d{14}$/', $v)) {
                                        $fail('Sepertinya Anda mengisi Kode 124 di kolom "Kode Dokumen". Silakan pindahkan ke kolom "Kode 124".');
                                    }
                                })
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $code = trim((string) $state);

                                    // Jika 14 digit → pindahkan ke code_124 + notifikasi
                                    if ($code !== '' && preg_match('/^\d{14}$/', $code)) {
                                        $set('code_124', $code);
                                        $set('code', null);

                                        Notification::make()
                                            ->title('Dipindahkan ke "Kode 124"')
                                            ->body('Input terdeteksi 14 digit (Kode 124). Kami memindahkannya otomatis.')
                                            ->info()
                                            ->send();

                                        return;
                                    }

                                    // --- logic tarik DO (tetap seperti semula) ---
                                    $do = \App\Models\DeliveryOrderReceipt::with('deliveryOrderReceiptDetails')
                                        ->where('do_code', $state)
                                        ->first();

                                    if (!$do) {
                                        $set('returnDeliveryToVendorDetails', []);
                                        $set('delivery_order_receipt_id', null);
                                        return;
                                    }

                                    $set('delivery_order_receipt_id', $do->id);

                                    $grsDetails = \App\Models\GoodsReceiptSlipDetail::whereHas('goodsReceiptSlip', function ($q) use ($do) {
                                        $q->where('delivery_order_receipt_id', $do->id);
                                    })->get();

                                    $grsGrouped = $grsDetails->groupBy('item_no')->map(fn($items) => $items->sum('quantity'));

                                    $details = $do->deliveryOrderReceiptDetails->map(function ($item) use ($grsGrouped) {
                                        $qtyInDO = $item->quantity;
                                        $qtyInGRS = $grsGrouped[$item->item_no] ?? 0;
                                        $sisaQty = $qtyInDO - $qtyInGRS;

                                        if ($sisaQty <= 0)
                                            return null;

                                        return [
                                            'delivery_order_receipt_detail_id' => $item->id,
                                            'item_no' => $item->item_no,
                                            'material_code' => $item->material_code,
                                            'description' => $item->description,
                                            'quantity' => $sisaQty,
                                            'uoi' => $item->uoi,
                                        ];
                                    })->filter()->values();

                                    $set('returnDeliveryToVendorDetails', $details->toArray());
                                }),

                            TextInput::make('code_124')
                                ->label('Kode 124')
                                ->placeholder('Contoh: 5006550097')
                                ->prefixIcon('heroicon-o-qr-code')
                                ->live(debounce: 300)
                                ->minLength(14)
                                ->maxLength(14)
                                ->unique(ignoreRecord: true)
                                ->required()
                                // Wajib 14 digit & tolak prefix DO terpotong
                                ->rule(fn() => function (string $attribute, $value, \Closure $fail) {
                                    $v = trim((string) $value);
                                    if ($v === '')
                                        return;

                                    if (!preg_match('/^\d{14}$/', $v)) {
                                        if (preg_match('/[A-Za-z]/', $v) || strlen($v) > 14) {
                                            $fail('Sepertinya Anda mengisi "Kode Dokumen" di kolom "Kode 124". Silakan pindahkan ke "Kode Dokumen (Scan QR)".');
                                        } else {
                                            $fail('Format Kode 124 harus 14 digit angka.');
                                        }
                                        return;
                                    }

                                    $isDoPrefix = DB::table('delivery_order_receipts')
                                        ->where('do_code', 'like', $v . '%')
                                        ->whereRaw('CHAR_LENGTH(do_code) > 14')
                                        ->exists();

                                    if ($isDoPrefix) {
                                        $fail('Nilai ini terdeteksi sebagai potongan Kode Dokumen (DO). Scan QR DO pada kolom "Kode Dokumen", bukan di "Kode 124".');
                                    }
                                })
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $v = trim((string) $state);
                                    if ($v === '')
                                        return;

                                    if (preg_match('/^\d{14}$/', $v)) {
                                        $is103 = DB::table('transmittal_kirims')
                                            ->where('code_103', $v)
                                            ->exists();

                                        if ($is103) {
                                            $set('code_124', null);
                                            Notification::make()
                                                ->title('Bukan Kode 124')
                                                ->body('Nilai ini terdeteksi sebagai Kode 103 (QC).')
                                                ->danger()
                                                ->send();
                                            return;
                                        }

                                        $isDoPrefix = DB::table('delivery_order_receipts')
                                            ->where('do_code', 'like', $v . '%')
                                            ->whereRaw('CHAR_LENGTH(do_code) > 14')
                                            ->exists();

                                        if ($isDoPrefix) {
                                            $set('code_124', null);
                                            Notification::make()
                                                ->title('Kode Dokumen ter-scan di kolom 124')
                                                ->body('Nilai 14 digit ini adalah potongan Kode Dokumen (DO). Scan di kolom "Kode Dokumen".')
                                                ->danger()
                                                ->send();
                                        }
                                        return;
                                    }

                                    if (strlen($v) >= 15 || preg_match('/[A-Za-z]/', $v)) {
                                        $set('code', $v);
                                        $set('code_124', null);

                                        Notification::make()
                                            ->title('Dipindahkan ke "Kode Dokumen"')
                                            ->body('Input terdeteksi sebagai Kode Dokumen (bukan 14 digit).')
                                            ->info()
                                            ->send();
                                        return;
                                    }

                                    Notification::make()
                                        ->title('Lengkapi 14 digit')
                                        ->body('Kode 124 harus tepat 14 digit angka.')
                                        ->warning()
                                        ->send();
                                }),

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
                                Hidden::make('delivery_order_receipt_detail_id'),
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
