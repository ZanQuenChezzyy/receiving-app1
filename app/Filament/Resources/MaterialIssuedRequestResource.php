<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\MIR;
use App\Filament\Resources\MaterialIssuedRequestResource\Pages;
use App\Filament\Resources\MaterialIssuedRequestResource\RelationManagers;
use App\Models\MaterialIssuedRequest;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Grouping\Group as GroupingGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class MaterialIssuedRequestResource extends Resource
{
    protected static ?string $model = MaterialIssuedRequest::class;
    protected static ?string $cluster = MIR::class;
    protected static ?string $label = 'Material Issued Request';
    protected static ?string $navigationGroup = 'Material Issued Request (MIR)';
    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-up';
    protected static ?string $activeNavigationIcon = 'heroicon-s-document-arrow-up';
    protected static ?int $navigationSort = 1;
    protected static ?string $slug = 'material-issued-request';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::count();
        return $count < 1 ? 'danger' : 'success';
    }

    protected static ?string $navigationBadgeTooltip = 'Total MIR';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Utama')
                    ->description('Data utama permintaan material.')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([

                            // Fieldset Kiri
                            Fieldset::make('Informasi MIR')
                                ->schema([
                                    DatePicker::make('tanggal')
                                        ->label('Tanggal')
                                        ->placeholder('Pilih tanggal permintaan')
                                        ->native(false)
                                        ->default(now())
                                        ->required()
                                        ->helperText('Tanggal dibuatnya dokumen MIR.'),

                                    Select::make('created_by')
                                        ->label('Dibuat oleh')
                                        ->relationship('createdBy', 'name')
                                        ->native(false)
                                        ->disabled()
                                        ->dehydrated()
                                        ->default(Auth::id())
                                        ->required()
                                        ->helperText('Secara otomatis diisi dengan user login.'),

                                    Forms\Components\TextInput::make('mir_no')
                                        ->label('Nomor MIR')
                                        ->required()
                                        ->maxLength(50)
                                        ->unique(ignoreRecord: true)
                                        ->disabled()
                                        ->dehydrated()
                                        ->placeholder('Nomor otomatis')
                                        ->helperText('Nomor MIR akan terisi otomatis oleh sistem.')
                                        ->columnSpanFull(),
                                ])->columnSpan(1),

                            // Fieldset Kanan
                            Fieldset::make('Informasi PO & Kebutuhan')
                                ->schema([
                                    Forms\Components\Select::make('purchase_order_terbit_id')
                                        ->relationship('purchaseOrderTerbit', 'purchase_order_no')
                                        ->label('Nomor PO')
                                        ->searchable()
                                        ->required()
                                        ->placeholder('Pilih nomor PO')
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            if ($state) {
                                                // reset repeater dan kasih 1 row default
                                                $set('details', []);

                                                // generate mir_no otomatis
                                                $year = date('Y');
                                                $last = MaterialIssuedRequest::whereYear('created_at', $year)->count() + 1;
                                                $mirNo = 'REC/' . $year . '/' . str_pad($last, 4, '0', STR_PAD_LEFT);

                                                $set('mir_no', $mirNo);
                                            } else {
                                                $set('details', []);
                                                $set('mir_no', null);
                                            }
                                        })
                                        ->helperText('Pilih Purchase Order yang terkait dengan MIR ini.'),

                                    Forms\Components\TextInput::make('department')
                                        ->required()
                                        ->maxLength(100)
                                        ->label('Departemen')
                                        ->placeholder('Masukkan nama departemen')
                                        ->helperText('Departemen yang mengajukan permintaan material.'),

                                    Forms\Components\TextInput::make('used_for')
                                        ->required()
                                        ->maxLength(100)
                                        ->label('Digunakan Untuk')
                                        ->placeholder('Contoh: Maintenance, Operasional, dsb.')
                                        ->helperText('Keterangan penggunaan material.')
                                        ->columnSpanFull(),
                                ])->columnSpan(1),
                        ]),

                        Forms\Components\Group::make()->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('requested_by')
                                    ->label('Diminta Oleh')
                                    ->placeholder('Nama peminta material')
                                    ->required()
                                    ->helperText('Orang yang meminta material ini.'),

                                Forms\Components\Select::make('handed_over_by')
                                    ->label('Diserahkan Oleh')
                                    ->relationship('handedOverBy', 'name')
                                    ->placeholder('Pilih petugas yang menyerahkan')
                                    ->searchable()
                                    ->required()
                                    ->preload()
                                    ->default(Auth::user()->id)
                                    ->helperText('Petugas gudang atau pihak yang menyerahkan material.'),
                            ]),

                            Forms\Components\Grid::make(4)->schema([
                                Forms\Components\TextInput::make('cost_center')
                                    ->maxLength(50)
                                    ->label('Cost Center')
                                    ->placeholder('Kode cost center')
                                    ->helperText('Opsional: masukkan kode cost center jika ada.'),

                                Forms\Components\TextInput::make('jor_no')
                                    ->maxLength(50)
                                    ->label('No. JOR / WO')
                                    ->placeholder('Nomor JOR / Work Order')
                                    ->helperText('Jika terkait JOR/WO, masukkan nomornya.'),

                                Forms\Components\TextInput::make('equipment_no')
                                    ->maxLength(50)
                                    ->label('No. Equipment')
                                    ->placeholder('Nomor peralatan terkait')
                                    ->helperText('Jika terkait equipment, masukkan nomornya.'),

                                Forms\Components\TextInput::make('reservation_no')
                                    ->maxLength(50)
                                    ->label('No. Reservasi')
                                    ->placeholder('Nomor reservasi (opsional)')
                                    ->helperText('Jika ada nomor reservasi material, isi di sini.'),
                            ]),

                            Forms\Components\Textarea::make('keterangan')
                                ->columnSpanFull()
                                ->label('Catatan Tambahan')
                                ->placeholder('Tambahkan catatan tambahan bila diperlukan...')
                                ->helperText('Opsional: catatan tambahan terkait permintaan.')
                                ->autosize()
                                ->rows(3),
                        ]),
                    ]),

                Forms\Components\Section::make('Detail Item')
                    ->description('Daftar item material yang diminta.')
                    ->schema([
                        Forms\Components\Repeater::make('details')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Forms\Components\Grid::make(7)->schema([ // atur jumlah kolom sesuai kebutuhan
                                    Forms\Components\Hidden::make('goods_receipt_slip_detail_id'),
                                    Forms\Components\Select::make('delivery_order_receipt_detail_id')
                                        ->label('No Item')
                                        ->relationship(
                                            name: 'deliveryOrderReceiptDetail',
                                            titleAttribute: 'item_no',
                                            modifyQueryUsing: function ($query, callable $get, $record) {
                                                $poId = $get('../../purchase_order_terbit_id');

                                                if ($poId) {
                                                    $query->whereHas('deliveryOrderReceipts', function ($q) use ($poId) {
                                                        $q->where('purchase_order_terbit_id', $poId);
                                                    });
                                                }

                                                // Tambahkan pengecualian untuk item yang sudah dipakai
                                                $selectedId = $record?->delivery_order_receipt_detail_id;

                                                $query->where(function ($q) use ($poId, $selectedId) {
                                                    $q->whereExists(function ($sub) use ($poId) {
                                                        $sub->selectRaw('1')
                                                            ->from('delivery_order_receipt_details as dord')
                                                            ->whereColumn('dord.id', 'delivery_order_receipt_details.id')
                                                            ->whereRaw("
                                                                (dord.quantity - (
                                                                    SELECT COALESCE(SUM(mird.issued_qty), 0)
                                                                    FROM material_issued_request_details mird
                                                                    JOIN material_issued_requests mir ON mir.id = mird.material_issued_request_id
                                                                    WHERE mir.purchase_order_terbit_id = ?
                                                                    AND mird.item_no = dord.item_no
                                                                )) > 0
                                                            ", [$poId]);
                                                    });
                                                    if ($selectedId) {
                                                        // tetap tampilkan item yang sudah dipilih
                                                        $q->orWhere('delivery_order_receipt_details.id', $selectedId);
                                                    }
                                                });
                                            }
                                        )
                                        ->placeholder('Pilih')
                                        ->searchable()
                                        ->required()
                                        ->preload()
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set, callable $get, $record) {
                                            if (!$state) {
                                                $set('stock_no', null);
                                                $set('description', null);
                                                $set('requested_qty', null);
                                                $set('uoi', null);
                                                $set('location_id', null);
                                                return;
                                            }

                                            $detail = \App\Models\DeliveryOrderReceiptDetail::find($state);
                                            if ($detail) {
                                                $poId = $get('../../purchase_order_terbit_id');

                                                // Hitung qty_po, qty_issued, sisa
                                                [$qtyPo, $qtyIssued, $sisa] = \App\Models\MaterialIssuedRequestDetail::getQtyPoAndIssued(
                                                    $poId,
                                                    $detail->item_no,
                                                    $record?->id
                                                );

                                                $set('stock_no', $detail->material_code ?? '-');
                                                $set('description', $detail->description);
                                                $set('requested_qty', $sisa); // otomatis isi dengan sisa
                                                $set('uoi', $detail->uoi);
                                                $set('item_no', $detail->item_no);
                                                $set('location_id', $detail->deliveryOrderReceipts->location_id);
                                            }
                                        }),

                                    Forms\Components\TextInput::make('stock_no')
                                        ->maxLength(50)
                                        ->label('Stock No')
                                        ->placeholder('Masukkan nomor stok')
                                        ->disabled()
                                        ->dehydrated()
                                        ->columnSpan(1),

                                    Forms\Components\TextInput::make('description')
                                        ->required()
                                        ->label('Deskripsi')
                                        ->maxLength(100)
                                        ->placeholder('Masukkan deskripsi material')
                                        ->disabled()
                                        ->dehydrated()
                                        ->columnSpan(2),

                                    Forms\Components\TextInput::make('requested_qty')
                                        ->numeric()
                                        ->label('Qty Diminta')
                                        ->placeholder('Qty')
                                        ->suffix(fn(Get $get) => $get('uoi') ?? '')
                                        ->minValue(0)
                                        ->reactive()
                                        ->rules([
                                            fn(Get $get, $record): \Closure =>
                                            function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                                $poId = $get('../../purchase_order_terbit_id');
                                                $itemNo = $get('item_no');
                                                $excludeId = $record?->id;

                                                [$qtyPo, $qtyIssued, $sisa] = \App\Models\MaterialIssuedRequestDetail::getQtyPoAndIssued(
                                                    $poId,
                                                    $itemNo,
                                                    $excludeId
                                                );

                                                if ($value > $sisa) {
                                                    $fail("Qty Diminta tidak boleh melebihi sisa qty dari PO. Maksimum sisa: {$sisa}");
                                                }
                                            }
                                        ])
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            $requested = $get('requested_qty') ?? 0;

                                            // kalau user isi negatif → reset jadi 0
                                            if ($state < 0) {
                                                $set('requested_qty', 0);
                                                $set('issued_qty', 0);
                                            }

                                            // kalau issued_qty lebih besar dari requested_qty → reset jadi batas maksimal
                                            $issued = $get('issued_qty') ?? 0;
                                            if ($issued > $requested) {
                                                $set('issued_qty', $requested);
                                            }
                                        })
                                        ->helperText(function (callable $get, $record) {
                                            $poId = $get('../../purchase_order_terbit_id');
                                            $itemNo = $get('item_no');
                                            $excludeId = $record?->id;
                                            $uoi = $get('uoi') ?? '';

                                            [$qtyPo, $qtyIssued, $sisa] = \App\Models\MaterialIssuedRequestDetail::getQtyPoAndIssued(
                                                $poId,
                                                $itemNo,
                                                $excludeId
                                            );

                                            return new \Illuminate\Support\HtmlString("
                                                <span style='color:#6b7280;font-weight:500;'>PO: {$qtyPo} {$uoi}</span><br>
                                                <span style='color:#16a34a;font-weight:500;'>Sudah Issue: {$qtyIssued} {$uoi}</span><br>
                                                <span style='color:#dc2626;font-weight:500;'>Sisa: {$sisa} {$uoi}</span>
                                            ");
                                        }),

                                    Forms\Components\TextInput::make('issued_qty')
                                        ->placeholder('Qty')
                                        ->numeric()
                                        ->required()
                                        ->label('Qty Diserahkan')
                                        ->minValue(0) // sudah cegah < 0
                                        ->suffix(fn(Get $get) => $get('uoi') ?? '')
                                        ->reactive()
                                        ->rules([
                                            fn(Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                                $requested = $get('requested_qty') ?? 0;
                                                $uoi = $get('uoi') ?? '';

                                                if ($value < 0) {
                                                    $fail("Qty Diserahkan tidak boleh kurang dari 0.");
                                                }

                                                if ($value > $requested) {
                                                    $fail("Qty Diserahkan tidak boleh lebih dari Qty Diminta ({$requested}).");
                                                }
                                            },
                                        ])
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            $requested = $get('requested_qty') ?? 0;

                                            // kalau user isi negatif → reset jadi 0
                                            if ($state < 0) {
                                                $set('issued_qty', 0);
                                            }

                                            // kalau lebih besar → reset jadi batas maksimal
                                            if ($state > $requested) {
                                                $set('issued_qty', $requested);
                                            }
                                        })
                                        ->helperText(fn($get) => "Tidak boleh lebih dari: {$get('requested_qty')}"),

                                    Forms\Components\Select::make('location_id')
                                        ->relationship('location', 'name')
                                        ->label('Lokasi')
                                        ->placeholder('Pilih lokasi')
                                        ->disabled()
                                        ->dehydrated()
                                        ->searchable()
                                        ->columnSpan(1),

                                    Forms\Components\Hidden::make('uoi')->required(),
                                    Forms\Components\Hidden::make('item_no')->required(),
                                ]),
                            ])
                            ->defaultItems(0)
                            ->columns(1)
                            ->collapsible()
                            ->addActionLabel('Tambah Item')
                            ->reorderable()
                            ->disabled(fn(Get $get) => empty($get('purchase_order_terbit_id'))),
                    ]),

                Forms\Components\Section::make('Lampiran')
                    ->description('Unggah lampiran terkait MIR ini.')
                    ->schema([
                        Forms\Components\Repeater::make('lampirans')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Forms\Components\FileUpload::make('file_path')
                                    ->label('Lampiran')
                                    ->helperText('Unggah gambar atau dokumen terkait MIR ini.')
                                    ->image() // kalau khusus gambar
                                    ->imageEditor() // bisa edit gambar
                                    ->maxSize(2048) // max 2MB
                                    ->downloadable()
                                    ->imageEditorAspectRatios([
                                        null,
                                        '9:16',
                                        '16:9',
                                        '4:3',
                                        '1:1',
                                    ])
                                    ->imageCropAspectRatio('1:1')
                                    ->directory('lampiran_mir')
                                    ->visibility('public')
                                    ->imagePreviewHeight('250')
                                    ->panelAspectRatio('2:1')
                                    ->panelLayout('integrated')
                                    ->openable()
                                    ->required()
                                    ->previewable(),
                            ])
                            ->addActionAlignment(Alignment::End)
                            ->addActionLabel('Tambah Lampiran')
                            ->grid(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultGroup(
                GroupingGroup::make('tanggal')
                    ->date()
                    ->collapsible()
            )
            ->groups([
                GroupingGroup::make('tanggal')
                    ->date()
                    ->collapsible()
            ])
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Hari, Tanggal')
                    ->date('l, d F Y')
                    ->sortable()
                    ->icon('heroicon-m-calendar')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('mir_no')
                    ->label('Nomor PO & MIR')
                    ->icon('heroicon-s-document-text')
                    ->description(fn($record) => 'PO No: ' . $record->purchaseOrderTerbit->purchase_order_no) // default 'below'
                    ->color('primary'),

                Tables\Columns\TextColumn::make('department')
                    ->label('Departemen')
                    ->icon('heroicon-m-building-office')
                    ->color('info')
                    ->badge(),

                Tables\Columns\TextColumn::make('used_for')
                    ->searchable()
                    ->label('Dipakai')
                    ->wrap(),

                Tables\Columns\TextColumn::make('requested_by')
                    ->label('Diminta Oleh')
                    ->searchable()
                    ->icon('heroicon-o-user')
                    ->badge()
                    ->alignRight()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('handedOverBy.name')
                    ->label('Diserahkan Oleh')
                    ->alignRight()
                    ->icon('heroicon-o-user')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('cost_center')
                    ->label('Pusat Biaya')
                    ->badge()
                    ->color('purple')
                    ->searchable()
                    ->placeholder('Tidak ada')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('jor_no')
                    ->label('No Jor')
                    ->searchable()
                    ->color('danger')
                    ->placeholder('Tidak Ada')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('equipment_no')
                    ->label('No Alat')
                    ->searchable()
                    ->placeholder('Tidak Ada')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('reservation_no')
                    ->label('No Reservasi')
                    ->searchable()
                    ->icon('heroicon-o-archive-box')
                    ->placeholder('Tidak Ada')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->numeric()
                    ->sortable()
                    ->icon('heroicon-o-user-circle')
                    ->color('warning')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color('info'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color('secondary'),
            ])
            ->filters([
                //
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
            'index' => Pages\ListMaterialIssuedRequests::route('/'),
            'create' => Pages\CreateMaterialIssuedRequest::route('/create'),
            'view' => Pages\ViewMaterialIssuedRequest::route('/{record}'),
            'edit' => Pages\EditMaterialIssuedRequest::route('/{record}/edit'),
        ];
    }
}
