<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ReceivingChartHelpers;
use Carbon\CarbonInterface;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Cycle103CumulativeChart extends ChartWidget
{
    use ReceivingChartHelpers;
    protected static ?string $heading = null; // ⬅️ biar dinamis
    protected static ?int $sort = 3;
    protected static ?string $maxHeight = '279px';
    public ?string $filter = 'day';

    /** Heading dinamis (cocok dengan parent signature) */
    public function getHeading(): string|Htmlable|null
    {
        $now = Carbon::today();

        $period = [
            'day' => 'Harian (30 hari)',
            'week' => 'Mingguan (12 minggu)',
            'month' => 'Bulanan (Jan-Des ' . $now->year . ')',
            'year' => 'Tahunan (5 tahun)',
        ][$this->filter] ?? 'Periode';

        return "103 Kirim & Kembali {$period}";
    }

    /** Deskripsi dinamis (cocok dengan parent signature) */
    public function getDescription(): string|Htmlable|null
    {
        $now = Carbon::today();

        switch ($this->filter) {
            case 'day':
                $start = $now->copy()->subDays(29);
                $end = $now;
                $periode = $start->format('d M Y') . ' - ' . $end->format('d M Y');
                $bucket = 'per hari';
                break;

            case 'week':
                $start = $now->copy()->subWeeks(11)->startOfWeek(CarbonInterface::MONDAY);
                $end = $now->copy()->endOfWeek(CarbonInterface::SUNDAY);
                $periode = $start->format('d M Y') . ' - ' . $end->format('d M Y');
                $bucket = 'per minggu (ISO)';
                break;

            case 'year':
                $periode = ($now->year - 4) . ' - ' . $now->year;
                $bucket = 'per tahun';
                break;

            case 'month':
            default:
                $periode = 'Jan - Des ' . $now->year;
                $bucket = 'per bulan';
                $bulanIni = $now->translatedFormat('F Y'); // contoh: "Agustus 2025"
                return "Untuk periode {$periode}, menampilkan kumulatif kirim & kembali {$bucket}. Bulan ini: {$bulanIni}.";
        }

        return "Untuk periode {$periode}, menampilkan kumulatif kirim & kembali {$bucket}.";
    }

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

        // ===== Tentukan bucket (keys), label tampilan, dan rentang waktu =====
        switch ($this->filter) {
            case 'day': // 30 hari terakhir, bucket per HARI: Y-m-d
                $start = $now->copy()->subDays(29)->startOfDay();
                $end = $now->copy()->endOfDay();
                $keys = $this->dayLabels(30);                   // ['Y-m-d', ...] dari trait
                $labels = array_map(fn($d) => Carbon::parse($d)->format('d M'), $keys);
                $keyFn = fn($dt) => Carbon::parse($dt)->format('Y-m-d');
                break;

            case 'week': // 12 minggu terakhir, bucket per MINGGU ISO: 2025-W09
                $start = $now->copy()->subWeeks(11)->startOfWeek(CarbonInterface::MONDAY)->startOfDay();
                $end = $now->copy()->endOfWeek(CarbonInterface::MONDAY)->endOfDay();
                $keys = $this->weeklyKeys($start, $end);        // ['YYYY-Www', ...]
                $labels = $keys;                                  // tampilkan apa adanya
                $keyFn = fn($dt) => Carbon::parse($dt)->isoFormat('GGGG-[W]WW');
                break;

            case 'year': // 5 tahun terakhir, bucket per TAHUN: YYYY
                $startYear = $now->year - 4;
                $endYear = $now->year;
                $start = Carbon::create($startYear, 1, 1)->startOfDay();
                $end = Carbon::create($endYear, 12, 31)->endOfDay();
                $keys = $this->yearlyKeys($startYear, $endYear); // ['2021',...,'2025']
                $labels = $keys;
                $keyFn = fn($dt) => Carbon::parse($dt)->format('Y');
                break;

            case 'month': // tahun berjalan, bucket per BULAN: YYYY-MM
            default:
                $year = $now->year;
                $start = Carbon::create($year, 1, 1)->startOfDay();
                $end = Carbon::create($year, 12, 31)->endOfDay();
                $keys = $this->monthlyKeys($start, $end);       // ['YYYY-MM' dari Jan..Des]
                $labels = array_map(fn($m) => Carbon::createFromFormat('Y-m', $m)->translatedFormat('M'), $keys);
                $keyFn = fn($dt) => Carbon::parse($dt)->format('Y-m');
                break;
        }

        // ===== Ambil tanggal pertama KIRIM & KEMBALI per DO dalam rentang =====
        $firstKirimDates = DB::table('transmittal_kirims as tk')
            ->join('delivery_order_receipts as dor', 'dor.id', '=', 'tk.delivery_order_receipt_id')
            ->whereBetween(DB::raw('DATE(tk.tanggal_kirim)'), [$start->toDateString(), $end->toDateString()])
            ->groupBy('dor.id')
            ->selectRaw('DATE(MIN(tk.tanggal_kirim)) as d')   // ⬅️ sudah jadi 'Y-m-d'
            ->pluck('d')
            ->toArray();

        // KEMBALI: sama—bandingkan sebagai DATE, lalu ambil MIN dan cast ke DATE
        $firstKembaliDates = DB::table('transmittal_kirims as tk')
            ->join('transmittal_kembali_details as tkd', 'tkd.transmittal_kirim_id', '=', 'tk.id')
            ->join('transmittal_kembalis as tkk', 'tkk.id', '=', 'tkd.transmittal_kembali_id')
            ->join('delivery_order_receipts as dor', 'dor.id', '=', 'tk.delivery_order_receipt_id')
            ->whereBetween(DB::raw('DATE(tkk.tanggal_kembali)'), [$start->toDateString(), $end->toDateString()])
            ->groupBy('dor.id')
            ->selectRaw('DATE(MIN(tkk.tanggal_kembali)) as d') // ⬅️ sudah jadi 'Y-m-d'
            ->pluck('d')
            ->toArray();

        $baselineKembali = DB::table('transmittal_kirims as tk')
            ->join('transmittal_kembali_details as tkd', 'tkd.transmittal_kirim_id', '=', 'tk.id')
            ->join('transmittal_kembalis as tkk', 'tkk.id', '=', 'tkd.transmittal_kembali_id')
            ->join('delivery_order_receipts as dor', 'dor.id', '=', 'tk.delivery_order_receipt_id')
            ->select('dor.id')
            ->groupBy('dor.id')
            ->havingRaw('MIN(DATE(tkk.tanggal_kembali)) < ?', [$start->toDateString()])
            ->count();

        // ===== Hitung frekuensi per bucket key =====
        $countByKey = function (array $dates) use ($keyFn): array {
            $m = [];
            foreach ($dates as $d) {
                if (!$d)
                    continue;
                $k = $keyFn($d);           // $d sudah 'Y-m-d', aman untuk Carbon::parse
                $m[$k] = ($m[$k] ?? 0) + 1;
            }
            return $m;
        };

        $kirimMap = $countByKey($firstKirimDates);    // ['key' => count]
        $kembaliMap = $countByKey($firstKembaliDates);

        // ===== Susun series sesuai urutan $keys lalu kumulatif =====
        $seriesKirim = $this->seriesFromMap($kirimMap, $keys);     // dari trait
        $seriesKembali = $this->seriesFromMap($kembaliMap, $keys);

        $cumKirim = $this->prefixSum($seriesKirim);             // dari trait
        $cumKembali = $this->prefixSum($seriesKembali);

        // setelah hitung $cumKembali:
        if ($baselineKembali > 0) {
            foreach ($cumKembali as $i => $val) {
                $cumKembali[$i] = $val + $baselineKembali;
            }
        }

        // Backlog = Kirim kumulatif – Kembali kumulatif (>=0)
        $backlog = [];
        foreach ($cumKirim as $i => $v) {
            $backlog[] = max(0, ($cumKirim[$i] ?? 0) - ($cumKembali[$i] ?? 0));
        }

        return [
            'datasets' => [
                [
                    'type' => 'bar',
                    'label' => 'Backlog (Kirim - Kembali)',
                    'data' => $backlog,
                    'backgroundColor' => 'rgba(251, 146, 60, .25)', // amber-400 @ 25%
                    'borderColor' => 'rgb(251, 146, 60)',
                    'borderWidth' => 1,
                    'barPercentage' => 0.9,
                    'categoryPercentage' => 0.9,
                    'order' => 1,
                ],
                [
                    'type' => 'line',
                    'label' => 'Kirim QC (kumulatif)',
                    'data' => $cumKirim,
                    'fill' => true,
                    'tension' => 0.25,
                    'spanGaps' => true,
                    'borderColor' => 'rgb(37, 99, 235)',      // blue-600
                    'backgroundColor' => 'rgba(37, 99, 235, .15)',
                    'borderWidth' => 2,
                    'pointRadius' => 2,
                    'pointHoverRadius' => 5,
                    'pointHitRadius' => 8,
                    'order' => 3,
                ],
                [
                    'type' => 'line',
                    'label' => 'Kembali (kumulatif)',
                    'data' => $cumKembali,
                    'fill' => false,
                    'tension' => 0.25,
                    'spanGaps' => true,
                    'borderColor' => 'rgb(34, 197, 94)',      // green-500
                    'backgroundColor' => 'rgba(34, 197, 94, .25)',
                    'borderWidth' => 2,
                    'borderDash' => [6, 4],
                    'pointRadius' => 2,
                    'pointHoverRadius' => 5,
                    'pointHitRadius' => 8,
                    'order' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        // mixed chart tetap 'line'
        return 'line';
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

    // ===== Helper keys untuk week / month / year =====

    /** ['YYYY-Www', ...] dari $start..$end (inklusif), minggu ISO */
    private function weeklyKeys(Carbon $start, Carbon $end): array
    {
        $keys = [];
        $c = $start->copy()->startOfWeek(CarbonInterface::MONDAY);
        while ($c->lte($end)) {
            $keys[] = $c->isoFormat('GGGG-[W]WW');
            $c->addWeek();
        }
        return $keys;
    }

    /** ['YYYY-MM', ...] Jan..Des (atau rentang $start..$end) */
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

    /** ['YYYY', ...] $startYear..$endYear */
    private function yearlyKeys(int $startYear, int $endYear): array
    {
        $keys = [];
        for ($y = $startYear; $y <= $endYear; $y++) {
            $keys[] = (string) $y;
        }
        return $keys;
    }
}
