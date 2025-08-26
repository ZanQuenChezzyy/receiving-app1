<?php

namespace App\Filament\Resources\TransmittalGudangKirimDetailResource\Pages;

use App\Filament\Resources\TransmittalGudangKirimDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransmittalGudangKirimDetail extends EditRecord
{
    protected static string $resource = TransmittalGudangKirimDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
