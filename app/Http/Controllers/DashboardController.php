<?php

namespace App\Http\Controllers;

use App\Services\IncidentSqlService;

class DashboardController extends Controller
{
    public function __invoke(IncidentSqlService $sqlService)
    {
        $summary = $sqlService->summary();
        $pinnedCritical = $sqlService->pinnedCritical();
        $criticalAlerts = $sqlService->criticalAlerts();
        $incidents = $sqlService->incidentList();
        $recentActivities = $sqlService->recentActivities();
        $dailyTrend = $sqlService->trendData(7);
        // pull 28 days and aggregate into 4 weekly buckets (most recent weeks)
        $raw28 = $sqlService->trendData(28);
        $weeklyTrend = $raw28->chunk(7)->map(function ($chunk, $i) {
            $startDay = $chunk->first()['day'];
            $endDay = $chunk->last()['day'];
            $sum = $chunk->sum('value');

            // format label as range like '1–7 May' or fallback to 'Week X'
            try {
                $start = \Carbon\Carbon::parse($startDay);
                $end = \Carbon\Carbon::parse($endDay);
                if ($start->format('M') === $end->format('M')) {
                    $label = $start->format('j') . '–' . $end->format('j') . ' ' . $start->format('M');
                } else {
                    $label = $start->format('j M') . '–' . $end->format('j M');
                }
            } catch (\Exception $e) {
                $label = 'Week ' . ($i + 1);
            }

            return ['label' => $label, 'value' => (int) $sum];
        })->values();

        $trendData = $dailyTrend; // keep existing variable for backward compatibility in views

        return view('dashboard', compact(
            'summary',
            'pinnedCritical',
            'criticalAlerts',
            'incidents',
            'recentActivities',
            'trendData',
            'dailyTrend',
            'weeklyTrend'
        ));
    }

    public function history(IncidentSqlService $sqlService)
    {
        $activities = $sqlService->recentActivities(30);

        return view('activity-logs.index', compact('activities'));
    }

    public function activityLogs(IncidentSqlService $sqlService)
    {
        return $this->history($sqlService);
    }
}
