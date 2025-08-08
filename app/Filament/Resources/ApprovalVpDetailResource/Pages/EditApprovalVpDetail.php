<?php

namespace App\Filament\Resources\ApprovalVpDetailResource\Pages;

use App\Filament\Resources\ApprovalVpDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditApprovalVpDetail extends EditRecord
{
    protected static string $resource = ApprovalVpDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
