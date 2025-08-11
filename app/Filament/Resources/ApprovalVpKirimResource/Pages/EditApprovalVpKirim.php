<?php

namespace App\Filament\Resources\ApprovalVpKirimResource\Pages;

use App\Filament\Resources\ApprovalVpKirimResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditApprovalVpKirim extends EditRecord
{
    protected static string $resource = ApprovalVpKirimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
