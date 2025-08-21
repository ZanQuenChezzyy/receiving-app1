<?php

namespace App\Filament\Resources\MaterialIssuedRequestResource\Pages;

use App\Filament\Resources\MaterialIssuedRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMaterialIssuedRequest extends ViewRecord
{
    protected static string $resource = MaterialIssuedRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
