<?php

namespace App\Filament\Resources\TransmittalKembaliGrsDetailResource\Pages;

use App\Filament\Resources\TransmittalKembaliGrsDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransmittalKembaliGrsDetails extends ListRecords
{
    protected static string $resource = TransmittalKembaliGrsDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
