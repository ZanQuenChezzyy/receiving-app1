<?php

namespace App\Filament\Resources\TransmittalGudangKirimResource\Pages;

use App\Filament\Resources\TransmittalGudangKirimResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransmittalGudangKirim extends EditRecord
{
    protected static string $resource = TransmittalGudangKirimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
