<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ReceivingChartHelpers;
use App\Models\DeliveryOrderReceipt;
use App\Models\GoodsReceiptSlip;
use App\Models\ReturnDeliveryToVendor;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class DoGrsRdtvTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Tren: DO vs 105 (GRS) vs 124 (RDTV)';
    protected static ?int $sort = 2;
    protected static ?string $maxHeight = '279px';
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

            case 'week': // 12 minggu terakhir (per MINGGU ISO), label: 2025-W09, dst.
                $start = $now->copy()->subWeeks(11)->startOfWeek(CarbonInterface::MONDAY);
                $end = $now->copy()->endOfWeek(CarbonInterface::SUNDAY);
                $keys = $this->weeklyKeys($start, $end); // ['2025-W01', '2025-W02', ...]
                $labels = $keys;
                $groupExpr = "DATE_FORMAT(%s, '%x-W%v')";
                $useDateOnly = false;
                break;

            case 'year': // 5 tahun terakhir (per TAHUN)
                $startYear = $now->year - 4;
                $endYear = $now->year;
                $start = Carbon::create($startYear, 1, 1)->startOfDay();
                $end = Carbon::create($endYear, 12, 31)->endOfDay();
                $keys = $this->yearlyKeys($startYear, $endYear); // ['2021','2022','2023','2024','2025']
                $labels = $keys; // tampilkan angka tahun
                $groupExpr = "YEAR(%s)";
                $useDateOnly = false;
                break;

            case 'month': // BULANAN: Jan–Des TAHUN BERJALAN (per BULAN)
            default:
                $year = $now->year;
                $start = Carbon::create($year, 1, 1)->startOfDay();
                $end = Carbon::create($year, 12, 31)->endOfDay();
                $keys = $this->monthlyKeys($start, $end); // ['Y-m' 12 elemen]
                $labels = array_map(fn($m) => Carbon::createFromFormat('Y-m', $m)->translatedFormat('M'), $keys);
                $groupExpr = "DATE_FORMAT(%s, '%Y-%m')";
                $useDateOnly = false;
                break;
        }

        // Helper ambil map [key => count] sesuai grouping
        $fetch = function (string $modelClass, string $col) use ($start, $end, $groupExpr, $useDateOnly) {
            $group = str_replace('%s', $col, $groupExpr); // hindari sprintf conflict dgn %x/%v
            $q = $modelClass::query();

            if ($useDateOnly) {
                $q->whereDate($col, '>=', $start->toDateString())
                    ->whereDate($col, '<=', $end->toDateString());
            } else {
                $q->where($col, '>=', $start)
                    ->where($col, '<=', $end);
            }

            return $q->selectRaw("$group as k, COUNT(*) as c")
                ->groupBy('k')
                ->pluck('c', 'k')
                ->toArray();
        };

        // Ambil data
        $doMap = $fetch(DeliveryOrderReceipt::class, 'received_date');
        $grsMap = $fetch(GoodsReceiptSlip::class, 'tanggal_terbit');
        $rdtvMap = $fetch(ReturnDeliveryToVendor::class, 'tanggal_terbit');

        // Susun data series mengikuti $keys
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
        $c = $start->copy();
        while ($c->lte($end)) {
            $keys[] = $c->format('Y-m-d');
            $c->addDay();
        }
        return $keys;
    }

    /** Array of ISO week keys like 2025-W09 from $start..$end */
    private function weeklyKeys(Carbon $start, Carbon $end): array
    {
        $keys = [];
        $c = $start->copy()->startOfWeek(CarbonInterface::MONDAY);
        while ($c->lte($end)) {
            $keys[] = $c->isoFormat('GGGG-[W]WW'); // 2025-W09
            $c->addWeek();
        }
        return $keys;
    }

    /** Array of Y-m (Jan..Des) dari $start..$end (inklusif) */
    private function monthlyKeys(Carbon $start, Carbon $end): array
    {
        $keys = [];
        $c = $start->copy()->startOfMonth();
        while ($c->lte($end)) {
            $keys[] = $c->format('Y-m');
            $c->addMonth();
        }
        return $keys;
    }

    /** Array tahun ['2021','2022',...,'2025'] */
    private function yearlyKeys(int $startYear, int $endYear): array
    {
        $keys = [];
        for ($y = $startYear; $y <= $endYear; $y++) {
            $keys[] = (string) $y;
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
            'scales' => [
                'x' => [
                    'ticks' => [
                        'autoSkip' => true,
                        'maxRotation' => 0,
                        'minRotation' => 0,
                    ],
                ],
            ],
        ];
    }
}
