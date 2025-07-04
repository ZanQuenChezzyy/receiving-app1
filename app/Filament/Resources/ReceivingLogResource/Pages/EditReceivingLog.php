<?php

namespace App\Filament\Resources\ReceivingLogResource\Pages;

use App\Filament\Resources\ReceivingLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReceivingLog extends EditRecord
{
    protected static string $resource = ReceivingLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
