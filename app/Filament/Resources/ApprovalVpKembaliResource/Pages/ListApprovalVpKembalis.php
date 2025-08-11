<?php

namespace App\Filament\Resources\ApprovalVpKembaliResource\Pages;

use App\Filament\Resources\ApprovalVpKembaliResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListApprovalVpKembalis extends ListRecords
{
    protected static string $resource = ApprovalVpKembaliResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
