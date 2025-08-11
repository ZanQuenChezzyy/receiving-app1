<?php

namespace App\Filament\Resources\ApprovalVpKirimResource\Pages;

use App\Filament\Resources\ApprovalVpKirimResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewApprovalVpKirim extends ViewRecord
{
    protected static string $resource = ApprovalVpKirimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
