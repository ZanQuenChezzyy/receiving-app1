<?php

namespace App\Filament\Resources\DeliveryOrderReceiptResource\Pages;

use App\Filament\Resources\DeliveryOrderReceiptResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDeliveryOrderReceipts extends ListRecords
{
    protected static string $resource = DeliveryOrderReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
