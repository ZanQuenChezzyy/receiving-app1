<?php

namespace App\Filament\Resources\ReturnDeliveryToVendorResource\Pages;

use App\Filament\Resources\ReturnDeliveryToVendorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReturnDeliveryToVendors extends ListRecords
{
    protected static string $resource = ReturnDeliveryToVendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Dokumen RDTV')
                ->icon('heroicon-m-plus-circle'),
        ];
    }
}
