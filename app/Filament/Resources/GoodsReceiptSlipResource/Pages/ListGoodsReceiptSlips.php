<?php

namespace App\Filament\Resources\GoodsReceiptSlipResource\Pages;

use App\Filament\Resources\GoodsReceiptSlipResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGoodsReceiptSlips extends ListRecords
{
    protected static string $resource = GoodsReceiptSlipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
