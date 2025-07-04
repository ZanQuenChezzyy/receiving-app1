<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderTerbitResource\Pages;
use App\Filament\Resources\PurchaseOrderTerbitResource\RelationManagers;
use App\Models\PurchaseOrderTerbit;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class PurchaseOrderTerbitResource extends Resource
{
    protected static ?string $model = PurchaseOrderTerbit::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informasi PO')
                ->description('Masukkan informasi umum tentang Purchase Order')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('purchase_order_no')
                            ->label('No. Purchase Order')
                            ->placeholder('Masukkan nomor PO')
                            ->helperText('Contoh: 5000004191')
                            ->required()
                            ->maxLength(12)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $itemNo = $get('item_no');
                                if ($itemNo !== null) {
                                    $set('purchase_order_item', $state . '-' . $itemNo);
                                }
                            }),

                        Select::make('item_no')
                            ->label('Item No.')
                            ->placeholder('Pilih nomor item PO')
                            ->options([
                                10 => '10',
                                20 => '20',
                                30 => '30',
                                40 => '40',
                                50 => '50',
                                60 => '60',
                                70 => '70',
                                80 => '80',
                                90 => '90',
                                100 => '100',
                                110 => '110',
                                120 => '120',
                                130 => '130',
                                140 => '140',
                                150 => '150',
                                160 => '160',
                                170 => '170',
                                180 => '180',
                                190 => '190',
                                200 => '200',
                                210 => '210',
                                220 => '220',
                                230 => '230',
                                240 => '240',
                                250 => '250',
                                260 => '260',
                                270 => '270',
                                280 => '280',
                                290 => '290',
                                300 => '300',
                            ])
                            ->required()
                            ->reactive()
                            ->native(false)
                            ->searchable()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $poNo = $get('purchase_order_no');
                                if ($poNo !== null) {
                                    $set('purchase_order_item', $poNo . '-' . $state);
                                }
                            }),

                        TextInput::make('purchase_order_item')
                            ->label('Purchase Order & Item')
                            ->disabled() // agar tidak bisa diedit manual
                            ->dehydrated()
                            ->placeholder('Otomatis Terisi')
                            ->maxLength(20),

                        TextInput::make('material_code')
                            ->label('Kode Material')
                            ->numeric()
                            ->placeholder('Masukkan kode material'),
                    ]),

                    Textarea::make('description')
                        ->label('Deskripsi Material')
                        ->required()
                        ->columnSpanFull()
                        ->rows(3)
                        ->autosize()
                        ->required()
                        ->placeholder('Tulis nama material atau detail spesifikasi'),

                    Grid::make(2)->schema([
                        TextInput::make('quantity')
                            ->label('Kuantitas')
                            ->placeholder('Masukkan kuantitas')
                            ->helperText('Contoh: 1000')
                            ->required()
                            ->numeric()
                            ->default(0),

                        Select::make('uoi')
                            ->label('Unit of Issue (UOI)')
                            ->placeholder('Pilih UOI')
                            ->options([
                                'AML' => 'AML',
                                'ASY' => 'ASY',
                                'AU' => 'AU',
                                'BAG' => 'BAG',
                                'BAL' => 'BAL',
                                'BDL' => 'BDL',
                                'BOX' => 'BOX',
                                'BT' => 'BT',
                                'BTG' => 'BTG',
                                'CAN' => 'CAN',
                                'CAR' => 'CAR',
                                'CM' => 'CM',
                                'CRD' => 'CRD',
                                'CV' => 'CV',
                                'CYL' => 'CYL',
                                'DR' => 'DR',
                                'DZ' => 'DZ',
                                'EA' => 'EA',
                                'FT' => 'FT',
                                'G' => 'G',
                                'GAL' => 'GAL',
                                'KG' => 'KG',
                                'KIT' => 'KIT',
                                'KL' => 'KL',
                                'L' => 'L',
                                'LBR' => 'LBR',
                                'LGT' => 'LGT',
                                'LIC' => 'LIC',
                                'LMI' => 'LMI',
                                'LNK' => 'LNK',
                                'LOT' => 'LOT',
                                'M' => 'M',
                                'M2' => 'M2',
                                'M3' => 'M3',
                                'ML' => 'ML',
                                'MM' => 'MM',
                                'MM3' => 'MM3',
                                'MON' => 'MON',
                                'NM3' => 'NM3',
                                'PAA' => 'PAA',
                                'PAC' => 'PAC',
                                'PC' => 'PC',
                                'PKT' => 'PKT',
                                'PL' => 'PL',
                                'ROL' => 'ROL',
                                'SAK' => 'SAK',
                                'SET' => 'SET',
                                'SHT' => 'SHT',
                                'STK' => 'STK',
                                'TON' => 'TON',
                                'TUB' => 'TUB',
                                'UN' => 'UN',
                                'VIA' => 'VIA',
                                'YD' => 'YD',
                                'YD3' => 'YD3',
                            ])
                            ->native(false)
                            ->searchable()
                            ->required(),
                    ]),
                ])->columnSpan(1),

            Group::make()
                ->schema([
                    Section::make('Vendor & Pengiriman')
                        ->description('Informasi vendor dan tanggal pengiriman')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('vendor_id')
                                    ->label('ID Vendor')
                                    ->placeholder('Masukkan ID Vendor')
                                    ->required()
                                    ->numeric(),

                                TextInput::make('vendor_id_name')
                                    ->label('ID & Nama Vendor')
                                    ->placeholder('Masukkan ID & Nama vendor')
                                    ->required()
                                    ->maxLength(100),
                            ]),

                            Grid::make(2)->schema([
                                DatePicker::make('date_created')
                                    ->label('Tanggal PO Dibuat')
                                    ->placeholder('Pilih tanggal dibuat')
                                    ->native(false)
                                    ->required()
                                    ->displayFormat('d M Y'),

                                DatePicker::make('delivery_date')
                                    ->label('Tanggal Pengiriman (PO)')
                                    ->placeholder('Pilih tanggal pengiriman')
                                    ->native(false)
                                    ->required()
                                    ->displayFormat('d M Y'),
                            ]),
                        ])->columnSpan(1),

                    Section::make('Lainnya')
                        ->schema([
                            Grid::make(2)->schema([
                                Select::make('status')
                                    ->label('Status')
                                    ->placeholder('Pillih status Purchase Order')
                                    ->options([
                                        'A' => 'A',
                                        'B' => 'B',
                                    ])
                                    ->default('B')
                                    ->required()
                                    ->native(false),

                                TextInput::make('incoterm')
                                    ->label('Incoterm')
                                    ->placeholder('Masukkan Incoterm')
                                    ->maxLength(100)
                                    ->helperText('Contoh: CIF JAKARTA'),
                            ]),
                        ]),

                ]),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->columns([
                TextColumn::make('purchase_order_no')
                    ->label('Purchase Order')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Tidak ada')
                    ->color('primary'),

                TextColumn::make('item_no')
                    ->label('Item')
                    ->sortable()
                    ->placeholder('Tidak ada')
                    ->alignCenter(),

                TextColumn::make('purchase_order_item')
                    ->label('Purchase Order & Item')
                    ->searchable()
                    ->placeholder('Tidak ada')
                    ->color('gray'),

                TextColumn::make('material_code')
                    ->label('Material Code')
                    ->sortable()
                    ->placeholder('Tidak ada')
                    ->color('gray'),

                TextColumn::make('description')
                    ->limit(30)
                    ->wrap()
                    ->placeholder('Tidak ada')
                    ->tooltip(fn($record) => $record->description),

                TextColumn::make('quantity_uoi')
                    ->label('Quantity')
                    ->getStateUsing(fn($record) => $record->quantity . ' ' . $record->uoi)
                    ->sortable()
                    ->badge()
                    ->placeholder('Tidak ada')
                    ->color('info')
                    ->alignRight(),

                TextColumn::make('uoi')
                    ->label('UoI')
                    ->placeholder('Tidak ada')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('vendor_id_name')
                    ->label('Vendor')
                    ->searchable()
                    ->placeholder('Tidak ada')
                    ->tooltip(fn($record) => $record->vendor_id_name)
                    ->limit(30),

                TextColumn::make('date_created')
                    ->label('Tanggal PO')
                    ->date('d M Y')
                    ->placeholder('Tidak ada')
                    ->sortable(),

                TextColumn::make('delivery_date')
                    ->label('Tgl Kirim')
                    ->date('d M Y')
                    ->placeholder('Tidak ada')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'A' => 'success',
                        'B' => 'warning',
                        'C' => 'danger',
                        default => 'gray',
                    })
                    ->placeholder('Tidak ada'),

                TextColumn::make('incoterm')
                    ->label('Incoterm')
                    ->tooltip(fn($record) => $record->incoterm)
                    ->limit(20)
                    ->placeholder('Tidak ada'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('Tidak ada')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Update Terakhir')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('Tidak ada')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                DateRangeFilter::make('date_created')
                    ->label('Tanggal PO Dibuat')
                    ->placeholder('Pilih rentang tanggal'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->slideOver(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
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
            'index' => Pages\ListPurchaseOrderTerbits::route('/'),
            'create' => Pages\CreatePurchaseOrderTerbit::route('/create'),
            'view' => Pages\ViewPurchaseOrderTerbit::route('/{record}'),
            'edit' => Pages\EditPurchaseOrderTerbit::route('/{record}/edit'),
        ];
    }
}
