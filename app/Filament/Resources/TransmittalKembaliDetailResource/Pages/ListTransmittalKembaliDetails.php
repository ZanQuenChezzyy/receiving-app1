<?php

namespace App\Filament\Resources\TransmittalKembaliDetailResource\Pages;

use App\Filament\Exports\TransmittalKembaliDetailExporter;
use App\Filament\Resources\TransmittalKembaliDetailResource;
use Filament\Actions;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class ListTransmittalKembaliDetails extends ListRecords
{
    protected static string $resource = TransmittalKembaliDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make('export-transmittal')
                ->label('Export Transmittal')
                ->exporter(TransmittalKembaliDetailExporter::class)
                ->modifyQueryUsing(function (EloquentBuilder $query) {
                    return $query->with([
                        'transmittalKirim.users',
                        'transmittalKembali.createdBy',
                        'deliveryOrderReceipts.purchaseOrderTerbits', // ⬅️ plural
                    ]);
                })
                ->icon('heroicon-m-document-arrow-up'),
        ];
    }
}
