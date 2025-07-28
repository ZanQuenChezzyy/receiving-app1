<?php

namespace App\Filament\Resources\TransmittalKembaliGrsDetailResource\Pages;

use App\Filament\Resources\TransmittalKembaliGrsDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransmittalKembaliGrsDetail extends EditRecord
{
    protected static string $resource = TransmittalKembaliGrsDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
