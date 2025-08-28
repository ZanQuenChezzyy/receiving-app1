<?php

namespace App\Filament\Resources\TransmittalKirimResource\Pages;

use App\Filament\Resources\TransmittalKirimResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTransmittalKirim extends CreateRecord
{
    protected static string $resource = TransmittalKirimResource::class;
    protected static bool $canCreateAnother = false;
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('create', ['focus' => 1]);
    }
}

