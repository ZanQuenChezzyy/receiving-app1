<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApprovalVpDetailResource\Pages;
use App\Filament\Resources\ApprovalVpDetailResource\RelationManagers;
use App\Models\ApprovalVpDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ApprovalVpDetailResource extends Resource
{
    protected static ?string $model = ApprovalVpDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('approval_vp_id')
                    ->relationship('approvalVp', 'id')
                    ->required(),
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(50),
                Forms\Components\TextInput::make('document_type')
                    ->required()
                    ->numeric(),
                Forms\Components\Select::make('goods_receipt_slip_id')
                    ->relationship('goodsReceiptSlip', 'id'),
                Forms\Components\Select::make('return_delivery_to_vendor_id')
                    ->relationship('returnDeliveryToVendor', 'id'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('approvalVp.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('document_type')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('goodsReceiptSlip.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('returnDeliveryToVendor.id')
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
            'index' => Pages\ListApprovalVpDetails::route('/'),
            'create' => Pages\CreateApprovalVpDetail::route('/create'),
            'view' => Pages\ViewApprovalVpDetail::route('/{record}'),
            'edit' => Pages\EditApprovalVpDetail::route('/{record}/edit'),
        ];
    }
}
