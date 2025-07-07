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
                ->importer(PurchaseOrderTerbitImporter::class)
                ->chunkSize(1000),
            Actions\CreateAction::make(),
        ];
    }
}
