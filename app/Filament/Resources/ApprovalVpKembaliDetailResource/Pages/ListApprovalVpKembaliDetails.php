<?php

namespace App\Filament\Resources\ApprovalVpKembaliDetailResource\Pages;

use App\Filament\Resources\ApprovalVpKembaliDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListApprovalVpKembaliDetails extends ListRecords
{
    protected static string $resource = ApprovalVpKembaliDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
