<?php

namespace App\Filament\Resources\TransmittalGudangTerimaResource\Pages;

use App\Filament\Resources\TransmittalGudangTerimaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransmittalGudangTerimas extends ListRecords
{
    protected static string $resource = TransmittalGudangTerimaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
