<?php

namespace App\Filament\Resources\TransmittalKembaliResource\Pages;

use App\Filament\Resources\TransmittalKembaliResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransmittalKembalis extends ListRecords
{
    protected static string $resource = TransmittalKembaliResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
