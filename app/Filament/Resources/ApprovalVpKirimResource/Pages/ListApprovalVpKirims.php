<?php

namespace App\Filament\Resources\ApprovalVpKirimResource\Pages;

use App\Filament\Resources\ApprovalVpKirimResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListApprovalVpKirims extends ListRecords
{
    protected static string $resource = ApprovalVpKirimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
