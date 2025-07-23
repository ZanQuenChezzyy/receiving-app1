<?php

namespace App\Filament\Resources\TransmittalKirimResource\Pages;

use App\Filament\Resources\TransmittalKirimResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTransmittalKirim extends ViewRecord
{
    protected static string $resource = TransmittalKirimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
