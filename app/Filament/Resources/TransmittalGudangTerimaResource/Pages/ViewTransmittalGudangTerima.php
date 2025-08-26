<?php

namespace App\Filament\Resources\TransmittalGudangTerimaResource\Pages;

use App\Filament\Resources\TransmittalGudangTerimaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTransmittalGudangTerima extends ViewRecord
{
    protected static string $resource = TransmittalGudangTerimaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
