<?php

namespace App\Filament\Resources\TransmittalGudangTerimaDetailResource\Pages;

use App\Filament\Resources\TransmittalGudangTerimaDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTransmittalGudangTerimaDetail extends ViewRecord
{
    protected static string $resource = TransmittalGudangTerimaDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
