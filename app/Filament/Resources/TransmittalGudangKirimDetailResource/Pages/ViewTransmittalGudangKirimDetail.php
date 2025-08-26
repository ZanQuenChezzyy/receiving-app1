<?php

namespace App\Filament\Resources\TransmittalGudangKirimDetailResource\Pages;

use App\Filament\Resources\TransmittalGudangKirimDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTransmittalGudangKirimDetail extends ViewRecord
{
    protected static string $resource = TransmittalGudangKirimDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
