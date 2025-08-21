<?php

namespace App\Filament\Resources\MaterialIssuedRequestResource\Pages;

use App\Filament\Resources\MaterialIssuedRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMaterialIssuedRequest extends EditRecord
{
    protected static string $resource = MaterialIssuedRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
