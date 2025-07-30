<?php

namespace App\Filament\Resources\TransmittalKirimResource\Pages;

use App\Filament\Resources\TransmittalKirimResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransmittalKirims extends ListRecords
{
    protected static string $resource = TransmittalKirimResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Dokumen Kirim')
                ->icon('heroicon-m-plus-circle'),
        ];
    }
}
