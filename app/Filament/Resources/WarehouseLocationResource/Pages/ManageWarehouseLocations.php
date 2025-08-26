<?php

namespace App\Filament\Resources\WarehouseLocationResource\Pages;

use App\Filament\Resources\WarehouseLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageWarehouseLocations extends ManageRecords
{
    protected static string $resource = WarehouseLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
