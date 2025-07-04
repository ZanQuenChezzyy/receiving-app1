<?php

namespace App\Filament\Resources\ReceivingLogResource\Pages;

use App\Filament\Resources\ReceivingLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewReceivingLog extends ViewRecord
{
    protected static string $resource = ReceivingLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
