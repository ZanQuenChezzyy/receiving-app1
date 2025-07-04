<?php

namespace App\Filament\Imports;

use App\Models\PurchaseOrderTerbit;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Carbon;

class PurchaseOrderTerbitImporter extends Importer
{
    protected static ?string $model = PurchaseOrderTerbit::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('purchase_order_item')
                ->rules(['max:20']),
            ImportColumn::make('purchase_order_no')
                ->requiredMapping()
                ->rules(['required', 'max:12']),
            ImportColumn::make('item_no')
                ->requiredMapping()
                ->numeric(),
            ImportColumn::make('material_code')
                ->rules(['max:12']),
            ImportColumn::make('description')
                ->requiredMapping(),
            ImportColumn::make('quantity')
                ->requiredMapping()
                ->numeric(),
            ImportColumn::make('uoi')
                ->requiredMapping()
                ->rules(['required', 'max:5']),
            ImportColumn::make('vendor_id')
                ->requiredMapping()
                ->numeric(),
            ImportColumn::make('vendor_id_name')
                ->requiredMapping()
                ->rules(['max:100']),
            ImportColumn::make('date_created')
                ->requiredMapping()
                ->rules(['date', 'nullable']),
            ImportColumn::make('delivery_date')
                ->requiredMapping()
                ->rules(['date', 'nullable']),
            ImportColumn::make('status')
                ->rules(['max:2']),
            ImportColumn::make('incoterm')
                ->rules(['max:100']),
        ];
    }

    public function resolveRecord(): ?PurchaseOrderTerbit
    {
        // Konversi langsung pada $this->data agar tidak override oleh internal fill()
        if (!empty($this->data['date_created'])) {
            try {
                $this->data['date_created'] = Carbon::createFromFormat('d/m/Y', $this->data['date_created'])->format('Y-m-d');
            } catch (\Exception $e) {
                $this->data['date_created'] = null;
            }
        }

        if (!empty($this->data['delivery_date'])) {
            try {
                $this->data['delivery_date'] = Carbon::createFromFormat('d/m/Y', $this->data['delivery_date'])->format('Y-m-d');
            } catch (\Exception $e) {
                $this->data['delivery_date'] = null;
            }
        }

        // Kembalikan model seperti biasa
        return new PurchaseOrderTerbit([
            'purchase_order_item' => $this->data['purchase_order_item'],
            'purchase_order_no' => $this->data['purchase_order_no'],
            'item_no' => $this->data['item_no'],
            'material_code' => $this->data['material_code'],
            'description' => $this->data['description'],
            'quantity' => $this->data['quantity'],
            'uoi' => $this->data['uoi'],
            'vendor_id' => $this->data['vendor_id'],
            'vendor_id_name' => $this->data['vendor_id_name'],
            'date_created' => $this->data['date_created'],
            'delivery_date' => $this->data['delivery_date'],
            'status' => $this->data['status'] ?? null,
            'incoterm' => $this->data['incoterm'] ?? null,
        ]);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your purchase order terbit import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
