<?php

namespace App\Filament\Resources\TransmittalKirimGrsResource\Pages;

use App\Filament\Resources\TransmittalKirimGrsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransmittalKirimGrs extends EditRecord
{
    protected static string $resource = TransmittalKirimGrsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
