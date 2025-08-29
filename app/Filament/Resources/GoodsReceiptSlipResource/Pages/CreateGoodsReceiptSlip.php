<?php

namespace App\Filament\Resources\GoodsReceiptSlipResource\Pages;

use App\Filament\Resources\GoodsReceiptSlipResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateGoodsReceiptSlip extends CreateRecord
{
    protected static string $resource = GoodsReceiptSlipResource::class;
    protected static bool $canCreateAnother = false;
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('create', ['focus' => 1]);
    }
}
