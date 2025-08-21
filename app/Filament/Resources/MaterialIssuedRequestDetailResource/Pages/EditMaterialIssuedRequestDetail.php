<?php

namespace App\Filament\Resources\MaterialIssuedRequestDetailResource\Pages;

use App\Filament\Resources\MaterialIssuedRequestDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMaterialIssuedRequestDetail extends EditRecord
{
    protected static string $resource = MaterialIssuedRequestDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
