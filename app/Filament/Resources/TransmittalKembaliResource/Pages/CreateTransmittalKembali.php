<?php

namespace App\Filament\Resources\TransmittalKembaliResource\Pages;

use App\Filament\Resources\TransmittalKembaliResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTransmittalKembali extends CreateRecord
{
    protected static string $resource = TransmittalKembaliResource::class;
    protected static bool $canCreateAnother = false;
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
