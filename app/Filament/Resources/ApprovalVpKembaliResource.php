<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\GrsRdtv;
use App\Filament\Resources\ApprovalVpKembaliResource\Pages;
use App\Filament\Resources\ApprovalVpKembaliResource\RelationManagers;
use App\Models\ApprovalVpKembali;
use App\Models\ApprovalVpKirim;
use App\Models\GoodsReceiptSlip;
use App\Models\ReturnDeliveryToVendor;
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
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class ApprovalVpKembaliResource extends Resource
{
    protected static ?string $model = ApprovalVpKembali::class;
    protected static ?string $cluster = GrsRdtv::class;
    protected static ?string $label = 'Kembali';
    protected static ?string $navigationGroup = 'Approval VP';
    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-on-square';
    protected static ?string $activeNavigationIcon = 'heroicon-s-arrow-down-on-square';
    protected static ?int $navigationSort = 6;
    protected static ?string $slug = 'approval-vp-kembali';
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::count();
        return $count < 1 ? 'danger' : 'info';
    }
    protected static ?string $navigationBadgeTooltip = 'Total Dokumen Kembali';

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

                Section::make('Daftar Approval VP Kembali')
                    ->description('Scan QR Code Approval VP Kirim untuk mengisi data pengembalian secara otomatis.')
                    ->icon('heroicon-o-list-bullet')
                    ->schema([
                        Repeater::make('approvalVpKembaliDetails')
                            ->relationship()
                            ->label('')
                            ->schema([
                                Grid::make(4)->schema([ // Ubah jadi 6 kolom
                                    TextInput::make('code')
                                        ->label('Scan QR Code')
                                        ->placeholder('Scan QR Dokumen')
                                        ->autofocus()
                                        ->live()
                                        ->required()
                                        ->unique(ignoreRecord: true)
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            if (!$state) {
                                                return;
                                            }

                                            $statusList = [];
                                            $totalItem = 0;
                                            $approvalVpKirimId = null;
                                            $tanggalKirim = null;

                                            // Cari di GRS
                                            $grs = GoodsReceiptSlip::with('goodsReceiptSlipDetails')
                                                ->where('code', $state)
                                                ->first();

                                            if ($grs) {
                                                $statusList[] = '105';
                                                $approvalVpKirim = ApprovalVpKirim::where('code', $state)->first();
                                                $approvalVpKirimId = $approvalVpKirim?->id;
                                                $tanggalKirim = $approvalVpKirim?->tanggal_kirim;
                                                $totalItem += $grs->goodsReceiptSlipDetails->count();
                                            }

                                            // Cari di RDTV
                                            $rdtv = ReturnDeliveryToVendor::with('returnDeliveryToVendorDetails')
                                                ->where('code', $state)
                                                ->first();

                                            if ($rdtv) {
                                                $statusList[] = '124';
                                                $approvalVpKirim = $approvalVpKirim ?? ApprovalVpKirim::where('code', $state)->first();
                                                $approvalVpKirimId = $approvalVpKirim?->id;
                                                $tanggalKirim = $approvalVpKirim?->tanggal_kirim;
                                                $totalItem += $rdtv->returnDeliveryToVendorDetails->count();
                                            }

                                            if (empty($statusList)) {
                                                Notification::make()
                                                    ->title("QR Code tidak ditemukan.")
                                                    ->danger()
                                                    ->send();

                                                $set('status', null);
                                                $set('total_item', null);
                                                $set('approval_vp_kirim_id', null);
                                                $set('tanggal_kirim', null);
                                                return;
                                            }

                                            $status = implode('/', $statusList);

                                            $set('status', $status);
                                            $set('total_item', $totalItem);
                                            $set('approval_vp_kirim_id', $approvalVpKirimId);
                                            $set('tanggal_kirim', $tanggalKirim);

                                            // ✅ Auto tambah 1 row kalau code sudah terisi
                                            $items = $get('../../approvalVpKembaliDetails');
                                            if (count($items) >= 1) {
                                                $items[] = [
                                                    'code' => '',
                                                    'status' => '',
                                                    'total_item' => '',
                                                    'approval_vp_kirim_id' => null,
                                                    'tanggal_kirim' => '',
                                                ];
                                                $set('../../approvalVpKembaliDetails', $items);
                                            }
                                        }),

                                    TextInput::make('status')
                                        ->label('Status')
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
                                        ->disabled()
                                        ->displayFormat('d/m/Y')
                                        ->native(false)
                                        ->dehydrated(true),

                                    Hidden::make('approval_vp_kirim_id')
                                        ->required(),
                                ]),
                            ])
                            ->addActionLabel('Tambah Daftar')
                            ->columnSpanFull()
                            ->defaultItems(2)
                            ->afterStateHydrated(function ($state, callable $set) {
                                if (!empty($state) && is_array($state)) {
                                    foreach ($state as $i => $row) {
                                        if (!empty($row['approval_vp_kirim_id'])) {
                                            $tanggalKirim = ApprovalVpKirim::find($row['approval_vp_kirim_id'])?->tanggal_kirim;
                                            $state[$i]['tanggal_kirim'] = $tanggalKirim;
                                        }
                                    }
                                    // set ulang seluruh state repeater
                                    $set('approvalVpKembaliDetails', $state);
                                }
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->latest()) // Urutkan DESC
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_kembali')
                    ->label('Tanggal Kembali')
                    ->date('l, d F Y')
                    ->sortable()
                    ->color('gray')
                    ->icon('heroicon-m-calendar-days'),

                Tables\Columns\TextColumn::make('approvalVpKembaliDetails.code')
                    ->label('Daftar Dokumen')
                    ->getStateUsing(function ($record) {
                        return $record->approvalVpKembaliDetails
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
            'index' => Pages\ListApprovalVpKembalis::route('/'),
            'create' => Pages\CreateApprovalVpKembali::route('/create'),
            'view' => Pages\ViewApprovalVpKembali::route('/{record}'),
            'edit' => Pages\EditApprovalVpKembali::route('/{record}/edit'),
        ];
    }
}
