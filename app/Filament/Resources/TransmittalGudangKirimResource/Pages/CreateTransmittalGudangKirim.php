<?php

namespace App\Filament\Resources\TransmittalGudangKirimResource\Pages;

use App\Filament\Resources\TransmittalGudangKirimResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTransmittalGudangKirim extends CreateRecord
{
    protected static string $resource = TransmittalGudangKirimResource::class;
    protected static bool $canCreateAnother = false;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('create');
    }
}
