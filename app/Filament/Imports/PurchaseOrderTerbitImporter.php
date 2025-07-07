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
            ImportColumn::make('purchase_order_and_item')
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
            ImportColumn::make('qty_po')
                ->requiredMapping()
                ->numeric(),
            ImportColumn::make('uoi')
                ->requiredMapping()
                ->rules(['required', 'max:5']),
            ImportColumn::make('vendor')
                ->requiredMapping()
                ->numeric(),
            ImportColumn::make('vendor_id_name')
                ->requiredMapping()
                ->rules(['max:100']),
            ImportColumn::make('date_create')
                ->requiredMapping()
                ->rules(['date', 'nullable']),
            ImportColumn::make('delivery_date_po')
                ->requiredMapping()
                ->rules(['date', 'nullable']),
            ImportColumn::make('po_status')
                ->rules(['max:2']),
            ImportColumn::make('incoterm')
                ->rules(['max:100']),
        ];
    }

    public function resolveRecord(): ?PurchaseOrderTerbit
    {
        // Format tanggal
        $this->data['date_create'] = $this->parseDate($this->data['date_create'] ?? null);
        $this->data['delivery_date_po'] = $this->parseDate($this->data['delivery_date_po'] ?? null);

        if (empty($this->data['purchase_order_no']) || empty($this->data['item_no'])) {
            return null; // skip baris tanpa kunci utama
        }

        // Ambil atau buat record berdasarkan kunci unik
        $record = PurchaseOrderTerbit::firstOrNew([
            'purchase_order_no' => $this->data['purchase_order_no'],
            'item_no' => $this->data['item_no'],
        ]);

        // Isi ulang data
        $record->fill([
            'purchase_order_and_item' => $this->data['purchase_order_and_item'],
            'material_code' => $this->data['material_code'],
            'description' => $this->data['description'],
            'qty_po' => $this->data['qty_po'],
            'uoi' => $this->data['uoi'],
            'vendor' => $this->data['vendor'],
            'vendor_id_name' => $this->data['vendor_id_name'],
            'date_create' => $this->data['date_create'],
            'delivery_date_po' => $this->data['delivery_date_po'],
            'po_status' => $this->data['po_status'] ?? null,
            'incoterm' => $this->data['incoterm'] ?? null,
        ]);

        // Skip jika tidak ada perubahan (agar lebih cepat)
        if (!$record->isDirty()) {
            return null;
        }

        return $record;
    }

    protected function parseDate(?string $date): ?string
    {
        try {
            return Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your purchase order terbit import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }

    public function getJobMiddleware(): array
    {
        return []; // kosongkan agar semua job boleh tumpang tindih
    }
}
