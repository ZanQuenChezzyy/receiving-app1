<?php

namespace App\Filament\Resources\TransmittalKembaliGrsResource\Pages;

use App\Filament\Resources\TransmittalKembaliGrsResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTransmittalKembaliGrs extends ViewRecord
{
    protected static string $resource = TransmittalKembaliGrsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
