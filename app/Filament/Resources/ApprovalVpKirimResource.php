<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApprovalVpKirimResource\Pages;
use App\Filament\Resources\ApprovalVpKirimResource\RelationManagers;
use App\Models\ApprovalVpKirim;
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
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class ApprovalVpKirimResource extends Resource
{
    protected static ?string $model = ApprovalVpKirim::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
                                    ->live()
                                    ->required()
                                    ->afterStateUpdated(function ($state, callable $set) {
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
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tanggal_kirim')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
