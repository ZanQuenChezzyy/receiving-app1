<?php

namespace App\Filament\Resources\TransmittalResource\Pages;

use App\Filament\Resources\TransmittalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransmittal extends EditRecord
{
    protected static string $resource = TransmittalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
