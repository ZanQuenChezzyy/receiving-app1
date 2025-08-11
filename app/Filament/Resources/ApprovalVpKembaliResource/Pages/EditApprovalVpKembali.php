<?php

namespace App\Filament\Resources\ApprovalVpKembaliResource\Pages;

use App\Filament\Resources\ApprovalVpKembaliResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditApprovalVpKembali extends EditRecord
{
    protected static string $resource = ApprovalVpKembaliResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
