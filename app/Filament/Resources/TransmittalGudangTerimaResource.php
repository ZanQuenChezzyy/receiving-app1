<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\MIR;
use App\Filament\Resources\TransmittalGudangTerimaResource\Pages;
use App\Filament\Resources\TransmittalGudangTerimaResource\RelationManagers;
use App\Models\GoodsReceiptSlip;
use App\Models\TransmittalGudangKirim;
use App\Models\TransmittalGudangKirimDetail;
use App\Models\TransmittalGudangTerima;
use DesignTheBox\BarcodeField\Forms\Components\BarcodeInput;
use Filament\Actions\StaticAction;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid as ComponentsGrid;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;

class TransmittalGudangTerimaResource extends Resource
{
    protected static ?string $model = TransmittalGudangTerima::class;
    protected static ?string $cluster = MIR::class;
    protected static ?string $label = 'Terima';
    protected static ?string $navigationGroup = 'Transmittal Gudang Terima';
    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static ?string $activeNavigationIcon = 'heroicon-s-arrow-down-tray';
    protected static ?int $navigationSort = 5;
    protected static ?string $slug = 'transmittal-gudang-terima';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::count();
        return $count < 1 ? 'danger' : 'success';
    }

    protected static ?string $navigationBadgeTooltip = 'Total Terima';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Penerimaan')
                    ->description('Scan GRS (105) untuk menarik Transmittal Gudang Kirim & memprefill detail terima.')
                    ->schema([
                        Forms\Components\DatePicker::make('tanggal_terima')
                            ->label('Tanggal Terima')
                            ->placeholder('Pilih Tanggal')
                            ->displayFormat('l, d F Y')
                            ->native(false)
                            ->default(now())
                            ->required(),
                        // Scan GRS 105
                        BarcodeInput::make('scan_code_105')
                            ->label('Scan Nomor GRS (105)')
                            ->placeholder('Scan / ketik nomor GRS (105)')
                            ->dehydrated(false) // tidak disimpan
                            ->live()
                            ->afterStateHydrated(function ($state, callable $set, $record) {
                                if (!$record)
                                    return;

                                $code105 = optional($record->transmittalGudangKirim?->goodsReceiptSlip)->code_105;
                                $set('scan_code_105', $code105 ?? '');
                            })
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (blank($state)) {
                                    $set('transmittal_gudang_kirim_id', null);
                                    $set('transmittal_code', null);
                                    $set('transmittalGudangTerimaDetails', []); // kosongkan repeater relasi
                                    return;
                                }

                                // 1) Cari GRS by code_105
                                $grs = GoodsReceiptSlip::query()
                                    ->where('code_105', $state)
                                    ->first();

                                if (!$grs) {
                                    $set('transmittal_gudang_kirim_id', null);
                                    $set('transmittal_code', null);
                                    $set('transmittalGudangTerimaDetails', []);

                                    Notification::make()
                                        ->title("GRS dengan code_105 [$state] tidak ditemukan")
                                        ->danger()
                                        ->send();

                                    return;
                                }

                                // 2) Ambil Transmittal Gudang Kirim terkait GRS
                                $kirim = TransmittalGudangKirim::query()
                                    ->with(['transmittalGudangKirimDetails'])
                                    ->where('goods_receipt_slip_id', $grs->id)
                                    ->latest('id')
                                    ->first();

                                if (!$kirim) {
                                    $set('transmittal_gudang_kirim_id', null);
                                    $set('transmittal_code', null);
                                    $set('transmittalGudangTerimaDetails', []);

                                    Notification::make()
                                        ->title("Transmittal Gudang Kirim untuk GRS [$state] belum dibuat")
                                        ->warning()
                                        ->send();

                                    return;
                                }

                                // 3) Set FK kirim yang akan disimpan di header
                                $set('transmittal_gudang_kirim_id', $kirim->id);
                                $set('transmittal_code', $kirim->code ?? ("#{$kirim->id}"));

                                // 4) Prefill repeater RELASI detail (akan DISIMPAN)
                                //    - transmittal_gudang_kirim_detail_id: referensi sumber
                                //    - item info: display-only (disabled)
                                //    - quantity_terima: default = quantity kirim (silakan sesuaikan)
                                $details = $kirim->transmittalGudangKirimDetails->map(function ($d) {
                                    return [
                                        'transmittal_gudang_kirim_detail_id' => $d->id,
                                        'item_no' => $d->item_no,
                                        'material_code' => $d->material_code,
                                        'description' => $d->description,
                                        'uoi' => $d->uoi,
                                        'quantity_kirim' => $d->quantity,   // hanya tampilan
                                        'qty_diterima' => $d->quantity,   // default: samakan, bisa diubah user
                                    ];
                                })->toArray();

                                $set('transmittalGudangTerimaDetails', $details);
                            }),

                        // FK header yang disimpan
                        Forms\Components\Hidden::make('transmittal_gudang_kirim_id')
                            ->required(),

                        // Penerima
                        Forms\Components\Select::make('diterima_oleh')
                            ->label('Diterima Oleh')
                            ->relationship('diterimaOleh', 'name') // pastikan relasinya ada
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn() => Auth::id()),

                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan (Opsional)')
                            ->placeholder('Masukkan Catatan (Jika Ada)')
                            ->rows(3)
                            ->autosize()
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Detail Penerimaan (tersimpan)')
                    ->description('Data berikut akan disimpan sebagai detail penerimaan.')
                    ->schema([
                        Forms\Components\Repeater::make('transmittalGudangTerimaDetails') // ganti sesuai nama relasi DI MODEL
                            ->relationship()         // penting: ini repeater relasi -> disimpan otomatis
                            ->label('')
                            ->addable(false)         // diisi dari hasil scan
                            ->deletable(false)
                            ->reorderable(false)
                            ->afterStateHydrated(function (callable $set, ?array $state) {
                                // Kumpulkan semua ID sumber dari state repeater
                                $ids = collect($state ?? [])
                                    ->pluck('transmittal_gudang_kirim_detail_id')
                                    ->filter()
                                    ->all();

                                if (empty($ids)) {
                                    return;
                                }

                                // Ambil semua sumber detail kirim sekaligus (hemat query)
                                $sources = TransmittalGudangKirimDetail::query()
                                    ->whereIn('id', $ids)
                                    ->get()
                                    ->keyBy('id');

                                // Merge kolom tampilan ke setiap baris state
                                $enriched = collect($state)->map(function ($row) use ($sources) {
                                    $src = $sources[$row['transmittal_gudang_kirim_detail_id'] ?? null] ?? null;

                                    if ($src) {
                                        $row['item_no'] = $row['item_no'] ?? $src->item_no;
                                        $row['material_code'] = $row['material_code'] ?? $src->material_code;
                                        $row['description'] = $row['description'] ?? $src->description;
                                        $row['uoi'] = $row['uoi'] ?? $src->uoi;
                                        $row['quantity_kirim'] = $row['quantity_kirim'] ?? $src->quantity;
                                    }

                                    return $row;
                                })->all();

                                // Set ulang state repeater-nya
                                $set('transmittalGudangTerimaDetails', $enriched);
                            })
                            ->schema([
                                // FK referensi ke detail kirim (hidden, disimpan di DB)
                                Forms\Components\Hidden::make('transmittal_gudang_kirim_detail_id'),

                                // Info item (display-only)
                                Forms\Components\TextInput::make('item_no')
                                    ->label('No. Item')
                                    ->placeholder('Otomatis')
                                    ->disabled(),

                                Forms\Components\TextInput::make('material_code')
                                    ->label('Kode Material')
                                    ->placeholder('Otomatis')
                                    ->disabled(),

                                Forms\Components\TextInput::make('description')
                                    ->label('Deskripsi')
                                    ->placeholder('Otomatis')
                                    ->columnSpan(2)
                                    ->disabled(),

                                // Qty kirim (display-only)
                                Forms\Components\TextInput::make('quantity_kirim')
                                    ->label('Qty Kirim')
                                    ->placeholder('Otomatis')
                                    ->suffix(fn($get) => $get('uoi'))
                                    ->disabled(),

                                // Qty terima (DISIMPAN)
                                Forms\Components\TextInput::make('qty_diterima')
                                    ->label('Qty Terima')
                                    ->placeholder('Masukkan Qty')
                                    ->numeric()
                                    ->required()
                                    ->rules(['numeric', 'min:0'])
                                    ->helperText('Pastikan sesuai item fisik yang diterima.')
                                    ->suffix(fn($get) => $get('uoi')),

                                Forms\Components\Hidden::make('uoi'),
                            ])
                            ->columns([
                                'default' => 6,
                                'sm' => 6,
                                'lg' => 6,
                            ])
                            ->minItems(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Cari transmittal / penerima / kode material...')
            ->columns([
                // Transmittal terkait (kode, bukan ID)
                TextColumn::make('transmittalGudangKirim.code')
                    ->label('Code GRS (105)')
                    ->icon('heroicon-m-document-text')
                    ->color('primary')
                    ->searchable()
                    ->sortable()
                    ->description(
                        fn($record) => 'No. PO: ' . optional(
                            $record->transmittalGudangKirim?->goodsReceiptSlip?->purchaseOrderTerbit
                        )?->purchase_order_no,
                        position: 'below'
                    )
                    ->toggleable(),

                // Tanggal terima dengan human diff
                TextColumn::make('tanggal_terima')
                    ->label('Tgl. Terima')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(),

                // Penerima (nama user)
                TextColumn::make('diterimaOleh.name')
                    ->label('Diterima Oleh')
                    ->icon('heroicon-o-user-circle')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                // Dibuat/diupdate
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diupdate')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            // GROUPING
            ->groups([
                Group::make('tanggal_terima')
                    ->label('Tanggal Terima')
                    ->date()
                    ->collapsible(),
            ])
            ->defaultGroup('tanggal_terima')

            // FILTERS
            ->filters([
                // Filter penerima (relasi)
                SelectFilter::make('diterima_oleh')
                    ->label('Diterima Oleh')
                    ->relationship('diterimaOleh', 'name')
                    ->searchable()
                    ->preload(),
            ], layout: Tables\Enums\FiltersLayout::Dropdown)

            // ACTIONS
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->slideOver()
                    ->modalWidth('3xl'),

                Tables\Actions\EditAction::make()
                    ->label('Ubah')
                    ->icon('heroicon-o-pencil-square')
                    ->slideOver(),

                // Lihat item penerimaan (infolist modal, tanpa tombol Kirim/Batal)
                Tables\Actions\Action::make('lihat_items_terima')
                    ->label('Items')
                    ->icon('heroicon-o-list-bullet')
                    ->modalHeading('Daftar Item Diterima')
                    ->modalSubmitAction(false)
                    ->modalCancelAction(fn(StaticAction $action) => $action->label('Tutup'))
                    ->modalWidth('5xl')
                    ->infolist([
                        // Ringkasan
                        ComponentsGrid::make(2)
                            ->schema([
                                TextEntry::make('ringkasan.total_qty')
                                    ->label('Total Item')
                                    ->inlineLabel()
                                    ->state(fn($record) => $record->transmittalGudangTerimaDetails->count())
                                    ->suffix(' Item')
                                    ->badge()
                                    ->color('info')
                                    ->icon('heroicon-o-list-bullet'),

                                TextEntry::make('ringkasan.uom')
                                    ->label('Total Qty Terima')
                                    ->inlineLabel()
                                    ->state(fn($record) => Number::format(
                                        (float) $record->transmittalGudangTerimaDetails->sum('qty_diterima')
                                    ))
                                    ->badge()
                                    ->color('primary')
                                    ->icon('heroicon-o-cube'),
                            ])
                            ->columns(2),

                        // Daftar item (pakai RELASI ke detail kirim)
                        RepeatableEntry::make('transmittalGudangTerimaDetails')
                            ->label('Daftar Item Terima')
                            ->columns(12)
                            ->schema([
                                TextEntry::make('transmittalGudangKirimDetail.item_no')
                                    ->label('No.')
                                    ->prefix('Item ')
                                    ->color('primary')
                                    ->columnSpan(3),

                                TextEntry::make('transmittalGudangKirimDetail.material_code')
                                    ->label('Kode')
                                    ->badge()
                                    ->copyable()
                                    ->copyMessage('Kode material disalin')
                                    ->copyMessageDuration(1200)
                                    ->icon('heroicon-o-qr-code')
                                    ->color('primary')
                                    ->tooltip(fn($state) => $state ?? '')
                                    ->columnSpan(3),

                                TextEntry::make('transmittalGudangKirimDetail.uoi')
                                    ->label('UoI')
                                    ->badge()
                                    ->color('gray')
                                    ->icon('heroicon-o-beaker')
                                    ->columnSpan(3),

                                // qty_diterima itu dari tabel TERIMA (bukan 'quantity')
                                TextEntry::make('qty_diterima')
                                    ->label('Qty Terima')
                                    ->icon('heroicon-o-cube')
                                    ->badge()
                                    ->formatStateUsing(fn($state) => Number::format((float) $state))
                                    ->color(fn($state) => ((float) $state) > 0 ? 'success' : 'danger')
                                    ->extraAttributes(['class' => 'text-right'])
                                    ->columnSpan(3),

                                TextEntry::make('transmittalGudangKirimDetail.description')
                                    ->label('Deskripsi')
                                    ->icon('heroicon-o-document-text')
                                    ->columnSpan(12),
                            ]),
                    ])
                    ->visible(fn($record) => $record?->transmittalGudangTerimaDetails?->count() > 0),
            ])

            // BULK ACTIONS
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])

            // EMPTY STATE
            ->emptyStateHeading('Belum ada penerimaan')
            ->emptyStateDescription('Data akan muncul setelah dokumen gudang diterima.')
            ->emptyStateIcon('heroicon-o-inbox');
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
            'index' => Pages\ListTransmittalGudangTerimas::route('/'),
            'create' => Pages\CreateTransmittalGudangTerima::route('/create'),
            'view' => Pages\ViewTransmittalGudangTerima::route('/{record}'),
            'edit' => Pages\EditTransmittalGudangTerima::route('/{record}/edit'),
        ];
    }
}
