<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ReceivingChartHelpers;
use App\Models\DeliveryOrderReceipt;
use App\Models\GoodsReceiptSlip;
use App\Models\ReturnDeliveryToVendor;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DoGrsRdtvTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Tren: DO vs 105 (GRS) vs 124 (RDTV)';
    protected static ?int $sort = 2;

    public ?string $filter = 'month';

    protected function getFilters(): ?array
    {
        return [
            'day' => 'Harian',
            'week' => 'Mingguan',
            'month' => 'Bulanan',
            'year' => 'Tahunan',
        ];
    }

    protected function getData(): array
    {
        $now = Carbon::today();

        switch ($this->filter) {
            case 'day': // 30 hari terakhir (per HARI)
                $start = $now->copy()->subDays(29);
                $end = $now;
                $keys = $this->dailyKeys($start, $end); // ['Y-m-d', ...]
                $labels = array_map(fn($d) => Carbon::parse($d)->format('d M'), $keys);
                $groupExpr = "DATE(%s)";
                $useDateOnly = true;
                break;

            case 'week': // 12 minggu terakhir (per MINGGU, ISO), format kunci: 2025-W09
                $start = $now->copy()->subWeeks(11)->startOfWeek(Carbon::MONDAY);
                $end = $now->copy()->endOfWeek(Carbon::MONDAY);
                $keys = $this->weeklyKeys($start, $end); // ['2025-W01', '2025-W02', ...]
                $labels = $keys; // langsung tampilkan 2025-Www supaya jelas
                $groupExpr = "DATE_FORMAT(%s, '%x-W%v')";
                $useDateOnly = false;
                break;

            case 'year': // Tahun berjalan (per BULAN)
                $start = $now->copy()->startOfYear();
                $end = $now;
                $keys = $this->monthlyKeys($start, $end); // ['Y-m', ...]
                $labels = array_map(fn($m) => Carbon::createFromFormat('Y-m', $m)->translatedFormat('M'), $keys);
                $groupExpr = "DATE_FORMAT(%s, '%Y-%m')";
                $useDateOnly = false;
                break;

            case 'month': // default: 30 hari terakhir (per HARI)
            default:
                $start = $now->copy()->subDays(29);
                $end = $now;
                $keys = $this->dailyKeys($start, $end);
                $labels = array_map(fn($d) => Carbon::parse($d)->format('d M'), $keys);
                $groupExpr = "DATE(%s)";
                $useDateOnly = true;
                break;
        }

        // Helper: ambil map [key => count] dengan grouping yang konsisten
        $fetch = function (string $modelClass, string $col) use ($start, $end, $groupExpr, $useDateOnly) {
            $group = sprintf($groupExpr, $col);
            $q = $modelClass::query();

            if ($useDateOnly) {
                // pakai DATE() di WHERE juga (2 argumen tambahan untuk bikin lint senang)
                $q->whereBetween(
                    DB::raw("DATE($col)"),
                    [$start->toDateString(), $end->toDateString()],
                    'and',
                    false
                );
            } else {
                $q->whereBetween(
                    $col,
                    [$start->startOfDay(), $end->endOfDay()],
                    'and',
                    false
                );
            }

            return $q->selectRaw("$group as k, COUNT(*) as c")
                ->groupBy('k')
                ->pluck('c', 'k')
                ->toArray();
        };

        $doMap = $fetch(DeliveryOrderReceipt::class, 'received_date');
        $grsMap = $fetch(GoodsReceiptSlip::class, 'tanggal_terbit');
        $rdtvMap = $fetch(ReturnDeliveryToVendor::class, 'tanggal_terbit');

        $seriesFrom = fn(array $map) => array_map(fn($k) => (int) ($map[$k] ?? 0), $keys);

        return [
            'datasets' => [
                [
                    'label' => 'DO diterima',
                    'data' => $seriesFrom($doMap),
                    'tension' => 0.3,
                    'spanGaps' => true,
                    'borderWidth' => 2,
                    'borderColor' => 'rgb(37, 99, 235)',    // blue-600
                    'backgroundColor' => 'rgba(37, 99, 235, .2)',
                    'fill' => true,
                ],
                [
                    'label' => '105 (GRS)',
                    'data' => $seriesFrom($grsMap),
                    'tension' => 0.3,
                    'spanGaps' => true,
                    'borderWidth' => 2,
                    'borderColor' => 'rgb(34, 197, 94)',     // green-500
                    'backgroundColor' => 'rgba(34, 197, 94, .2)',
                    'fill' => true,
                ],
                [
                    'label' => '124 (RDTV)',
                    'data' => $seriesFrom($rdtvMap),
                    'tension' => 0.3,
                    'spanGaps' => true,
                    'borderWidth' => 2,
                    'borderColor' => 'rgb(244, 63, 94)',     // rose-500
                    'backgroundColor' => 'rgba(244, 63, 94, .2)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    /** Array of Y-m-d from $start..$end */
    private function dailyKeys(Carbon $start, Carbon $end): array
    {
        $keys = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $keys[] = $cursor->format('Y-m-d');
            $cursor->addDay();
        }
        return $keys;
    }

    /** Array of ISO week keys like 2025-W09 from $start..$end */
    private function weeklyKeys(Carbon $start, Carbon $end): array
    {
        $keys = [];
        $cursor = $start->copy()->startOfWeek(Carbon::MONDAY);
        while ($cursor->lte($end)) {
            // pakai format yang match dengan SQL '%x-W%v'
            $keys[] = $cursor->isoFormat('GGGG-[W]WW'); // contoh: 2025-W09
            $cursor->addWeek();
        }
        return $keys;
    }

    /** Array of Y-m from $start..$end (inklusif) */
    private function monthlyKeys(Carbon $start, Carbon $end): array
    {
        $keys = [];
        $cursor = $start->copy()->startOfMonth();
        while ($cursor->lte($end)) {
            $keys[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }
        return $keys;
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['position' => 'bottom'],
                'tooltip' => ['mode' => 'index', 'intersect' => false],
            ],
            'interaction' => ['mode' => 'index', 'intersect' => false],
        ];
    }
}
