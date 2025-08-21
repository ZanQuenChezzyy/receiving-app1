<?php

namespace App\Filament\Resources\MaterialIssuedRequestDetailResource\Pages;

use App\Filament\Resources\MaterialIssuedRequestDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaterialIssuedRequestDetails extends ListRecords
{
    protected static string $resource = MaterialIssuedRequestDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
