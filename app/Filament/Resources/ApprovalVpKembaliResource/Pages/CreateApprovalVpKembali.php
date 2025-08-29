<?php

namespace App\Filament\Resources\ApprovalVpKembaliResource\Pages;

use App\Filament\Resources\ApprovalVpKembaliResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateApprovalVpKembali extends CreateRecord
{
    protected static string $resource = ApprovalVpKembaliResource::class;
    protected static bool $canCreateAnother = false;
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
