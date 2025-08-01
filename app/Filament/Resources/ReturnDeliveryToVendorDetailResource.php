<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\GrsRdtv;
use App\Filament\Resources\ReturnDeliveryToVendorDetailResource\Pages;
use App\Filament\Resources\ReturnDeliveryToVendorDetailResource\RelationManagers;
use App\Models\ReturnDeliveryToVendorDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ReturnDeliveryToVendorDetailResource extends Resource
{
    protected static ?string $model = ReturnDeliveryToVendorDetail::class;
    protected static ?string $cluster = GrsRdtv::class;
    protected static ?string $label = 'Detail Dokumen RDTV';
    protected static ?string $navigationGroup = 'Return Delivery to Vendor (RDTV)';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('return_delivery_to_vendor_id')
                    ->relationship('returnDeliveryToVendor', 'id')
                    ->required(),
                Forms\Components\Select::make('transmittal_kembali_detail_id')
                    ->relationship('transmittalKembaliDetail', 'id')
                    ->required(),
                Forms\Components\Select::make('delivery_order_receipt_id')
                    ->relationship('deliveryOrderReceipt', 'id')
                    ->required(),
                Forms\Components\TextInput::make('item_no')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->maxLength(10),
                Forms\Components\TextInput::make('material_code')
                    ->maxLength(20),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('uoi')
                    ->maxLength(5),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('returnDeliveryToVendor.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('transmittalKembaliDetail.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('deliveryOrderReceipt.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('item_no')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->searchable(),
                Tables\Columns\TextColumn::make('material_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('uoi')
                    ->searchable(),
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
            'index' => Pages\ListReturnDeliveryToVendorDetails::route('/'),
            'create' => Pages\CreateReturnDeliveryToVendorDetail::route('/create'),
            'view' => Pages\ViewReturnDeliveryToVendorDetail::route('/{record}'),
            'edit' => Pages\EditReturnDeliveryToVendorDetail::route('/{record}/edit'),
        ];
    }
}
