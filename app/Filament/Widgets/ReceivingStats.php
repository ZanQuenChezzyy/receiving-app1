<?php

namespace App\Filament\Widgets;

use App\Models\DeliveryOrderReceipt;
use App\Models\GoodsReceiptSlip;
use App\Models\ReturnDeliveryToVendor;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ReceivingStats extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';
    protected static ?array $cachedHolidays = null;


    protected function getStats(): array
    {
        $today = Carbon::today();
        $now = Carbon::now();
        $startMonth = $now->copy()->startOfMonth();

        // === Range chart 14 hari ===
        $points = 14;
        $endDay = Carbon::today();
        $startDay = $endDay->copy()->subDays($points - 1);
        $labels = [];
        for ($i = 0; $i < $points; $i++) {
            $labels[] = $startDay->copy()->addDays($i)->toDateString(); // Y-m-d
        }

        // Helpers ------------------------------------------------------------
        $seriesFromMap = function (array $byDate) use ($labels): array {
            return array_map(fn($d) => (int) ($byDate[$d] ?? 0), $labels);
        };
        $prefixFromDailySeries = function (array $daily) {
            $sum = 0;
            $out = [];
            foreach ($daily as $v) {
                $sum += (int) $v;
                $out[] = $sum;
            }
            return $out;
        };
        $countByDate = function ($dates) {
            $out = [];
            foreach ($dates as $d) {
                if ($d) {
                    $out[$d] = ($out[$d] ?? 0) + 1;
                }
            }
            return $out;
        };

        // 1) DO hari ini & MTD + chart harian --------------------------------
        $doToday = DeliveryOrderReceipt::whereDate('received_date', $today)->count();
        $doMtd = DeliveryOrderReceipt::whereBetween('received_date', [$startMonth, $now])->count();

        $doPerDayMap = DeliveryOrderReceipt::whereBetween('received_date', [$startDay, $endDay])
            ->selectRaw('DATE(received_date) d, COUNT(*) c')
            ->groupBy('d')->pluck('c', 'd')->toArray();

        $chartDo = $seriesFromMap($doPerDayMap);
        $cumDoReceived = $prefixFromDailySeries($chartDo);

        // 2) 103 selesai (MTD) ------------------------------------------------
        $doWithKirimMtd = DeliveryOrderReceipt::whereHas(
            'transmittalKirims',
            fn($q) =>
            $q->whereDate('tanggal_kirim', '>=', $startMonth)
        )->count();

        $do103DoneMtd = DeliveryOrderReceipt::whereHas(
            'transmittalKirims',
            fn($q) =>
            $q->whereDate('tanggal_kirim', '>=', $startMonth)
        )->whereHas(
                'transmittalKirims.transmittalKembaliDetails.transmittalKembali',
                fn($q) =>
                $q->whereDate('tanggal_kembali', '>=', $startMonth)
            )->count();

        $pct103 = $doWithKirimMtd > 0 ? round(($do103DoneMtd / $doWithKirimMtd) * 100) : 0;

        // --- Ambil tanggal pertama KIRIM & KEMBALI per DO (<= endDay) -----
        $firstKirimDates = DB::table('transmittal_kirims as tk')
            ->join('delivery_order_receipts as dor', 'dor.id', '=', 'tk.delivery_order_receipt_id')
            ->whereDate('tk.tanggal_kirim', '<=', $endDay->toDateString())
            ->groupBy('dor.id')
            ->selectRaw('DATE(MIN(tk.tanggal_kirim)) as d')
            ->pluck('d')
            ->filter()
            ->values()
            ->all(); // array of 'Y-m-d'

        $firstKembaliDates = DB::table('transmittal_kirims as tk')
            ->join('transmittal_kembali_details as tkd', 'tkd.transmittal_kirim_id', '=', 'tk.id')
            ->join('transmittal_kembalis as tkk', 'tkk.id', '=', 'tkd.transmittal_kembali_id')
            ->join('delivery_order_receipts as dor', 'dor.id', '=', 'tk.delivery_order_receipt_id')
            ->whereDate('tkk.tanggal_kembali', '<=', $endDay->toDateString())
            ->groupBy('dor.id')
            ->selectRaw('DATE(MIN(tkk.tanggal_kembali)) as d')
            ->pluck('d')
            ->filter()
            ->values()
            ->all();

        $firstKirimMap = $countByDate($firstKirimDates);    // date => jumlah DO pertama kali kirim
        $firstKembaliMap = $countByDate($firstKembaliDates);  // date => jumlah DO pertama kali kembali

        $chart103Done = $seriesFromMap($firstKembaliMap);   // reuse sebagai “103 selesai per hari”
        $seriesKirim = $seriesFromMap($firstKirimMap);
        $seriesKembali = $chart103Done;

        $cumKirim = $prefixFromDailySeries($seriesKirim);
        $cumKembali = $prefixFromDailySeries($seriesKembali);

        // 3) Outstanding 103 per hari = kumulatif kirim - kumulatif kembali ---
        $chartOut103 = [];
        for ($i = 0; $i < $points; $i++) {
            $chartOut103[$i] = max(0, ($cumKirim[$i] ?? 0) - ($cumKembali[$i] ?? 0));
        }
        $out103 = (int) Arr::last($chartOut103);

        // 4) Outstanding proses awal (belum 105 & 124) ------------------------
        //    Earliest process per DO = min(first GRS, first RDTV)
        $grsMin = DB::table('goods_receipt_slips')
            ->selectRaw('delivery_order_receipt_id, MIN(tanggal_terbit) as min_grs')
            ->groupBy('delivery_order_receipt_id');

        $rdtvMin = DB::table('return_delivery_to_vendors')
            ->selectRaw('delivery_order_receipt_id, MIN(tanggal_terbit) as min_rdtv')
            ->groupBy('delivery_order_receipt_id');

        $firstProcessDates = DB::table('delivery_order_receipts as dor')
            ->leftJoinSub($grsMin, 'g', 'g.delivery_order_receipt_id', '=', 'dor.id')
            ->leftJoinSub($rdtvMin, 'r', 'r.delivery_order_receipt_id', '=', 'dor.id')
            ->whereDate('dor.received_date', '<=', $endDay->toDateString())
            ->selectRaw("
            DATE(COALESCE(LEAST(g.min_grs, r.min_rdtv), g.min_grs, r.min_rdtv)) as d
        ")
            ->pluck('d')
            ->filter()
            ->values()
            ->all();

        $firstProcessMap = $countByDate($firstProcessDates);
        $seriesFirstProc = $seriesFromMap($firstProcessMap);
        $cumFirstProc = $prefixFromDailySeries($seriesFirstProc);

        // Outstanding proses = kumulatif DO diterima - kumulatif earliest proses
        $chartOutProses = [];
        for ($i = 0; $i < $points; $i++) {
            $chartOutProses[$i] = max(0, ($cumDoReceived[$i] ?? 0) - ($cumFirstProc[$i] ?? 0));
        }
        $outProses = (int) Arr::last($chartOutProses);

        // 5) 105 & 124 MTD + chart per hari -----------------------------------
        $grsMtd = GoodsReceiptSlip::whereBetween('tanggal_terbit', [$startMonth, $now])->count();
        $rdtvMtd = ReturnDeliveryToVendor::whereBetween('tanggal_terbit', [$startMonth, $now])->count();

        $grsPerDayMap = GoodsReceiptSlip::whereBetween('tanggal_terbit', [$startDay, $endDay])
            ->selectRaw('DATE(tanggal_terbit) d, COUNT(*) c')
            ->groupBy('d')->pluck('c', 'd')->toArray();

        $rdtvPerDayMap = ReturnDeliveryToVendor::whereBetween('tanggal_terbit', [$startDay, $endDay])
            ->selectRaw('DATE(tanggal_terbit) d, COUNT(*) c')
            ->groupBy('d')->pluck('c', 'd')->toArray();

        $chart105 = $seriesFromMap($grsPerDayMap);
        $chart124 = $seriesFromMap($rdtvPerDayMap);

        // 6) Lead time 103 (network days) — rata-rata per hari selesai --------
        $leadRows = DB::table('transmittal_kirims as tk')
            ->join('transmittal_kembali_details as tkd', 'tkd.transmittal_kirim_id', '=', 'tk.id')
            ->join('transmittal_kembalis as tkk', 'tkk.id', '=', 'tkd.transmittal_kembali_id')
            ->join('delivery_order_receipts as dor', 'dor.id', '=', 'tk.delivery_order_receipt_id')
            ->whereBetween(DB::raw('DATE(tkk.tanggal_kembali)'), [$startDay->toDateString(), $endDay->toDateString()])
            ->groupBy('dor.id')
            ->selectRaw('dor.id dor_id, MIN(tk.tanggal_kirim) min_kirim, DATE(MIN(tkk.tanggal_kembali)) d')
            ->get();

        $sumPerDay = $cntPerDay = [];
        foreach ($leadRows as $r) {
            if ($r->min_kirim && $r->d) {
                $days = self::hitungHariKerja($r->min_kirim, $r->d); // exclude weekend & libur nasional
                if (is_int($days)) {
                    $sumPerDay[$r->d] = ($sumPerDay[$r->d] ?? 0) + $days;
                    $cntPerDay[$r->d] = ($cntPerDay[$r->d] ?? 0) + 1;
                }
            }
        }

        $avgLeadPerDay = [];
        foreach ($labels as $d) {
            $avgLeadPerDay[] = !empty($cntPerDay[$d]) ? round($sumPerDay[$d] / $cntPerDay[$d], 1) : 0;
        }
        $avgLead103 = ($n = count(array_filter($avgLeadPerDay))) ? round(array_sum($avgLeadPerDay) / $n, 1) : 0.0;

        // Warna ----------------------------------------------------------------
        $color103Rate = $pct103 >= 90 ? 'success' : ($pct103 >= 70 ? 'warning' : 'danger');
        $colorOut103 = $out103 === 0 ? 'success' : 'danger';
        $colorOutProses = $outProses === 0 ? 'success' : 'warning';
        $colorLead = $avgLead103 <= 3 ? 'success' : ($avgLead103 <= 5 ? 'warning' : 'danger');

        // Optional tooltip: daftar libur nasional dalam window chart
        $holidaysInWindow = static::getHolidaysInRange($startDay, $endDay);
        $holidayInfo = count($holidaysInWindow)
            ? ('Libur nasional terhitung: ' . implode(', ', $holidaysInWindow))
            : 'Tidak ada libur nasional dalam periode';

        return [
            Stat::make('DO diterima — hari ini', number_format($doToday))
                ->description('Bulan berjalan (MTD): ' . number_format($doMtd) . ' DO')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->chart($chartDo)
                ->color('primary')
                ->extraAttributes(['title' => 'MTD = Month-To-Date (bulan berjalan)']),

            Stat::make('103 selesai — bulan berjalan', $pct103 . '%')
                ->description($do103DoneMtd . ' dari ' . $doWithKirimMtd . ' DO (bulan berjalan / MTD)')
                ->descriptionIcon('heroicon-m-check-badge')
                ->chart($chart103Done)
                ->color($color103Rate)
                ->extraAttributes(['title' => 'MTD = Month-To-Date (bulan berjalan)']),

            Stat::make('Outstanding 103 (belum kembali)', number_format($out103))
                ->description('Sudah kirim QC, dokumen belum kembali (103)')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->chart($chartOut103)
                ->color($colorOut103),

            Stat::make('Outstanding proses awal', number_format($outProses))
                ->description('Belum dibuat 105 (GRS) & 124 (RDTV)')
                ->descriptionIcon('heroicon-m-clock')
                ->chart($chartOutProses)
                ->color($colorOutProses),

            Stat::make('105 (GRS) — bulan berjalan', number_format($grsMtd))
                ->description('124 (RDTV) — bulan berjalan: ' . number_format($rdtvMtd))
                ->descriptionIcon('heroicon-m-arrows-right-left')
                ->chart($chart105) // kalau mau gabung, jumlahkan $chart105 dan $chart124 per indeks
                ->color('info')
                ->extraAttributes(['title' => 'MTD = Month-To-Date (bulan berjalan)']),

            Stat::make('Rata-rata lead time 103 (hari kerja)', $avgLead103 . ' hari')
                ->description('14 hari terakhir (Sen–Jum, libur nasional tidak dihitung)')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->chart($avgLeadPerDay)
                ->color($colorLead)
                ->extraAttributes(['title' => $holidayInfo]),
        ];
    }


    protected static function getHolidaysByYear(int $year): array
    {
        $key = "holidays:id:$year";

        return Cache::remember(
            $key,
            Carbon::create($year, 12, 31, 23, 59, 59)->diffInSeconds(now()),
            function () use ($year) {
                try {
                    // Ambil dari api-harilibur, boleh pakai ?year= juga meski opsional
                    $resp = Http::withOptions(['verify' => false])->timeout(4)
                        ->get('https://api-harilibur.vercel.app/api', ['year' => $year]);

                    $rows = collect($resp->json());

                    // Filter: hanya libur nasional
                    $dates = $rows
                        ->filter(fn($r) => (bool) data_get($r, 'is_national_holiday') === true)
                        // Normalisasi ke Y-m-d (zero-pad) agar cocok dengan $cursor->format('Y-m-d')
                        ->map(function ($r) {
                        $raw = (string) data_get($r, 'holiday_date');
                        try {
                            return Carbon::parse($raw)->format('Y-m-d');
                        } catch (\Throwable $e) {
                            return null;
                        }
                    })
                        ->filter()
                        // Pastikan memang tahun yang diminta
                        ->filter(fn($d) => Str::startsWith($d, "{$year}-"))
                        ->unique()
                        ->values()
                        ->toArray();

                    return $dates;
                } catch (\Throwable $e) {
                    return [];
                }
            }
        );
    }

    /** Ambil libur nasional utk rentang start..end (merge per tahun di rentang) */
    protected static function getHolidaysInRange($start, $end): array
    {
        $s = Carbon::parse($start)->startOfDay();
        $e = Carbon::parse($end)->startOfDay();

        $years = range((int) $s->year, (int) $e->year);

        $all = [];
        foreach ($years as $y) {
            $all = array_merge($all, static::getHolidaysByYear($y));
        }

        // Batasi hanya yg berada di rentang start..end
        return array_values(array_filter($all, function ($date) use ($s, $e) {
            try {
                $d = Carbon::parse($date)->startOfDay();
                return $d->betweenIncluded($s, $e);
            } catch (\Throwable $e) {
                return false;
            }
        }));
    }

    /** Network days: Sen–Jum, exclude libur nasional (lintas tahun OK) */
    protected static function hitungHariKerja($start, $end): string|int
    {
        if (!$start || !$end) {
            return 'Tidak Ada';
        }

        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->startOfDay();

        if ($end->lt($start)) {
            return 0;
        }

        $holidays = static::getHolidaysInRange($start, $end); // ⬅️ pakai rentang, bukan 1 tahun saja

        $days = 0;
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $isHoliday = in_array($cursor->format('Y-m-d'), $holidays, true);
            if (!$cursor->isWeekend() && !$isHoliday) {
                $days++;
            }
            $cursor->addDay();
        }

        return $days;
    }
}
