<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\TransmittalIstek;
use App\Filament\Resources\TransmittalKirimResource\Pages;
use App\Filament\Resources\TransmittalKirimResource\RelationManagers;
use App\Models\DeliveryOrderReceipt;
use App\Models\TransmittalKirim;
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
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

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
                        Grid::make(3)
                            ->schema([
                                DatePicker::make('tanggal_kirim')
                                    ->label('Tanggal Kirim')
                                    ->displayFormat('l, d F Y')
                                    ->native(false)
                                    ->live()
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->placeholder('Pilih Tanggal Kirim')
                                    ->default(now())
                                    ->required(),
                                TextInput::make('code')
                                    ->label('Kode Dokumen (Scan QR)')
                                    ->placeholder('Contoh: 5000001269086PLJ072514072025')
                                    ->prefixIcon('heroicon-o-qr-code')
                                    ->autofocus()
                                    ->live()
                                    ->required()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $receipt = DeliveryOrderReceipt::with('deliveryOrderReceiptDetails.locations')
                                            ->where('do_code', $state)
                                            ->first();

                                        if ($receipt) {
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
                                        } else {
                                            $set('delivery_order_receipt_id', null);
                                            $set('items', []);
                                        }
                                    }),

                                TextInput::make('code_103')
                                    ->label('Kode 103 (Scan QR)')
                                    ->placeholder('Contoh: 5006550097')
                                    ->prefixIcon('heroicon-o-qr-code')
                                    ->autofocus()
                                    ->required(),

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

                TextColumn::make('users.name')
                    ->label('Dibuat Oleh')
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
