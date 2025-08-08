<?php

namespace App\Filament\Resources\ApprovalVpResource\Pages;

use App\Filament\Resources\ApprovalVpResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewApprovalVp extends ViewRecord
{
    protected static string $resource = ApprovalVpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
