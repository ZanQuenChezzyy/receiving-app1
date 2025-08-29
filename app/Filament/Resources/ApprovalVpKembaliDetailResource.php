<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\GrsRdtv;
use App\Filament\Resources\ApprovalVpKembaliDetailResource\Pages;
use App\Filament\Resources\ApprovalVpKembaliDetailResource\RelationManagers;
use App\Models\ApprovalVpKembaliDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ApprovalVpKembaliDetailResource extends Resource
{
    protected static ?string $model = ApprovalVpKembaliDetail::class;
    protected static ?string $cluster = GrsRdtv::class;
    protected static ?string $label = 'Detail Dokumen';
    protected static ?string $navigationGroup = 'Dokumen Approval';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $activeNavigationIcon = 'heroicon-s-document-text';
    protected static ?int $navigationSort = 7;
    protected static ?string $slug = 'detail-dokumen-approval';
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::count();
        return $count < 1 ? 'danger' : 'info';
    }
    protected static ?string $navigationBadgeTooltip = 'Total Detail Approval';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('approval_vp_kirim_id')
                    ->relationship('approvalVpKirim', 'id')
                    ->required(),
                Forms\Components\Select::make('approval_vp_kembali_id')
                    ->relationship('approvalVpKembali', 'id')
                    ->required(),
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(50),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(6),
                Forms\Components\TextInput::make('total_item')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query->latest(); // urutkan berdasarkan created_at DESC
            })
            ->columns([
                Tables\Columns\TextColumn::make('purchase_order_no')
                    ->label('Nomor PO')
                    ->icon('heroicon-s-document-text')
                    ->color('primary')
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        $po = \App\Models\DeliveryOrderReceipt::where('do_code', $record->code)
                            ->with('purchaseOrderTerbits') // pastikan relasinya benar
                            ->first();

                        return $po->purchaseOrderTerbits->purchase_order_no ?? '-';
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        // kalau statusnya 105/124 → tampilkan gabungan
                        if ($record->status === '105/124') {
                            return '105/124 ';
                        }
                        // selain itu pakai aslinya
                        return $record->status;
                    })
                    ->color(function ($record) {
                        return match ($record->status) {
                            '105' => 'success',
                            '124' => 'danger',
                            '105/124' => 'warning',
                            default => 'gray',
                        };
                    }),


                Tables\Columns\TextColumn::make('total_item')
                    ->label('Total Item')
                    ->numeric()
                    ->sortable()
                    ->color('info')
                    ->suffix(' Item')
                    ->icon('heroicon-s-cube'),

                Tables\Columns\TextColumn::make('approvalVpKirim.tanggal_kirim')
                    ->label('Tanggal Kirim')
                    ->dateTime('l, d F Y')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('approvalVpKembali.tanggal_kembali')
                    ->label('Tanggal Kembali')
                    ->dateTime('l, d F Y')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('lead_time')
                    ->label('Lead Time (hari)')
                    ->icon('heroicon-s-clock')
                    ->color('warning')
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        $start = optional($record->approvalVpKirim)->tanggal_kirim;
                        $end = optional($record->approvalVpKembali)->tanggal_kembali;

                        if (!$start || !$end) {
                            return '-';
                        }

                        $start = \Carbon\Carbon::parse($start);
                        $end = \Carbon\Carbon::parse($end);

                        // Ambil daftar hari libur dari API
                        $response = \Illuminate\Support\Facades\Http::withOptions([
                            'verify' => false, // nonaktifkan verifikasi SSL
                        ])->get('https://api-harilibur.vercel.app/api');

                        $holidays = collect($response->json())->pluck('holiday_date')->toArray();

                        $networkDays = 0;
                        $current = $start->copy();

                        while ($current->lte($end)) {
                            $isWeekend = $current->isWeekend();
                            $isHoliday = in_array($current->format('Y-m-d'), $holidays);

                            if (!$isWeekend && !$isHoliday) {
                                $networkDays++;
                            }

                            $current->addDay();
                        }

                        return "{$networkDays} hari";
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->icon('heroicon-m-calendar-days')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->icon('heroicon-o-arrow-path')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                // ActionGroup::make([
                //     Tables\Actions\ViewAction::make(),
                //     Tables\Actions\EditAction::make(),
                // ])
                //     ->icon('heroicon-o-ellipsis-horizontal-circle')
                //     ->color('info')
                //     ->tooltip('Aksi')
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
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
            'index' => Pages\ListApprovalVpKembaliDetails::route('/'),
            // 'create' => Pages\CreateApprovalVpKembaliDetail::route('/create'),
            // 'view' => Pages\ViewApprovalVpKembaliDetail::route('/{record}'),
            // 'edit' => Pages\EditApprovalVpKembaliDetail::route('/{record}/edit'),
        ];
    }
}
