<?php

namespace App\Filament\Resources\GoodsReceiptSlipResource\Pages;

use App\Filament\Resources\GoodsReceiptSlipResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGoodsReceiptSlip extends EditRecord
{
    protected static string $resource = GoodsReceiptSlipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
