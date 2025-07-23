<?php

namespace App\Filament\Resources\TransmittalKembaliDetailResource\Pages;

use App\Filament\Resources\TransmittalKembaliDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTransmittalKembaliDetail extends ViewRecord
{
    protected static string $resource = TransmittalKembaliDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
