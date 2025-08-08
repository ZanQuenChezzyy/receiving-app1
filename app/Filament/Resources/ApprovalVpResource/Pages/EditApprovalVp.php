<?php

namespace App\Filament\Resources\ApprovalVpResource\Pages;

use App\Filament\Resources\ApprovalVpResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditApprovalVp extends EditRecord
{
    protected static string $resource = ApprovalVpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
