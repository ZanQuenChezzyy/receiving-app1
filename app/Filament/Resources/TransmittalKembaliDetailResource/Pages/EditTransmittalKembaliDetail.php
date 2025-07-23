<?php

namespace App\Filament\Resources\TransmittalKembaliDetailResource\Pages;

use App\Filament\Resources\TransmittalKembaliDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransmittalKembaliDetail extends EditRecord
{
    protected static string $resource = TransmittalKembaliDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
