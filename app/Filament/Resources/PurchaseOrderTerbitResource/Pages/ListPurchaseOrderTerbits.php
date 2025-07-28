<?php

namespace App\Filament\Resources\PurchaseOrderTerbitResource\Pages;

use App\Filament\Imports\PurchaseOrderTerbitImporter;
use App\Filament\Resources\PurchaseOrderTerbitResource;
use Filament\Actions;
use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseOrderTerbits extends ListRecords
{
    protected static string $resource = PurchaseOrderTerbitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportAction::make()
                ->label('Impor Purchase Order Terbit')
                ->icon('heroicon-m-arrow-down-tray')
                ->importer(PurchaseOrderTerbitImporter::class)
                ->chunkSize(1000),
            Actions\CreateAction::make()
                ->label('Tambah Purchase Order Terbit')
                ->icon('heroicon-m-plus-circle'),
        ];
    }
}
