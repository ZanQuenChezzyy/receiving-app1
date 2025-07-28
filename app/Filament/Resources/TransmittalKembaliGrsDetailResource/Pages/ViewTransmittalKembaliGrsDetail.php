<?php

namespace App\Filament\Resources\TransmittalKembaliGrsDetailResource\Pages;

use App\Filament\Resources\TransmittalKembaliGrsDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTransmittalKembaliGrsDetail extends ViewRecord
{
    protected static string $resource = TransmittalKembaliGrsDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
