<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class TransmittalIstek extends Cluster
{
    protected static ?string $label = 'Transmittal Istek';
    protected static ?string $navigationGroup = 'Dokumen Transmittal';
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $activeNavigationIcon = 'heroicon-s-arrow-path';
    protected static ?int $navigationSort = 3;
}
