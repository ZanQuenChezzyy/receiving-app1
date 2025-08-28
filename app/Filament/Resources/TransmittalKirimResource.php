<?php

namespace App\Filament\Resources;

use Closure;
use App\Filament\Clusters\TransmittalIstek;
use App\Filament\Resources\TransmittalKirimResource\Pages;
use App\Filament\Resources\TransmittalKirimResource\RelationManagers;
use App\Models\DeliveryOrderReceipt;
use App\Models\TransmittalKirim;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\DB;

class TransmittalKirimResource extends Resource
{
    protected static ?string $model = TransmittalKirim::class;
    protected static ?string $cluster = TransmittalIstek::class;
    protected static ?string $label = 'Kirim';
    protected static ?string $navigationGroup = 'Dokumen Kirim & Kembali';
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-on-square';
    protected static ?string $activeNavigationIcon = 'heroicon-s-arrow-up-on-square';
    protected static ?int $navigationSort = 1;
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::count() < 2 ? 'danger' : 'info';
    }
    protected static ?string $navigationBadgeTooltip = 'Total Transmittal Kirim';
    protected static ?string $slug = 'kirim-istek';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Scan & Informasi DO')
                    ->icon('heroicon-o-qr-code')
                    ->description('Scan kode QR dari Delivery Order untuk menarik data otomatis.')
                    ->schema([
                        Grid::make(7)
                            ->schema([
                                DatePicker::make('tanggal_kirim')
                                    ->label('Tanggal Kirim')
                                    ->displayFormat('l, d F Y')
                                    ->native(false)
                                    ->live()
                                    ->prefixIcon('heroicon-m-calendar-days')
                                    ->placeholder('Pilih Tanggal Kirim')
                                    ->default(now())
                                    ->columnSpan(2)
                                    ->required(),


                                Select::make('qc_destination')
                                    ->label('Tujuan QC')
                                    ->placeholder('Pilih')
                                    ->options([
                                        'ISTEK' => 'ISTEK',
                                        'PPE' => 'PPE',
                                    ])
                                    ->required()
                                    ->native(false)
                                    ->columnSpan(1)
                                    ->afterStateHydrated(function (callable $set, $state, ?TransmittalKirim $record) {
                                        // Kalau edit & sudah ada nilai di DB → biarkan apa adanya
                                        if (filled($state))
                                            return;

                                        // Ambil riwayat terakhir qc_destination milik user ini
                                        $last = TransmittalKirim::query()
                                            ->where('created_by', Auth::id())
                                            ->whereNotNull('qc_destination')
                                            ->latest('id')
                                            ->value('qc_destination');

                                        if ($last) {
                                            $set('qc_destination', $last);
                                        }
                                    }),

                                TextInput::make('code')
                                    ->label('Kode Dokumen (Scan QR)')
                                    ->placeholder('Contoh: 5000001269086PLJ072514072025')
                                    ->prefixIcon('heroicon-o-qr-code')
                                    ->autofocus()
                                    ->live(debounce: 300)
                                    ->minLength(15)
                                    ->unique(ignoreRecord: true)
                                    ->columnSpan(2)
                                    ->required()
                                    // validator form-level tetap dipertahankan
                                    ->rule(fn() => function (string $attribute, $value, Closure $fail) {
                                        $v = trim((string) $value);
                                        if ($v !== '' && preg_match('/^\d{14}$/', $v)) {
                                            $fail('Sepertinya Anda mengisi Code 103 di kolom "Kode Dokumen". Silakan pindahkan ke kolom "Kode 103".');
                                        }
                                    })
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $v = trim((string) $state);

                                        // Kosong → reset dependensi
                                        if ($v === '') {
                                            $set('delivery_order_receipt_id', null);
                                            $set('items', []);
                                            return;
                                        }

                                        // Jika 14 digit → sebenarnya Kode 103, pindahkan ke kolom code_103
                                        if (preg_match('/^\d{14}$/', $v)) {
                                            $set('code', null);
                                            $set('code_103', $v);

                                            Notification::make()
                                                ->title('Dipindahkan ke "Kode 103"')
                                                ->body('Input terdeteksi 14 digit (Kode 103). Kami pindahkan ke kolom "Kode 103".')
                                                ->info()
                                                ->send();

                                            // karena ini bukan Kode Dokumen, jangan query DO
                                            $set('delivery_order_receipt_id', null);
                                            $set('items', []);
                                            return;
                                        }

                                        // Minimal 15 karakter untuk Kode Dokumen (hindari query sia-sia)
                                        if (mb_strlen($v) < 15) {
                                            Notification::make()
                                                ->title('Kode Dokumen terlalu pendek')
                                                ->body('Minimal 15 karakter. Jika 14 digit murni, itu Kode 103 dan harus diisi di kolom "Kode 103".')
                                                ->warning()
                                                ->send();

                                            // Tidak perlu reset "code" agar user bisa lanjut mengetik
                                            $set('delivery_order_receipt_id', null);
                                            $set('items', []);
                                            return;
                                        }

                                        // Lookup DO berdasarkan do_code
                                        $receipt = DeliveryOrderReceipt::with('deliveryOrderReceiptDetails.locations', 'locations')
                                            ->where('do_code', $v)
                                            ->first();

                                        if (!$receipt) {
                                            // Tidak ditemukan → kasih notifikasi dan reset field dependent
                                            Notification::make()
                                                ->title('Kode Dokumen tidak ditemukan')
                                                ->body('Pastikan Anda men-scan QR DO yang benar atau periksa kembali input Anda.')
                                                ->danger()
                                                ->send();

                                            $set('delivery_order_receipt_id', null);
                                            $set('items', []);
                                            return;
                                        }

                                        // Ditemukan → set relasi & items
                                        $set('delivery_order_receipt_id', $receipt->id);

                                        $items = $receipt->deliveryOrderReceiptDetails->map(function ($item) use ($receipt) {
                                            return [
                                                'item_no' => $item->item_no,
                                                'description' => $item->description,
                                                'material_code' => $item->material_code ?? 'None',
                                                'quantity' => $item->quantity,
                                                'uoi' => $item->uoi,
                                                'location' => $item->is_different_location
                                                    ? ($item->locations?->name ?? 'Lokasi Beda (Tidak diketahui)')
                                                    : ($receipt->locations?->name ?? 'Lokasi Utama (Tidak diketahui)'),
                                            ];
                                        })->toArray();

                                        $set('items', $items);
                                    })
                                    ->extraAttributes([
                                        'x-ref' => 'codeInput',
                                        'x-init' => 'if (new URLSearchParams(window.location.search).get("focus")) { $nextTick(() => { ($el.tagName==="INPUT"?$el:$el.querySelector("input"))?.focus() }) }',
                                    ]),

                                TextInput::make('code_103')
                                    ->label('Kode 103 (Scan QR)')
                                    ->placeholder('Contoh: 50065500972025') // 14 digit
                                    ->prefixIcon('heroicon-o-qr-code')
                                    ->autofocus()
                                    ->minLength(14)
                                    ->maxLength(14)
                                    ->columnSpan(2)
                                    ->required()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $v = trim((string) $state);

                                        if ($v === '') {
                                            return;
                                        }

                                        // Validasi format 14 digit
                                        if (!preg_match('/^\d{14}$/', $v)) {
                                            Notification::make()
                                                ->title('Format Kode 103 tidak valid')
                                                ->body('Kode 103 harus 14 digit angka.')
                                                ->warning()
                                                ->send();
                                            return;
                                        }

                                        // Deteksi jika yang discan sebenarnya Kode Dokumen (DO) terpotong jadi 14 char
                                        $looksLikeDoPrefix = DB::table('delivery_order_receipts')
                                            ->where('do_code', 'like', $v . '%')
                                            ->whereRaw('CHAR_LENGTH(do_code) > 14')
                                            ->exists();

                                        if ($looksLikeDoPrefix) {
                                            $set('code_103', null);

                                            Notification::make()
                                                ->title('Yang di-scan adalah Kode Dokumen (terpotong)')
                                                ->body('Silakan scan QR DO pada kolom "Kode Dokumen (Scan QR)".')
                                                ->danger()
                                                ->send();

                                            return;
                                        }

                                        // … lanjutkan logic lain khusus Kode 103 bila ada (opsional)
                                    }),

                                Hidden::make('delivery_order_receipt_id')->required(),
                                Hidden::make('created_by')->default(Auth::user()->id),
                            ]),
                    ]),

                Section::make('Daftar Item dalam Delivery Order')
                    ->icon('heroicon-o-list-bullet')
                    ->description('Item ditarik otomatis setelah scan QR.')
                    ->schema([
                        Repeater::make('items')
                            ->label('')
                            ->schema([
                                TextInput::make('item_no')->label('Item No')->disabled(),
                                TextInput::make('material_code')->label('Material Code')->disabled(),
                                TextInput::make('description')->label('Description')->disabled(),
                                TextInput::make('quantity')->label('Quantity')->disabled(),
                                TextInput::make('uoi')->label('UOI')->disabled(),
                                TextInput::make('location')->label('Lokasi')->disabled(),
                            ])
                            ->columns(6)
                            ->default([])
                            ->columnSpanFull()
                            ->addable(false)
                            ->reorderable(false)
                            ->deletable(false)
                            ->afterStateHydrated(function (callable $set, ?TransmittalKirim $record) {
                                if (!$record || !$record->deliveryOrderReceipts) {
                                    $set('items', []);
                                    return;
                                }

                                $receipt = $record->deliveryOrderReceipts; // alias biar lebih singkat
                    
                                $items = $receipt->deliveryOrderReceiptDetails->map(function ($item) use ($receipt) {
                                    return [
                                        'item_no' => $item->item_no,
                                        'material_code' => $item->material_code ?? 'None',
                                        'description' => $item->description,
                                        'quantity' => $item->quantity,
                                        'uoi' => $item->uoi,
                                        'location' => $item->is_different_location
                                            ? ($item->locations?->name ?? 'Lokasi Beda (Tidak diketahui)')
                                            : ($receipt->locations?->name ?? 'Lokasi Utama (Tidak diketahui)'),
                                    ];
                                })->toArray();

                                $set('items', $items);
                            }),
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
                Group::make('tanggal_kirim')
                    ->label('Tanggal Kirim')
                    ->date()
            ])
            ->defaultGroup(
                Group::make('tanggal_kirim')
                    ->label('tanggal_kirim')
                    ->date()
            )
            ->columns([
                TextColumn::make('tanggal_kirim')
                    ->label('Tanggal Kirim')
                    ->date('l, d F Y')
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('code')
                    ->label('Kode Dokumen')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('code_103')
                    ->label('Kode 103')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deliveryOrderReceipts.purchaseOrderTerbits.purchase_order_no')
                    ->label('Nomor PO & Kode 103')
                    ->searchable()
                    ->icon('heroicon-s-document-text')
                    ->color('primary')
                    ->description(fn($record) => 'Kode 103: ' . ($record->code_103 ?? '-')),

                TextColumn::make('deliveryOrderReceipts.locations.name')
                    ->label('Lokasi')
                    ->badge()
                    ->color('info'),

                TextColumn::make('qc_destination')
                    ->label('Tujuan QC')
                    ->badge()
                    ->icon(fn(?string $state) => match ($state) {
                        'ISTEK' => 'heroicon-o-building-office',
                        'PPE' => 'heroicon-o-building-library',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn(?string $state) => match ($state) {
                        'ISTEK' => 'primary',
                        'PPE' => 'info',
                        default => 'gray',
                    })
                    ->sortable()
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('users.name')
                    ->label('Dibuat Oleh')
                    ->color('warning')
                    ->badge()
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
                SelectFilter::make('qc_destination')
                    ->label('Tujuan QC')
                    ->options([
                        'ISTEK' => 'ISTEK',
                        'PPE' => 'PPE',
                    ])
                    ->placeholder('Semua')
                    ->native(false),
                SelectFilter::make('created_by')
                    ->label('Dibuat Oleh')
                    ->relationship('users', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->default(fn() => Auth::user()?->hasRole('Admin') ? Auth::id() : null),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
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
                Tables\Actions\BulkAction::make('print')
                    ->label('Cetak Transmittal')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->action(function (Collection $records) {
                        $ids = $records->pluck('id')->toArray();
                        $url = route('transmittal-kirim.bulk-print', ['records' => $ids]);

                        return redirect($url);
                    }),
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
            'index' => Pages\ListTransmittalKirims::route('/'),
            'create' => Pages\CreateTransmittalKirim::route('/create'),
            'view' => Pages\ViewTransmittalKirim::route('/{record}'),
            'edit' => Pages\EditTransmittalKirim::route('/{record}/edit'),
        ];
    }
}
