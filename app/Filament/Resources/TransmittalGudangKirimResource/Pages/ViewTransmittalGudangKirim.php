<?php

namespace App\Filament\Resources\TransmittalGudangKirimResource\Pages;

use App\Filament\Resources\TransmittalGudangKirimResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTransmittalGudangKirim extends ViewRecord
{
    protected static string $resource = TransmittalGudangKirimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
