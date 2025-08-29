<?php

namespace App\Filament\Widgets\Concerns;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

trait ReceivingChartHelpers
{
    protected function dayLabels(int $days = 30): array
    {
        $end = Carbon::today();
        $start = $end->copy()->subDays($days - 1);
        $labels = [];
        for ($i = 0; $i < $days; $i++) {
            $labels[] = $start->copy()->addDays($i)->toDateString();
        }
        return $labels;
    }

    protected function seriesFromMap(array $map, array $labels): array
    {
        return array_map(fn($d) => (int) ($map[$d] ?? 0), $labels);
    }

    /** Kumulatif dari array harian */
    protected function prefixSum(array $daily): array
    {
        $sum = 0;
        $out = [];
        foreach ($daily as $v) {
            $sum += (int) $v;
            $out[] = $sum;
        }
        return $out;
    }

    /** ====== Network Days (libur nasional dikecualikan) ====== */

    protected static function getHolidaysByYear(int $year): array
    {
        $key = "holidays:id:$year";
        return Cache::remember(
            $key,
            Carbon::create($year, 12, 31, 23, 59, 59)->diffInSeconds(now()),
            function () use ($year) {
                try {
                    $resp = Http::withOptions(['verify' => false])->timeout(4)
                        ->get('https://api-harilibur.vercel.app/api', ['year' => $year]);

                    $rows = collect($resp->json());

                    return $rows
                        ->filter(fn($r) => (bool) data_get($r, 'is_national_holiday') === true)
                        ->map(function ($r) {
                            $raw = (string) data_get($r, 'holiday_date');
                            try {
                                return Carbon::parse($raw)->format('Y-m-d');
                            } catch (\Throwable) {
                                return null;
                            }
                        })
                        ->filter()
                        ->filter(fn($d) => Str::startsWith($d, "{$year}-"))
                        ->unique()
                        ->values()
                        ->toArray();
                } catch (\Throwable) {
                    return [];
                }
            }
        );
    }

    protected static function getHolidaysInRange($start, $end): array
    {
        $s = Carbon::parse($start)->startOfDay();
        $e = Carbon::parse($end)->startOfDay();
        $years = range((int) $s->year, (int) $e->year);

        $all = [];
        foreach ($years as $y) {
            $all = array_merge($all, static::getHolidaysByYear($y));
        }

        return array_values(array_filter($all, function ($date) use ($s, $e) {
            try {
                $d = Carbon::parse($date)->startOfDay();
                return $d->betweenIncluded($s, $e);
            } catch (\Throwable) {
                return false;
            }
        }));
    }

    /** Hitung hari kerja inklusif start..end, exclude weekend & libur nasional */
    protected static function networkDays($start, $end): int
    {
        $start = Carbon::parse($start)->startOfDay();
        $end = Carbon::parse($end)->startOfDay();
        if ($end->lt($start))
            return 0;

        $holidays = static::getHolidaysInRange($start, $end);

        $days = 0;
        $c = $start->copy();
        while ($c->lte($end)) {
            $isHoliday = in_array($c->format('Y-m-d'), $holidays, true);
            if (!$c->isWeekend() && !$isHoliday)
                $days++;
            $c->addDay();
        }
        return $days;
    }
}
