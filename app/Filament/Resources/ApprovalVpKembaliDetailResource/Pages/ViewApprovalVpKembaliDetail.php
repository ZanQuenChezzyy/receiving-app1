<?php

namespace App\Filament\Resources\ApprovalVpKembaliDetailResource\Pages;

use App\Filament\Resources\ApprovalVpKembaliDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewApprovalVpKembaliDetail extends ViewRecord
{
    protected static string $resource = ApprovalVpKembaliDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
