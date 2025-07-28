<?php

namespace App\Filament\Resources\TransmittalKembaliGrsResource\Pages;

use App\Filament\Resources\TransmittalKembaliGrsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransmittalKembaliGrs extends EditRecord
{
    protected static string $resource = TransmittalKembaliGrsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
