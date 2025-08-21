<?php

namespace App\Filament\Resources\MaterialIssuedRequestDetailResource\Pages;

use App\Filament\Resources\MaterialIssuedRequestDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMaterialIssuedRequestDetail extends ViewRecord
{
    protected static string $resource = MaterialIssuedRequestDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
