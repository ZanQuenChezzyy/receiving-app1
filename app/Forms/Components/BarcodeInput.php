<?php

namespace App\Forms\Components;

use Filament\Forms\Components\TextInput;

class BarcodeInput extends TextInput
{
    protected string $view = 'barcode-field::components.barcode-input';

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Barcode Input')
            ->placeholder('Masukkan atau scan barcode di sini')
            ->required();
    }

    public function icon(string $icon): static
    {
        return $this->extraAttributes(['icon' => $icon]);
    }
}
