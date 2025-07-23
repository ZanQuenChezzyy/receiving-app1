<?php

namespace App\Filament\Resources\TransmittalKirimResource\Pages;

use App\Filament\Resources\TransmittalKirimResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransmittalKirim extends EditRecord
{
    protected static string $resource = TransmittalKirimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
