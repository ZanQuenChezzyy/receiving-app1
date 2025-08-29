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
use Filament\Notifications\Notification;
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
use Illuminate\Support\Facades\DB;

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
                        Grid::make(3)->schema([
                            DatePicker::make('tanggal_terbit')
                                ->label('Tanggal Terbit')
                                ->displayFormat('l, d F Y')
                                ->native(false)
                                ->live() // boleh live di date
                                ->prefixIcon('heroicon-m-calendar-days')
                                ->placeholder('Pilih Tanggal Terbit')
                                ->default(now())
                                ->required(),

                            TextInput::make('code')
                                ->label('Kode Dokumen (Scan QR)')
                                ->placeholder('Contoh: 5000001269086PLJ072514072025')
                                ->prefixIcon('heroicon-o-qr-code')
                                ->autofocus()
                                ->live(debounce: 500) // validasi jalan saat blur, biar ga “ribut” saat ngetik
                                ->required()
                                ->minLength(15)
                                // Hapus baris unique ini jika tabel GRS tidak punya kolom `code`
                                ->unique(
                                    table: GoodsReceiptSlip::class,
                                    column: 'code',
                                    ignoreRecord: true
                                )
                                // Tolak 14 digit murni (itu Kode 105), arahkan user:
                                ->rule(fn() => function (string $attribute, $value, \Closure $fail) {
                                    $v = trim((string) $value);
                                    if ($v !== '' && preg_match('/^\d{14}$/', $v)) {
                                        $fail('Sepertinya Anda mengisi Kode 105 di kolom "Kode Dokumen". Silakan pindahkan ke kolom "Kode 105".');
                                    }
                                })
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $code = trim((string) $state);

                                    if ($code === '') {
                                        $set('delivery_order_receipt_id', null);
                                        $set('goodsReceiptSlipDetails', []);
                                        return;
                                    }

                                    // 14 digit? cek jenis dulu
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

                                        // 124? (punya RDTV)
                                        $is124 = DB::table('return_delivery_to_vendors')
                                            ->where('code_124', $code)
                                            ->exists();

                                        if ($is124) {
                                            $set('code', null);
                                            Notification::make()
                                                ->title('Bukan Kode Dokumen (DO)')
                                                ->body('Nilai ini terdeteksi sebagai Kode 124 (RDTV). Masukkan di form RDTV.')
                                                ->danger()
                                                ->send();
                                            return;
                                        }

                                        // selain itu anggap 105 → pindahkan ke code_105
                                        $set('code_105', $code);
                                        $set('code', null);
                                        $set('focus_code', true);

                                        Notification::make()
                                            ->title('Dipindahkan ke "Kode 105"')
                                            ->body('Input terdeteksi 14 digit (Kode 105). Kami memindahkannya otomatis.')
                                            ->info()
                                            ->send();

                                        // reset dependen
                                        $set('delivery_order_receipt_id', null);
                                        $set('goodsReceiptSlipDetails', []);
                                        return;
                                    }

                                    // < 15 karakter → terlalu pendek
                                    if (mb_strlen($code) < 15) {
                                        Notification::make()
                                            ->title('Kode Dokumen terlalu pendek')
                                            ->body('Minimal 15 karakter. Jika 14 digit murni, itu Kode 105 dan harus diisi di kolom "Kode 105".')
                                            ->warning()
                                            ->send();

                                        $set('delivery_order_receipt_id', null);
                                        $set('goodsReceiptSlipDetails', []);
                                        return;
                                    }

                                    // 4) Lookup DO berdasarkan do_code
                                    $deliveryOrder = DeliveryOrderReceipt::with('deliveryOrderReceiptDetails')
                                        ->where('do_code', $code)
                                        ->first();

                                    if (!$deliveryOrder) {
                                        $set('goodsReceiptSlipDetails', []);
                                        $set('delivery_order_receipt_id', null);

                                        Notification::make()
                                            ->title('Kode Dokumen tidak ditemukan')
                                            ->body('Pastikan Anda men-scan QR DO yang benar atau periksa kembali input Anda.')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    // 5) Isi relasi & details
                                    $set('delivery_order_receipt_id', $deliveryOrder->id);

                                    $details = $deliveryOrder->deliveryOrderReceiptDetails->map(function ($item) {
                                        return [
                                            'item_no' => $item->item_no,
                                            'delivery_order_receipt_id' => $item->delivery_order_receipt_id,
                                            'delivery_order_receipt_detail_id' => $item->id,
                                            'material_code' => $item->material_code,
                                            'description' => $item->description,
                                            'quantity' => $item->quantity,
                                            'uoi' => $item->uoi,
                                        ];
                                    });

                                    $set('goodsReceiptSlipDetails', $details->toArray());
                                })
                                // Alpine hook: refocus & clear error `code_105` saat auto-pindah
                                ->extraAttributes([
                                    'x-ref' => 'codeInput',
                                    'x-init' => 'if (new URLSearchParams(window.location.search).get("focus")) { $nextTick(() => { ($el.tagName==="INPUT"?$el:$el.querySelector("input"))?.focus() }) }',
                                ]),

                            TextInput::make('code_105')
                                ->label('Kode 105')
                                ->placeholder('Contoh: 50065500972025') // 14 digit
                                ->prefixIcon('heroicon-o-qr-code')
                                ->live(debounce: 500)
                                ->minLength(14)
                                ->maxLength(14)
                                ->required()
                                // Hapus atau sesuaikan jika kolom unique-nya berbeda
                                ->unique(
                                    table: GoodsReceiptSlip::class,
                                    column: 'code_105',
                                    ignoreRecord: true
                                )
                                ->rule(fn() => function (string $attribute, $value, \Closure $fail) {
                                    $v = trim((string) $value);
                                    if ($v === '')
                                        return;

                                    if (!preg_match('/^\d{14}$/', $v)) {
                                        if (preg_match('/[A-Za-z]/', $v) || strlen($v) > 14) {
                                            $fail('Sepertinya Anda mengisi "Kode Dokumen" di kolom "Kode 105". Silakan pindahkan ke "Kode Dokumen (Scan QR)".');
                                        } else {
                                            $fail('Format Kode 105 harus 14 digit angka.');
                                        }
                                        return;
                                    }

                                    // 103?
                                    if (DB::table('transmittal_kirims')->where('code_103', $v)->exists()) {
                                        $fail('Nilai ini adalah Kode 103 (QC), bukan Kode 105.');
                                        return;
                                    }

                                    // 124?
                                    if (DB::table('return_delivery_to_vendors')->where('code_124', $v)->exists()) {
                                        $fail('Nilai ini adalah Kode 124 (RDTV), bukan Kode 105.');
                                        return;
                                    }

                                    // prefix DO (potongan do_code)
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
                                    if ($v === '') {
                                        return;
                                    }

                                    // A) tepat 14 digit → cek kasus-kasus salah kaprah
                                    if (preg_match('/^\d{14}$/', $v)) {
                                        // 103 masquerading as 105
                                        $is103 = DB::table('transmittal_kirims')->where('code_103', $v)->exists();
                                        if ($is103) {
                                            $set('code_105', null);
                                            Notification::make()
                                                ->title('Bukan Kode 105')
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
                                            $set('code_105', null);
                                            Notification::make()
                                                ->title('Kode Dokumen terscan di kolom 105')
                                                ->body('Nilai 14 digit ini adalah potongan Kode Dokumen (DO). Scan di kolom "Kode Dokumen".')
                                                ->danger()
                                                ->send();
                                        }

                                        return; // valid sebagai 105
                                    }

                                    // B) bukan 14 digit → jika >=15 atau mengandung huruf, anggap DO dan pindahkan
                                    if (strlen($v) >= 15 || preg_match('/[A-Za-z]/', $v)) {
                                        $set('code', $v);
                                        $set('code_105', null);

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
                                        ->body('Kode 105 harus tepat 14 digit angka.')
                                        ->warning()
                                        ->send();
                                }),

                            Hidden::make('delivery_order_receipt_id')->required(),
                            Hidden::make('created_by')->default(Auth::id()),
                            // Flag untuk re-focus input code
                            Hidden::make('focus_code')
                                ->default(false)
                                ->dehydrated(false),
                        ]),
                    ]),

                Section::make('Daftar Item GRS')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->description('Item ditarik otomatis dari Delivery Order setelah scan QR.')
                    ->schema([
                        Repeater::make('goodsReceiptSlipDetails')
                            ->label('')
                            ->relationship() // pastikan relasi bernama goodsReceiptSlipDetails
                            ->schema([
                                Hidden::make('delivery_order_receipt_detail_id'),
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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                    ->tooltip('Aksi'),
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
