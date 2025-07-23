<?php

namespace App\Filament\Resources\TransmittalKembaliDetailResource\Pages;

use App\Filament\Resources\TransmittalKembaliDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransmittalKembaliDetails extends ListRecords
{
    protected static string $resource = TransmittalKembaliDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
