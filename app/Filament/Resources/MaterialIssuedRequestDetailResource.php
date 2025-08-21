<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\MIR;
use App\Filament\Resources\MaterialIssuedRequestDetailResource\Pages;
use App\Filament\Resources\MaterialIssuedRequestDetailResource\RelationManagers;
use App\Models\MaterialIssuedRequestDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MaterialIssuedRequestDetailResource extends Resource
{
    protected static ?string $model = MaterialIssuedRequestDetail::class;
    protected static ?string $cluster = MIR::class;
    protected static ?string $label = 'Detail Material Issued Request';
    protected static ?string $navigationGroup = 'Material Issued Request (MIR)';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $activeNavigationIcon = 'heroicon-s-clipboard-document-list';
    protected static ?int $navigationSort = 2;
    protected static ?string $slug = 'material-issued-request-details';
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::count();
        return $count < 1 ? 'danger' : 'success';
    }

    protected static ?string $navigationBadgeTooltip = 'Total Detail MIR';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('material_issued_request_id')
                    ->relationship('materialIssuedRequest', 'id')
                    ->required(),
                Forms\Components\Select::make('goods_receipt_slip_detail_id')
                    ->relationship('goodsReceiptSlipDetail', 'id')
                    ->required(),
                Forms\Components\Select::make('location_id')
                    ->relationship('location', 'name')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('item_no')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('stock_no')
                    ->maxLength(50),
                Forms\Components\TextInput::make('requested_qty')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('issued_qty')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('uoi')
                    ->required()
                    ->maxLength(5),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultGroup(
                Group::make('materialIssuedRequest.purchaseOrderTerbit.purchase_order_no')
                    ->label('Nomor PO')
                    ->collapsible()
            )
            ->groups([
                Group::make('materialIssuedRequest.purchaseOrderTerbit.purchase_order_no')
                    ->label('Nomor PO')
                    ->collapsible()
            ])
            ->columns([
                Tables\Columns\TextColumn::make('materialIssuedRequest.purchaseOrderTerbit.purchase_order_no')
                    ->label('No PO & MIR')
                    ->color('primary')
                    ->icon('heroicon-s-document-text')
                    ->description(fn($record) => 'MIR No: ' . $record->materialIssuedRequest->mir_no) // default 'below'
                    ->sortable(),

                Tables\Columns\TextColumn::make('item_no')
                    ->label('Item')
                    ->alignCenter()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('stock_no')
                    ->label('Stock No.')
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-cube'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(20)
                    ->tooltip(fn($record) => $record->description),

                Tables\Columns\TextColumn::make('location.name')
                    ->label('Lokasi')
                    ->icon('heroicon-o-map-pin')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('requested_qty')
                    ->label('Qty Diminta')
                    ->numeric()
                    ->badge()
                    ->alignCenter()
                    ->color(fn($state) => $state > 0 ? 'primary' : 'gray')
                    ->suffix(fn($record) => ' ' . ($record->uoi ?? '')),

                Tables\Columns\TextColumn::make('issued_qty')
                    ->label('Qty Diserahkan')
                    ->numeric()
                    ->badge()
                    ->alignCenter()
                    ->color(fn($state) => $state > 0 ? 'success' : 'danger')
                    ->suffix(fn($record) => ' ' . ($record->uoi ?? '')),

                Tables\Columns\TextColumn::make('uoi')
                    ->label('UoI')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Saat')
                    ->since()
                    ->icon('heroicon-o-clock')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui Saat')
                    ->dateTime('d M Y H:i')
                    ->icon('heroicon-o-pencil-square')
                    ->sortable()
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
            'index' => Pages\ListMaterialIssuedRequestDetails::route('/'),
            // 'create' => Pages\CreateMaterialIssuedRequestDetail::route('/create'),
            // 'view' => Pages\ViewMaterialIssuedRequestDetail::route('/{record}'),
            // 'edit' => Pages\EditMaterialIssuedRequestDetail::route('/{record}/edit'),
        ];
    }
}
