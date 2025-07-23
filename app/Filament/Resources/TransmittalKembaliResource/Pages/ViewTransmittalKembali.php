<?php

namespace App\Filament\Resources\TransmittalKembaliResource\Pages;

use App\Filament\Resources\TransmittalKembaliResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTransmittalKembali extends ViewRecord
{
    protected static string $resource = TransmittalKembaliResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
