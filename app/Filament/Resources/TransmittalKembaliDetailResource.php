<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\TransmittalIstek;
use App\Filament\Resources\TransmittalKembaliDetailResource\Pages;
use App\Filament\Resources\TransmittalKembaliDetailResource\RelationManagers;
use App\Models\TransmittalKembaliDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;

class TransmittalKembaliDetailResource extends Resource
{
    protected static ?string $model = TransmittalKembaliDetail::class;
    protected static ?string $cluster = TransmittalIstek::class;
    protected static ?string $label = 'Detail Dokumen';
    protected static ?string $navigationGroup = 'Dokumen Transmittal';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $activeNavigationIcon = 'heroicon-s-clipboard-document-check';
    protected static ?int $navigationSort = 3;
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::count() < 2 ? 'danger' : 'info';
    }
    protected static ?string $navigationBadgeTooltip = 'Total Detail Dokumen Istek';
    protected static ?string $slug = 'detail-dokumen-istek';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('transmittal_kembali_id')
                    ->relationship('transmittalKembali', 'id')
                    ->required(),
                Forms\Components\Select::make('transmittal_kirim_id')
                    ->relationship('transmittalKirim', 'id')
                    ->required(),
                Forms\Components\TextInput::make('do_receipt_detail_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(50),
                Forms\Components\TextInput::make('code_103')
                    ->required()
                    ->maxLength(15),
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
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode Dokumen')
                    ->searchable()
                    ->color('primary')
                    ->icon('heroicon-s-document-text')
                    ->description(fn($record) => 'Kode QC: ' . ($record->code_103 ?? '-')),

                Tables\Columns\TextColumn::make('total_item')
                    ->label('Total Item')
                    ->numeric()
                    ->sortable()
                    ->color('info')
                    ->suffix(' Item')
                    ->icon('heroicon-s-cube'),

                Tables\Columns\TextColumn::make('transmittalKirim.tanggal_kirim')
                    ->label('Tanggal Kirim')
                    ->dateTime('l, d F Y')
                    ->sortable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('transmittalKembali.tanggal_kembali')
                    ->label('Tanggal Kembali')
                    ->dateTime('l, d F Y')
                    ->sortable()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('lead_time')
                    ->label('Lead Time (hari)')
                    ->icon('heroicon-s-clock')
                    ->color('warning')
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        $tanggalKirim = optional($record->transmittalKirim)->tanggal_kirim;
                        $tanggalKembali = optional($record->transmittalKembali)->tanggal_kembali;

                        if ($tanggalKirim && $tanggalKembali) {
                            $start = Carbon::parse($tanggalKirim);
                            $end = Carbon::parse($tanggalKembali);

                            return $start->diffInDays($end) . ' hari';
                        }

                        return '-';
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->icon('heroicon-o-calendar-days')
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
                //
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
            'index' => Pages\ListTransmittalKembaliDetails::route('/'),
            'create' => Pages\CreateTransmittalKembaliDetail::route('/create'),
            'view' => Pages\ViewTransmittalKembaliDetail::route('/{record}'),
            'edit' => Pages\EditTransmittalKembaliDetail::route('/{record}/edit'),
        ];
    }
}
