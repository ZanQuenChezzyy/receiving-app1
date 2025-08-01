<?php

namespace App\Filament\Resources\GoodsReceiptSlipDetailResource\Pages;

use App\Filament\Resources\GoodsReceiptSlipDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGoodsReceiptSlipDetail extends EditRecord
{
    protected static string $resource = GoodsReceiptSlipDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
