<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\TransmittalGrs;
use App\Filament\Resources\TransmittalKembaliGrsDetailResource\Pages;
use App\Filament\Resources\TransmittalKembaliGrsDetailResource\RelationManagers;
use App\Models\TransmittalKembaliGrsDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransmittalKembaliGrsDetailResource extends Resource
{
    protected static ?string $model = TransmittalKembaliGrsDetail::class;
    protected static ?string $cluster = TransmittalGrs::class;
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

    protected static ?string $navigationBadgeTooltip = 'Total Detail Dokumen GRS';
    protected static ?string $slug = 'detail-dokumen-grs';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('transmittal_kembali_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('transmittal_kirim_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('do_receipt_detail_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(50),
                Forms\Components\TextInput::make('code_105')
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
            ->columns([
                Tables\Columns\TextColumn::make('transmittal_kembali_grs_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('transmittal_kirim_grs_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('do_receipt_detail_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('code_105')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_item')
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
            'index' => Pages\ListTransmittalKembaliGrsDetails::route('/'),
            'create' => Pages\CreateTransmittalKembaliGrsDetail::route('/create'),
            'view' => Pages\ViewTransmittalKembaliGrsDetail::route('/{record}'),
            'edit' => Pages\EditTransmittalKembaliGrsDetail::route('/{record}/edit'),
        ];
    }
}
