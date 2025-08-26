<?php

namespace App\Filament\Resources\TransmittalGudangKirimResource\Pages;

use App\Filament\Resources\TransmittalGudangKirimResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransmittalGudangKirims extends ListRecords
{
    protected static string $resource = TransmittalGudangKirimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
