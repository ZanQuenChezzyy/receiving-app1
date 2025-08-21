<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class MIR extends Cluster
{
    protected static ?string $navigationLabel = 'MIR & Gudang';
    protected static ?string $navigationGroup = 'Transmittal';
    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $activeNavigationIcon = 'heroicon-s-arrows-right-left';
    protected static ?int $navigationSort = 5;
}
