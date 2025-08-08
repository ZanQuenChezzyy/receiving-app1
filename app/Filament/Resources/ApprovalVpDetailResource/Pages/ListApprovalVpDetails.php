<?php

namespace App\Filament\Resources\ApprovalVpDetailResource\Pages;

use App\Filament\Resources\ApprovalVpDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListApprovalVpDetails extends ListRecords
{
    protected static string $resource = ApprovalVpDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
