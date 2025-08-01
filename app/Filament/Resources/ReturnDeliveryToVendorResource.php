<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\GrsRdtv;
use App\Filament\Resources\ReturnDeliveryToVendorResource\Pages;
use App\Filament\Resources\ReturnDeliveryToVendorResource\RelationManagers;
use App\Models\ReturnDeliveryToVendor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ReturnDeliveryToVendorResource extends Resource
{
    protected static ?string $model = ReturnDeliveryToVendor::class;
    protected static ?string $cluster = GrsRdtv::class;
    protected static ?string $label = 'Dokumen RDTV';
    protected static ?string $navigationGroup = 'Return Delivery to Vendor (RDTV)';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('tanggal_terbit')
                    ->required(),
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(50),
                Forms\Components\TextInput::make('created_by')
                    ->required()
                    ->numeric(),
                Forms\Components\Textarea::make('keterangan')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_terbit')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
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
            'index' => Pages\ListReturnDeliveryToVendors::route('/'),
            'create' => Pages\CreateReturnDeliveryToVendor::route('/create'),
            'view' => Pages\ViewReturnDeliveryToVendor::route('/{record}'),
            'edit' => Pages\EditReturnDeliveryToVendor::route('/{record}/edit'),
        ];
    }
}
