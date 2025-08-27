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
                        Grid::make(3)
                            ->schema([
                                DatePicker::make('tanggal_terbit')
                                    ->label('Tanggal Terbit')
                                    ->displayFormat('l, d F Y')
                                    ->native(false)
                                    ->live()
                                    ->prefixIcon('heroicon-m-calendar-days')
                                    ->placeholder('Pilih Tanggal Terbit')
                                    ->default(now())
                                    ->required(),

                                TextInput::make('code')
                                    ->label('Kode Dokumen (Scan QR)')
                                    ->placeholder('Contoh: 5000001269086PLJ072514072025')
                                    ->prefixIcon('heroicon-o-qr-code')
                                    ->autofocus()
                                    ->live(debounce: 300)
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->minLength(15)
                                    // Tolak 14 digit murni (itu kode 105/124)
                                    ->rule(fn() => function (string $attribute, $value, \Closure $fail) {
                                        $v = trim((string) $value);
                                        if ($v !== '' && preg_match('/^\d{14}$/', $v)) {
                                            $fail('Sepertinya Anda mengisi Kode 105 di kolom "Kode Dokumen". Silakan pindahkan ke kolom "Kode 105".');
                                        }
                                    })
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $code = trim((string) $state);

                                        // 1) Jika 14 digit → pindah ke code_105 + fokus balik ke code
                                        if ($code !== '' && preg_match('/^\d{14}$/', $code)) {
                                            $set('code_105', $code);
                                            $set('code', null);
                                            $set('focus_code', true); // trigger re-focus
                            
                                            Notification::make()
                                                ->title('Dipindahkan ke "Kode 105"')
                                                ->body('Input terdeteksi 14 digit (Kode 105). Kami memindahkannya otomatis.')
                                                ->info()
                                                ->send();

                                            return;
                                        }

                                        // --- logic tarik DO (tetap) ---
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
                                                'delivery_order_receipt_detail_id' => $item->id,
                                                'material_code' => $item->material_code,
                                                'description' => $item->description,
                                                'quantity' => $item->quantity,
                                                'uoi' => $item->uoi,
                                            ];
                                        });

                                        $set('goodsReceiptSlipDetails', $details->toArray());
                                    })
                                    // Alpine hook untuk re-focus saat focus_code = true
                                    ->extraAttributes([
                                        'x-data' => '{}',
                                        'x-effect' => "if (\$wire.get('data.focus_code')) { \$nextTick(()=>{ (\$el.tagName==='INPUT'?\$el:\$el.querySelector('input'))?.focus() }); \$wire.set('data.focus_code', false) }",
                                    ]),

                                TextInput::make('code_105')
                                    ->label('Kode 105')
                                    ->placeholder('Contoh: 5006550097')
                                    ->prefixIcon('heroicon-o-qr-code')
                                    ->live(debounce: 300)
                                    ->minLength(14)
                                    ->maxLength(14)
                                    ->unique(ignoreRecord: true)
                                    ->required()
                                    ->rule(fn() => function (string $attribute, $value, \Closure $fail) {
                                        $v = trim((string) $value);
                                        if ($v === '')
                                            return;

                                        // wajib 14 digit
                                        if (!preg_match('/^\d{14}$/', $v)) {
                                            if (preg_match('/[A-Za-z]/', $v) || strlen($v) > 14) {
                                                $fail('Sepertinya Anda mengisi "Kode Dokumen" di kolom "Kode 105". Silakan pindahkan ke "Kode Dokumen (Scan QR)".');
                                            } else {
                                                $fail('Format Kode 105 harus 14 digit angka.');
                                            }
                                            return;
                                        }

                                        // (2) Tolak bila ini sebenarnya Kode 103
                                        $is103 = DB::table('transmittal_kirims')->where('code_103', $v)->exists();
                                        if ($is103) {
                                            $fail('Nilai ini adalah Kode 103 (QC), bukan Kode 105.');
                                            return;
                                        }

                                        // (3) Tolak bila 14 digit ini adalah prefix dari DO (berarti DO terpotong)
                                        $isDoPrefix = DB::table('delivery_order_receipts')
                                            ->where('do_code', 'like', $v . '%')
                                            ->whereRaw('CHAR_LENGTH(do_code) > 14')
                                            ->exists();

                                        if ($isDoPrefix) {
                                            $fail('Nilai ini terdeteksi sebagai potongan Kode Dokumen (DO). Scan QR DO pada kolom "Kode Dokumen", bukan di "Kode 105".');
                                        }
                                    })
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $v = trim((string) $state);
                                        if ($v === '')
                                            return;

                                        // CASE A: pas 14 digit → kandidat 105, lakukan pengecekan lanjutan
                                        if (preg_match('/^\d{14}$/', $v)) {
                                            // Tolak jika ini sebenarnya Code 103
                                            $is103 = DB::table('transmittal_kirims')
                                                ->where('code_103', $v)
                                                ->exists();

                                            if ($is103) {
                                                $set('code_105', null);
                                                Notification::make()
                                                    ->title('Bukan Kode 105')
                                                    ->body('Nilai ini terdeteksi sebagai Kode 103 (QC).')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }

                                            // Tolak jika ini prefix dari DO (kode dokumen terpotong)
                                            $isDoPrefix = DB::table('delivery_order_receipts')
                                                ->where('do_code', 'like', $v . '%')
                                                ->whereRaw('CHAR_LENGTH(do_code) > 14')
                                                ->exists();

                                            if ($isDoPrefix) {
                                                $set('code_105', null);
                                                Notification::make()
                                                    ->title('Kode Dokumen ter-scan di kolom 105')
                                                    ->body('Nilai 14 digit ini adalah potongan Kode Dokumen (DO). Scan di kolom "Kode Dokumen".')
                                                    ->danger()
                                                    ->send();
                                            }

                                            return; // valid 14 digit (dan bukan 103/prefix DO) → biarkan tetap sebagai 105
                                        }

                                        // CASE B: bukan 14 digit
                                        // - Jika panjang >= 15 ATAU mengandung huruf → kemungkinan DO → pindahkan ke kolom DO
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

                                        // - Jika hanya angka dan < 14 digit → JANGAN dianggap DO, biarkan user melengkapi 14 digit
                                        Notification::make()
                                            ->title('Lengkapi 14 digit')
                                            ->body('Kode 105 harus tepat 14 digit angka.')
                                            ->warning()
                                            ->send();
                                    }),

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
