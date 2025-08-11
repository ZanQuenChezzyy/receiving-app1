<?php

namespace App\Filament\Resources\ApprovalVpKembaliDetailResource\Pages;

use App\Filament\Resources\ApprovalVpKembaliDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditApprovalVpKembaliDetail extends EditRecord
{
    protected static string $resource = ApprovalVpKembaliDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
