<?php

namespace App\Filament\Resources\TransmittalGudangKirimDetailResource\Pages;

use App\Filament\Resources\TransmittalGudangKirimDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransmittalGudangKirimDetails extends ListRecords
{
    protected static string $resource = TransmittalGudangKirimDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
