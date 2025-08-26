<?php

namespace App\Filament\Resources\TransmittalGudangTerimaDetailResource\Pages;

use App\Filament\Resources\TransmittalGudangTerimaDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransmittalGudangTerimaDetail extends EditRecord
{
    protected static string $resource = TransmittalGudangTerimaDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
