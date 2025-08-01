<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class GrsRdtv extends Cluster
{
    protected static ?string $navigationLabel = 'GRS & RDTV';
    protected static ?string $navigationGroup = 'Transmittal';
    protected static ?string $navigationIcon = 'heroicon-o-scale';
    protected static ?string $activeNavigationIcon = 'heroicon-s-scale';
    protected static ?int $navigationSort = 4;
}
