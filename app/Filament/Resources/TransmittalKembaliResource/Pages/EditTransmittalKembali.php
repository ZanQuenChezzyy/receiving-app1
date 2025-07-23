<?php

namespace App\Filament\Resources\TransmittalKembaliResource\Pages;

use App\Filament\Resources\TransmittalKembaliResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransmittalKembali extends EditRecord
{
    protected static string $resource = TransmittalKembaliResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
