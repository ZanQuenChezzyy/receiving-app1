<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class TransmittalGrs extends Cluster
{
    protected static ?string $label = 'Transmittal ISTEK';
    protected static ?string $navigationGroup = 'Transmittal';
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    protected static ?string $activeNavigationIcon = 'heroicon-s-arrow-path-rounded-square';
    protected static ?int $navigationSort = 4;
}
