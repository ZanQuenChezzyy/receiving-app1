<?php

namespace App\Filament\Resources\StatusHistoryResource\Pages;

use App\Filament\Resources\StatusHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageStatusHistories extends ManageRecords
{
    protected static string $resource = StatusHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
