<?php

namespace App\Filament\Exports;

use App\Models\TransmittalKembaliDetail;
use Carbon\CarbonInterface;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TransmittalKembaliDetailExporter extends Exporter
{
    protected static ?string $model = TransmittalKembaliDetail::class;

    /** cache in-memory per proses export */
    protected static ?array $cachedHolidays = null;

    /** Ambil daftar libur nasional (cache per tahun) */
    protected static function getHolidays(): array
    {
        if (static::$cachedHolidays !== null) {
            return static::$cachedHolidays;
        }

        $year = now()->year;
        $key = "holidays:id:$year";

        static::$cachedHolidays = Cache::remember(
            $key,
            now()->endOfYear()->diffInSeconds(),
            function () use ($year) {
                try {
                    // Kalau API support query tahun: gunakan ?year=...
                    $res = Http::withOptions(['verify' => false])
                        ->timeout(4)
                        ->get('https://api-harilibur.vercel.app/api');

                    $dates = collect($res->json())
                        ->pluck('holiday_date')
                        ->filter(fn($d) => is_string($d) && str_starts_with($d, (string) $year)) // filter hanya tahun berjalan
                        ->values()
                        ->toArray();

                    return $dates;
                } catch (\Throwable $e) {
                    return [];
                }
            }
        );

        return static::$cachedHolidays;
    }

    /**
     * Hitung hari kerja (network days): exclude Sabtu/Minggu + libur nasional.
     * Inclusive start & end. Jika ingin eksklusif start-day, kurangi 1 saat return.
     */
    protected static function hitungHariKerja($start, $end): int|string
    {
        if (!$start || !$end)
            return 'Tidak Ada';

        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->startOfDay();

        if ($end->lt($start))
            return 0;

        $holidays = static::getHolidays();

        $days = 0;
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            if (
                !$cursor->isWeekend() &&
                !in_array($cursor->format('Y-m-d'), $holidays, true)
            ) {
                $days++;
            }
            $cursor->addDay();
        }

        return $days;
    }

    public static function getColumns(): array
    {
        return [
            // Nomor PO (tetap)
            ExportColumn::make('purchase_order_no')
                ->label('Nomor PO')
                ->state(function (TransmittalKembaliDetail $record) {
                    // Ambil sumber code: prioritas dari field detail, fallback ke DO code
                    $source = $record->code ?: $record->deliveryOrderReceipts?->do_code;

                    if (!is_string($source) || $source === '') {
                        return '-';
                    }

                    // Idealnya PO ada di 10 digit paling kiri dari code
                    if (preg_match('/^(\d{10})/', $source, $m)) {
                        return $m[1];
                    }

                    // Fallback: buang non-digit, ambil 10 digit pertama yang tersedia
                    $digitsOnly = preg_replace('/\D+/', '', $source);
                    if (strlen($digitsOnly) >= 10) {
                        return substr($digitsOnly, 0, 10);
                    }

                    return '-';
                }),

            ExportColumn::make('code_103')->label('Kode 103'),
            ExportColumn::make('total_item')->label('Total Item'),

            ExportColumn::make('tanggal_kirim')
                ->label('Tanggal Kirim')
                ->state(function (TransmittalKembaliDetail $record) {
                    $date = $record->transmittalKirim?->tanggal_kirim;
                    if ($date instanceof \DateTimeInterface)
                        return Carbon::instance($date)->format('d-m-Y');
                    if (is_string($date) && $date !== '')
                        return Carbon::parse($date)->format('d-m-Y');
                    return '-';
                }),

            ExportColumn::make('tanggal_kembali')
                ->label('Tanggal Kembali')
                ->state(function (TransmittalKembaliDetail $record) {
                    $date = $record->transmittalKembali?->tanggal_kembali;
                    if ($date instanceof \DateTimeInterface)
                        return Carbon::instance($date)->format('d-m-Y');
                    if (is_string($date) && $date !== '')
                        return Carbon::parse($date)->format('d-m-Y');
                    return '-';
                }),

            // ⬇️ Network days (hari kerja)
            ExportColumn::make('lead_time_hari')
                ->label('Lead Time (hari kerja)')
                ->state(function (TransmittalKembaliDetail $record) {
                    $kirim = $record->transmittalKirim?->tanggal_kirim;
                    $kembali = $record->transmittalKembali?->tanggal_kembali;

                    // kembalikan integer agar kolom numerik rapi di Excel
                    $val = self::hitungHariKerja($kirim, $kembali);
                    return is_int($val) ? $val : null; // kalau 'Tidak Ada' -> kosongkan
                }),

            ExportColumn::make('pembuat_kirim')
                ->label('Pembuat Kirim')
                ->state(fn(TransmittalKembaliDetail $r) => $r->transmittalKirim?->users?->name),

            ExportColumn::make('pembuat_kembali')
                ->label('Pembuat Kembali')
                ->state(fn(TransmittalKembaliDetail $r) => $r->transmittalKembali?->createdBy?->name),

            ExportColumn::make('created_at')
                ->label('Dibuat')
                ->state(fn(TransmittalKembaliDetail $r) => optional($r->created_at)?->format('Y-m-d H:i:s')),

            ExportColumn::make('updated_at')
                ->label('Diubah')
                ->state(fn(TransmittalKembaliDetail $r) => optional($r->updated_at)?->format('Y-m-d H:i:s')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Ekspor "Transmittal Kembali Detail" selesai. '
            . number_format($export->successful_rows)
            . ' ' . str('baris')->plural($export->successful_rows) . ' berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount)
                . ' ' . str('baris')->plural($failedRowsCount) . ' gagal diekspor.';
        }

        return $body;
    }
}
