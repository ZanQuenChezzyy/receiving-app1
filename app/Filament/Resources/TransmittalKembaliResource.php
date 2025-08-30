<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\TransmittalIstek;
use App\Filament\Resources\TransmittalKembaliResource\Pages;
use App\Filament\Resources\TransmittalKembaliResource\RelationManagers;
use App\Models\TransmittalKembali;
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
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;

class TransmittalKembaliResource extends Resource
{
    protected static ?string $model = TransmittalKembali::class;
    protected static ?string $cluster = TransmittalIstek::class;
    protected static ?string $label = 'Kembali';
    protected static ?string $navigationGroup = 'Dokumen Kirim & Kembali';
    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-on-square';
    protected static ?string $activeNavigationIcon = 'heroicon-s-arrow-down-on-square';
    protected static ?int $navigationSort = 2;
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::count() < 2 ? 'danger' : 'info';
    }
    protected static ?string $navigationBadgeTooltip = 'Total Transmittal Kembali';
    protected static ?string $slug = 'kembali-istek';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Pengembalian')
                    ->description('Isi tanggal pengembalian dokumen dan identitas pembuat entri.')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Grid::make(2)->schema([
                            DatePicker::make('tanggal_kembali')
                                ->label('Tanggal Kembali')
                                ->placeholder('Pilih Tanggal Kembali')
                                ->native(false)
                                ->default(now())
                                ->required(),

                            Select::make('created_by')
                                ->label('Dibuat oleh')
                                ->relationship('createdBy', 'name')
                                ->native(false)
                                ->disabled()
                                ->dehydrated()
                                ->default(Auth::id())
                                ->required(),
                        ]),
                    ]),

                Section::make('Daftar Transmittal Kembali')
                    ->description('Scan QR Code Dokumen untuk mengisi data pengembalian secara otomatis.')
                    ->icon('heroicon-o-list-bullet')
                    ->schema([
                        Repeater::make('transmittalKembaliDetails')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Grid::make(4)->schema([
                                    TextInput::make('code')
                                        ->label('Scan QR Code')
                                        ->placeholder('Scan QR Dokumen')
                                        ->autofocus()
                                        ->live(debounce: 300)
                                        ->required()
                                        ->unique(ignoreRecord: true)
                                        ->rule(fn() => function (string $attribute, $value, Closure $fail) {
                                            $v = trim((string) $value);
                                            if ($v !== '' && preg_match('/^\d{14}$/', $v)) {
                                                $fail('Yang dipindai harus QR Dokumen (bukan Code 103 14 digit). Silakan scan QR Dokumen.');
                                            }
                                        })
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            // Jika 14 digit (kemungkinan Code 103), beri pesan dini & JUGA kosongkan input 'code'
                                            if ($state && preg_match('/^\d{14}$/', $state)) {
                                                Notification::make()
                                                    ->title('Code 103 terdeteksi')
                                                    ->body('Silakan scan QR Dokumen (Bukan 103)')
                                                    ->danger()
                                                    ->send();

                                                // kosongkan input & field terkait agar tidak misleading
                                                $set('code', '');
                                                $set('code_103', null);
                                                $set('total_item', null);
                                                $set('tanggal_kirim', null);
                                                $set('transmittal_kirim_id', null);
                                                $set('delivery_order_receipt_id', null);
                                                return;
                                            }

                                            // --- logika kamu sebelumnya tetap ---
                                            $transmittal = TransmittalKirim::where('code', $state)->first();

                                            if (!$transmittal) {
                                                Notification::make()
                                                    ->title('QR Code tidak ditemukan.')
                                                    ->body('QR Code yang anda scan tidak terdaftar. Mohon periksa kembali!')
                                                    ->danger()
                                                    ->send();

                                                // kosongkan input & field terkait
                                                $set('code', '');
                                                $set('code_103', null);
                                                $set('total_item', null);
                                                $set('tanggal_kirim', null);
                                                $set('transmittal_kirim_id', null);
                                                $set('delivery_order_receipt_id', null);
                                                return;
                                            }

                                            $tanggal = $transmittal->tanggal_kirim;
                                            if (!($tanggal instanceof \Carbon\Carbon)) {
                                                $tanggal = \Illuminate\Support\Carbon::parse($tanggal);
                                            }

                                            $set('code_103', $transmittal->code_103);
                                            $set('tanggal_kirim', $tanggal->format('Y-m-d'));
                                            $set('transmittal_kirim_id', $transmittal->id);
                                            $set('delivery_order_receipt_id', optional($transmittal->deliveryOrderReceipts)->id);

                                            $total = $transmittal->deliveryOrderReceipts?->deliveryOrderReceiptDetails->count() ?? 0;
                                            $set('total_item', $total);

                                            $details = $get('../../transmittalKembaliDetails');
                                            if (count($details) >= 1) {
                                                $details[] = [
                                                    'code' => '',
                                                    'code_103' => '',
                                                    'tanggal_kirim' => '',
                                                    'total_item' => '',
                                                    'transmittal_kirim_id' => null,
                                                    'delivery_order_receipt_id' => null,
                                                ];
                                                $set('../../transmittalKembaliDetails', $details);
                                            }
                                        }),

                                    TextInput::make('code_103')
                                        ->label('Code 103')
                                        ->placeholder('Otomatis')
                                        ->disabled()
                                        ->dehydrated(true),

                                    TextInput::make('total_item')
                                        ->label('Total Item')
                                        ->placeholder('Otomatis')
                                        ->numeric()
                                        ->disabled()
                                        ->suffix('Item')
                                        ->dehydrated(true),
                                    DatePicker::make('tanggal_kirim')
                                        ->label('Tanggal Kirim')
                                        ->placeholder('Otomatis')
                                        ->native(false)
                                        ->disabled()
                                        ->dehydrated(true),
                                ]),

                                Hidden::make('transmittal_kirim_id')
                                    ->required(),

                                Hidden::make('delivery_order_receipt_id')
                                    ->required(),
                            ])
                            ->addActionLabel('Tambah Daftar')
                            ->columnSpanFull()
                            ->defaultItems(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query->latest(); // urutkan berdasarkan created_at DESC
            })
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_kembali')
                    ->label('Tanggal Kembali')
                    ->date('l, d F Y')
                    ->sortable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('transmittalKembaliDetails.code')
                    ->label('Daftar Dokumen')
                    ->getStateUsing(function ($record) {
                        return $record->transmittalKembaliDetails
                            ->map(function ($detail) {
                                $do = \App\Models\DeliveryOrderReceipt::where('do_code', $detail->code)
                                    ->withCount('deliveryOrderReceiptDetails') // langsung hitung total item DO
                                    ->with('purchaseOrderTerbits')
                                    ->first();

                                $poNumber = $do?->purchaseOrderTerbits?->purchase_order_no ?? '-';
                                $totalItem = $do?->delivery_order_receipt_details_count ?? 0;

                                return "{$poNumber} / {$totalItem}";
                            });
                    })
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->limitList(1)
                    ->expandableLimitedList()
                    ->color('primary')
                    ->disabledClick(),

                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Dibuat Oleh')
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-s-user'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-o-clock'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-o-arrow-path'),
            ])
            ->filters([
                Filter::make('cari')
                    ->form([
                        TextInput::make('q')
                            ->label('Pencarian')
                            ->placeholder('Cari Kode Dokumen / Kode 103 / No. PO'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $term = trim($data['q'] ?? '');
                        if ($term === '') {
                            return $query;
                        }

                        return $query->where(function (Builder $q) use ($term) {
                            // code atau code_103 di TransmittalKembaliDetail
                            $q->whereHas('transmittalKembaliDetails', function (Builder $d) use ($term) {
                                $d->where('code', 'like', "%{$term}%")
                                    ->orWhere('code_103', 'like', "%{$term}%");
                            })
                                // purchase_order_no di PurchaseOrderTerbit (via detail -> deliveryOrderReceipts -> purchaseOrderTerbits)
                                ->orWhereHas('transmittalKembaliDetails.deliveryOrderReceipts.purchaseOrderTerbits', function (Builder $p) use ($term) {
                                $p->where('purchase_order_no', 'like', "%{$term}%");
                            });
                        });
                    })
                    ->indicateUsing(fn(array $data) => ($data['q'] ?? null) ? ['Cari: ' . $data['q']] : null),
                SelectFilter::make('created_by')
                    ->label('Dibuat Oleh')
                    ->relationship('createdBy', 'name')   // otomatis where('created_by', <id>)
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
            'index' => Pages\ListTransmittalKembalis::route('/'),
            'create' => Pages\CreateTransmittalKembali::route('/create'),
            'view' => Pages\ViewTransmittalKembali::route('/{record}'),
            'edit' => Pages\EditTransmittalKembali::route('/{record}/edit'),
        ];
    }
}
