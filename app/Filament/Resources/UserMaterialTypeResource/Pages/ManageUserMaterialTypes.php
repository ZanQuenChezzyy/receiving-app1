<?php

namespace App\Filament\Resources\UserMaterialTypeResource\Pages;

use App\Filament\Resources\UserMaterialTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageUserMaterialTypes extends ManageRecords
{
    protected static string $resource = UserMaterialTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
