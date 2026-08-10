<?php

namespace App\Services;

use Asantibanez\LivewireCharts\Models\LineChartModel;
use Asantibanez\LivewireCharts\Models\PieChartModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class DashboardChartsService
{
    /**
     * @param  Collection<int, object{day: string, total: int}>  $rows
     */
    public static function patientVolumeChart(Carbon $today, Collection $rows): LineChartModel
    {
        $volumeByDay = [];

        foreach ($rows as $row) {
            $volumeByDay[Carbon::parse($row->day)->toDateString()] = (int) $row->total;
        }

        $chart = (new LineChartModel)
            ->setTitle('Patient volume')
            ->singleLine()
            ->withLegend()
            ->setDataLabelsEnabled(true)
            ->setAnimated(false)
            ->setColors(['#0d4a3c']);

        for ($daysAgo = 6; $daysAgo >= 0; $daysAgo--) {
            $date = $today->copy()->subDays($daysAgo);
            $chart->addPoint($date->format('D'), $volumeByDay[$date->toDateString()] ?? 0);
        }

        return $chart;
    }

    /**
     * @param  Collection<int, object{name: string, total: int}>  $rows
     */
    public static function presentingIllnessesChart(Collection $rows): PieChartModel
    {
        $colors = ['#0d4a3c', '#f97316', '#ec4899', '#22c55e', '#3b82f6', '#f59e0b'];

        $chart = (new PieChartModel)
            ->setTitle('Top presenting illnesses')
            ->asDonut()
            ->withoutLegend()
            ->setDataLabelsEnabled(false)
            ->setColors($colors);

        if ($rows->isEmpty()) {
            $chart->addSlice('No diagnoses', 1, '#cbd5e1');

            return $chart;
        }

        foreach ($rows->values() as $index => $illness) {
            $chart->addSlice(
                $illness->name,
                (int) $illness->total,
                $colors[$index % count($colors)]
            );
        }

        return $chart;
    }
}
