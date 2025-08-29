<?php

namespace App\Filament\Resources\ReturnDeliveryToVendorResource\Pages;

use App\Filament\Resources\ReturnDeliveryToVendorResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateReturnDeliveryToVendor extends CreateRecord
{
    protected static string $resource = ReturnDeliveryToVendorResource::class;
    protected static bool $canCreateAnother = false;
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('create', ['focus' => 1]);
    }
}
