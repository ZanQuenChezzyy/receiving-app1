<?php

namespace App\Filament\Resources\GoodsReceiptSlipDetailResource\Pages;

use App\Filament\Resources\GoodsReceiptSlipDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGoodsReceiptSlipDetails extends ListRecords
{
    protected static string $resource = GoodsReceiptSlipDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
