<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\GrsRdtv;
use App\Filament\Resources\ReturnDeliveryToVendorResource\Pages;
use App\Filament\Resources\ReturnDeliveryToVendorResource\RelationManagers;
use App\Models\DeliveryOrderReceipt;
use App\Models\GoodsReceiptSlipDetail;
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
                                ->displayFormat('l, d F Y')
                                ->native(false)
                                ->prefixIcon('heroicon-m-calendar-days')
                                ->placeholder('Pilih Tanggal Terbit')
                                ->default(now())
                                ->required(),

                            // == KODE DOKUMEN (mirroring GRS) ==
                            TextInput::make('code')
                                ->label('Kode Dokumen (Scan QR)')
                                ->placeholder('Contoh: 5000001269086PLJ072514072025')
                                ->prefixIcon('heroicon-o-qr-code')
                                ->autofocus()
                                ->live(debounce: 500) // sama seperti GRS
                                ->required()
                                ->minLength(15)
                                ->unique(ignoreRecord: true)
                                // Tolak 14 digit murni (itu Kode 124)
                                ->rule(fn() => function (string $attribute, $value, \Closure $fail) {
                                    $v = trim((string) $value);
                                    if ($v !== '' && preg_match('/^\d{14}$/', $v)) {
                                        $fail('Sepertinya Anda mengisi Kode 124 di kolom "Kode Dokumen". Silakan pindahkan ke kolom "Kode 124".');
                                    }
                                })
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $code = trim((string) $state);

                                    if ($code === '') {
                                        $set('delivery_order_receipt_id', null);
                                        $set('returnDeliveryToVendorDetails', []);
                                        return;
                                    }

                                    if (preg_match('/^\d{14}$/', $code)) {
                                        // 103?
                                        $is103 = DB::table('transmittal_kirims')
                                            ->where('code_103', $code)
                                            ->exists();

                                        if ($is103) {
                                            $set('code', null);
                                            Notification::make()
                                                ->title('Bukan Kode Dokumen (DO)')
                                                ->body('Nilai ini terdeteksi sebagai Kode 103 (QC). Masukkan di tempat yang sesuai.')
                                                ->danger()
                                                ->send();
                                            return;
                                        }

                                        // 105? (punya GRS)
                                        $is105 = DB::table('goods_receipt_slips')
                                            ->where('code_105', $code)
                                            ->exists();

                                        if ($is105) {
                                            $set('code', null);
                                            Notification::make()
                                                ->title('Bukan Kode Dokumen (DO)')
                                                ->body('Nilai ini terdeteksi sebagai Kode 105 (GRS). Masukkan di form GRS.')
                                                ->danger()
                                                ->send();
                                            return;
                                        }

                                        // selain itu anggap 124 → pindahkan ke code_124
                                        $set('code_124', $code);
                                        $set('code', null);
                                        $set('focus_code', true);

                                        Notification::make()
                                            ->title('Dipindahkan ke "Kode 124"')
                                            ->body('Input terdeteksi 14 digit (Kode 124). Kami memindahkannya otomatis.')
                                            ->info()
                                            ->send();

                                        // reset dependen
                                        $set('delivery_order_receipt_id', null);
                                        $set('returnDeliveryToVendorDetails', []);
                                        return;
                                    }

                                    if (mb_strlen($code) < 15) {
                                        Notification::make()
                                            ->title('Kode Dokumen terlalu pendek')
                                            ->body('Minimal 15 karakter. Jika 14 digit murni, itu Kode 124 dan harus diisi di kolom "Kode 124".')
                                            ->warning()
                                            ->send();

                                        $set('delivery_order_receipt_id', null);
                                        $set('returnDeliveryToVendorDetails', []);
                                        return;
                                    }

                                    // 4) Lookup DO berdasarkan do_code
                                    $do = DeliveryOrderReceipt::with('deliveryOrderReceiptDetails')
                                        ->where('do_code', $code)
                                        ->first();

                                    if (!$do) {
                                        $set('delivery_order_receipt_id', null);
                                        $set('returnDeliveryToVendorDetails', []);

                                        Notification::make()
                                            ->title('Kode Dokumen tidak ditemukan')
                                            ->body('Pastikan scan QR DO yang benar atau periksa kembali input Anda.')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    // 5) Isi relasi & detail retur (hanya item dengan sisa qty > 0)
                                    $set('delivery_order_receipt_id', $do->id);

                                    // Total GRS per item_no (sudah di-issue ke GRS)
                                    $grsDetails = GoodsReceiptSlipDetail::whereHas('goodsReceiptSlip', function ($q) use ($do) {
                                        $q->where('delivery_order_receipt_id', $do->id);
                                    })->get();

                                    $grsGrouped = $grsDetails
                                        ->groupBy('item_no')
                                        ->map(fn($items) => $items->sum('quantity'));

                                    $details = $do->deliveryOrderReceiptDetails->map(function ($item) use ($grsGrouped) {
                                        $qtyInDO = $item->quantity;
                                        $qtyInGRS = $grsGrouped[$item->item_no] ?? 0;
                                        $sisaQty = $qtyInDO - $qtyInGRS;

                                        if ($sisaQty <= 0) {
                                            return null; // tidak perlu dimunculkan
                                        }

                                        return [
                                            'delivery_order_receipt_detail_id' => $item->id,
                                            'item_no' => $item->item_no,
                                            'material_code' => $item->material_code,
                                            'description' => $item->description,
                                            'quantity' => $sisaQty, // sisa yang bisa diretur
                                            'uoi' => $item->uoi,
                                        ];
                                    })->filter()->values();

                                    $set('returnDeliveryToVendorDetails', $details->toArray());
                                })
                                // Alpine hook: refocus input code saat auto-pindah
                                ->extraAttributes([
                                    'x-ref' => 'codeInput',
                                    'x-init' => 'if (new URLSearchParams(window.location.search).get("focus")) { $nextTick(() => { ($el.tagName==="INPUT"?$el:$el.querySelector("input"))?.focus() }) }',
                                ]),

                            // == KODE 124 (mirror Kode 105 di GRS) ==
                            TextInput::make('code_124')
                                ->label('Kode 124')
                                ->placeholder('Contoh: 50065500972025') // 14 digit
                                ->prefixIcon('heroicon-o-qr-code')
                                ->live(debounce: 500)
                                ->minLength(14)
                                ->maxLength(14)
                                ->required()
                                ->unique(ignoreRecord: true)
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

                                    // 103?
                                    if (DB::table('transmittal_kirims')->where('code_103', $v)->exists()) {
                                        $fail('Nilai ini adalah Kode 103 (QC), bukan Kode 124.');
                                        return;
                                    }

                                    // 105?
                                    if (DB::table('goods_receipt_slips')->where('code_105', $v)->exists()) {
                                        $fail('Nilai ini adalah Kode 105 (GRS), bukan Kode 124.');
                                        return;
                                    }

                                    // prefix DO (potongan do_code) – tetap
                                    $isDoPrefix = DB::table('delivery_order_receipts')
                                        ->where('do_code', 'like', $v . '%')
                                        ->whereRaw('CHAR_LENGTH(do_code) > 14')
                                        ->exists();

                                    if ($isDoPrefix) {
                                        $fail('Nilai ini terdeteksi sebagai potongan Kode Dokumen (DO). Scan QR DO pada kolom "Kode Dokumen".');
                                    }
                                })
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $v = trim((string) $state);
                                    if ($v === '')
                                        return;

                                    // A) tepat 14 digit
                                    if (preg_match('/^\d{14}$/', $v)) {
                                        // 103 masquerading as 124
                                        $is103 = DB::table('transmittal_kirims')->where('code_103', $v)->exists();
                                        if ($is103) {
                                            $set('code_124', null);
                                            Notification::make()
                                                ->title('Bukan Kode 124')
                                                ->body('Nilai ini terdeteksi sebagai Kode 103 (QC).')
                                                ->danger()
                                                ->send();
                                            return;
                                        }

                                        // 14-digit ini ternyata potongan DO
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
                                        return; // valid sebagai 124
                                    }

                                    // B) bukan 14 digit → jika >= 15 atau mengandung huruf, anggap DO dan pindahkan
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

                                    // Angka < 14 digit → minta lengkapi
                                    Notification::make()
                                        ->title('Lengkapi 14 digit')
                                        ->body('Kode 124 harus tepat 14 digit angka.')
                                        ->warning()
                                        ->send();
                                }),

                            Hidden::make('delivery_order_receipt_id')->required(),
                            Hidden::make('created_by')->default(Auth::id()),
                            // Flag untuk re-focus input code (mirroring GRS)
                            Hidden::make('focus_code')
                                ->default(false)
                                ->dehydrated(false),
                        ]),
                    ]),

                Section::make('Daftar Item Retur')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->description('Item ditarik otomatis dari Delivery Order setelah scan QR.')
                    ->schema([
                        Repeater::make('returnDeliveryToVendorDetails')
                            ->label('')
                            ->relationship() // pastikan relasi di model sesuai
                            ->schema([
                                Hidden::make('delivery_order_receipt_detail_id'),
                                TextInput::make('item_no')->label('Item No')->disabled()->dehydrated(),
                                TextInput::make('material_code')->label('Kode Material')->disabled()->dehydrated(),
                                TextInput::make('description')->label('Deskripsi')->disabled()->dehydrated(),
                                TextInput::make('quantity')->label('Quantity')->numeric()->required()->disabled()->dehydrated(),
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
