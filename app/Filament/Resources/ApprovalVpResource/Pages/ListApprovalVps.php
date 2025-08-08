<?php

namespace App\Filament\Resources\ApprovalVpResource\Pages;

use App\Filament\Resources\ApprovalVpResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListApprovalVps extends ListRecords
{
    protected static string $resource = ApprovalVpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
