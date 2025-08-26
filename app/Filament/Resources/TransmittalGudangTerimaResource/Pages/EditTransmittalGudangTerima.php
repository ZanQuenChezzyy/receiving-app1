<?php

namespace App\Filament\Resources\TransmittalGudangTerimaResource\Pages;

use App\Filament\Resources\TransmittalGudangTerimaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransmittalGudangTerima extends EditRecord
{
    protected static string $resource = TransmittalGudangTerimaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
