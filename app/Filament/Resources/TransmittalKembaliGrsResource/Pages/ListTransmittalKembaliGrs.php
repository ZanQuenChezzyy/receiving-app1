<?php

namespace App\Filament\Resources\TransmittalKembaliGrsResource\Pages;

use App\Filament\Resources\TransmittalKembaliGrsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransmittalKembaliGrs extends ListRecords
{
    protected static string $resource = TransmittalKembaliGrsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
