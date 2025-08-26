<?php

namespace App\Filament\Resources\TransmittalGudangTerimaDetailResource\Pages;

use App\Filament\Resources\TransmittalGudangTerimaDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransmittalGudangTerimaDetails extends ListRecords
{
    protected static string $resource = TransmittalGudangTerimaDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
