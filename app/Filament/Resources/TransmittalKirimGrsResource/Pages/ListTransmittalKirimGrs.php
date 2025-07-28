<?php

namespace App\Filament\Resources\TransmittalKirimGrsResource\Pages;

use App\Filament\Resources\TransmittalKirimGrsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransmittalKirimGrs extends ListRecords
{
    protected static string $resource = TransmittalKirimGrsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
