<?php

namespace App\Filament\Resources\ApprovalVpKembaliResource\Pages;

use App\Filament\Resources\ApprovalVpKembaliResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewApprovalVpKembali extends ViewRecord
{
    protected static string $resource = ApprovalVpKembaliResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
