<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\TransmittalGrs;
use App\Filament\Resources\TransmittalKirimGrsResource\Pages;
use App\Filament\Resources\TransmittalKirimGrsResource\RelationManagers;
use App\Models\TransmittalKirimGrs;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class TransmittalKirimGrsResource extends Resource
{
    protected static ?string $model = TransmittalKirimGrs::class;
    protected static ?string $cluster = TransmittalGrs::class;
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

    protected static ?string $navigationBadgeTooltip = 'Total Transmittal Kirim GRS';
    protected static ?string $slug = 'kirim-grs';

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
                                    ->rules([
                                        fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) {
                                            if (!\App\Models\TransmittalKembaliDetail::where('code', $value)->exists()) {
                                                $fail("Dokumen dengan kode tersebut belum kembali dari Istek.");
                                            }
                                        },
                                    ])
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        // Cek apakah kode sudah ada di Transmittal Kembali
                                        $isExists = \App\Models\TransmittalKembali::whereHas('transmittalKembaliDetails', function ($query) use ($state) {
                                            $query->where('code', $state);
                                        })->exists();

                                        if (!$isExists) {
                                            // Reset field dan tampilkan error
                                            $set('delivery_order_receipt_id', null);
                                            $set('items', []);

                                            // Gunakan validationException agar muncul error di field
                                            throw \Illuminate\Validation\ValidationException::withMessages([
                                                'code' => 'Kode ini belum tercatat dalam Transmittal Kembali!',
                                            ]);
                                        }

                                        // Ambil data DO jika valid
                                        $receipt = \App\Models\DeliveryOrderReceipt::with('deliveryOrderReceiptDetails.locations')
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

                                TextInput::make('code_105')
                                    ->label('Kode 105 (Scan QR)')
                                    ->placeholder('Contoh: 5006550098')
                                    ->prefixIcon('heroicon-o-qr-code')
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
                            ->afterStateHydrated(function (callable $set, ?TransmittalKirimGrs $record) {
                                if (!$record || !$record->deliveryOrderReceipt) {
                                    $set('items', []);
                                    return;
                                }

                                $receipt = $record->deliveryOrderReceipt;

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
            ->modifyQueryUsing(fn(Builder $query) => $query->latest())
            ->groups([
                Group::make('tanggal_kirim')
                    ->label('Tanggal Kirim')
                    ->date(),
            ])
            ->defaultGroup(
                Group::make('tanggal_kirim')
                    ->label('Tanggal Kirim')
                    ->date(),
            )
            ->columns([
                TextColumn::make('tanggal_kirim')
                    ->label('Tanggal Kirim')
                    ->date('l, d F Y')
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('deliveryOrderReceipt.purchaseOrderTerbits.purchase_order_no')
                    ->label('Nomor PO & Kode 105')
                    ->searchable()
                    ->icon('heroicon-s-document-text')
                    ->color('primary')
                    ->description(fn($record) => 'Kode 105: ' . ($record->code_105 ?? '-')),

                TextColumn::make('deliveryOrderReceipt.locations.name')
                    ->label('Lokasi')
                    ->badge()
                    ->color('info'),

                TextColumn::make('createdBy.name')
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
                // Tambahkan filter jika diperlukan
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                ])
                    ->icon('heroicon-o-ellipsis-horizontal-circle')
                    ->color('info')
                    ->tooltip('Aksi'),
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
                        $url = route('transmittal-kirim-grs.bulk-print', ['records' => $ids]);

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
            'index' => Pages\ListTransmittalKirimGrs::route('/'),
            'create' => Pages\CreateTransmittalKirimGrs::route('/create'),
            'view' => Pages\ViewTransmittalKirimGrs::route('/{record}'),
            'edit' => Pages\EditTransmittalKirimGrs::route('/{record}/edit'),
        ];
    }
}
