<?php

namespace App\Filament\Resources;

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

class TransmittalKembaliDetailResource extends Resource
{
    protected static ?string $model = TransmittalKembaliDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
            ->columns([
                Tables\Columns\TextColumn::make('transmittalKembali.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('transmittalKirim.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('do_receipt_detail_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('code_103')
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
            'index' => Pages\ListTransmittalKembaliDetails::route('/'),
            'create' => Pages\CreateTransmittalKembaliDetail::route('/create'),
            'view' => Pages\ViewTransmittalKembaliDetail::route('/{record}'),
            'edit' => Pages\EditTransmittalKembaliDetail::route('/{record}/edit'),
        ];
    }
}
