<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\TransmittalGrs;
use App\Filament\Resources\TransmittalKirimGrsResource\Pages;
use App\Filament\Resources\TransmittalKirimGrsResource\RelationManagers;
use App\Models\TransmittalKirimGrs;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransmittalKirimGrsResource extends Resource
{
    protected static ?string $model = TransmittalKirimGrs::class;
    protected static ?string $cluster = TransmittalGrs::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(50),
                Forms\Components\TextInput::make('code_105')
                    ->required()
                    ->maxLength(15),
                Forms\Components\Select::make('delivery_order_receipt_id')
                    ->relationship('deliveryOrderReceipt', 'id')
                    ->required(),
                Forms\Components\DatePicker::make('tanggal_kirim')
                    ->required(),
                Forms\Components\TextInput::make('created_by')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('code_105')
                    ->searchable(),
                Tables\Columns\TextColumn::make('deliveryOrderReceipt.id')
                    ->numeric()
                    ->sortable(),
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
            'index' => Pages\ListTransmittalKirimGrs::route('/'),
            'create' => Pages\CreateTransmittalKirimGrs::route('/create'),
            'view' => Pages\ViewTransmittalKirimGrs::route('/{record}'),
            'edit' => Pages\EditTransmittalKirimGrs::route('/{record}/edit'),
        ];
    }
}
