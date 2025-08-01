<?php

namespace App\Filament\Resources\GoodsReceiptSlipDetailResource\Pages;

use App\Filament\Resources\GoodsReceiptSlipDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewGoodsReceiptSlipDetail extends ViewRecord
{
    protected static string $resource = GoodsReceiptSlipDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
