<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApprovalVpKembaliDetailResource\Pages;
use App\Filament\Resources\ApprovalVpKembaliDetailResource\RelationManagers;
use App\Models\ApprovalVpKembaliDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ApprovalVpKembaliDetailResource extends Resource
{
    protected static ?string $model = ApprovalVpKembaliDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
            ->columns([
                Tables\Columns\TextColumn::make('approvalVpKirim.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('approvalVpKembali.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
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
            'index' => Pages\ListApprovalVpKembaliDetails::route('/'),
            'create' => Pages\CreateApprovalVpKembaliDetail::route('/create'),
            'view' => Pages\ViewApprovalVpKembaliDetail::route('/{record}'),
            'edit' => Pages\EditApprovalVpKembaliDetail::route('/{record}/edit'),
        ];
    }
}
