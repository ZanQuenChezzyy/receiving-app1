<?php

namespace App\Filament\Resources\DeliveryOrderReceiptDetailResource\Pages;

use App\Filament\Resources\DeliveryOrderReceiptDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageDeliveryOrderReceiptDetails extends ManageRecords
{
    protected static string $resource = DeliveryOrderReceiptDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
