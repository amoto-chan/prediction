<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\SystemLog;
use App\Services\PredictionEngine;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $records = $user->scopedRecords()->with(['student', 'instructor'])->latest()->get();

        $statuses = ['Passed', 'At Risk', 'Failed', PredictionEngine::NO_PREDICTION];
        $counts = collect($statuses)->mapWithKeys(fn (string $status) => [$status => $records->where('prediction', $status)->count()]);

        $labels = config('prediction.indicator_labels');
        $indicatorAverages = collect(PredictionEngine::INDICATORS)->map(function (string $indicator) use ($records, $labels) {
            return [
                'key' => $indicator,
                'label' => $labels[$indicator] ?? ucfirst(str_replace('_', ' ', $indicator)),
                'value' => round((float) ($records->pluck($indicator)->filter(fn ($value) => $value !== null)->avg() ?? 0), 1),
            ];
        })->values();

        $weakest = $indicatorAverages->filter(fn (array $item) => $item['value'] > 0)->sort('value')->first();

        $sectionCounts = $records->groupBy('section')->map->count()->sortKeys();

        // Last 8 weeks of record activity (line chart).
        $trendLabels = [];
        $trendCounts = [];
        for ($i = 7; $i >= 0; $i--) {
            $start = now()->startOfWeek()->subWeeks($i);
            $end = (clone $start)->endOfWeek();
            $trendLabels[] = $start->format('M d');
            $trendCounts[] = $records->filter(fn ($record) => $record->created_at && $record->created_at->between($start, $end))->count();
        }

        $monitoredStudents = $records->pluck('student_id')->unique()->count();

        // Students needing intervention (admin & instructor dashboards).
        $atRiskStudents = collect();
        if (! $user->isRole('student')) {
            $atRiskStudents = $records
                ->filter(fn ($record) => in_array($record->prediction, ['At Risk', 'Failed'], true))
                ->groupBy('student_id')
                ->map(fn ($group) => [
                    'student' => $group->first()->student,
                    'section' => $group->first()->section,
                    'status' => $group->contains(fn ($record) => $record->prediction === 'Failed') ? 'Failed' : 'At Risk',
                ])
                ->values()
                ->filter(fn (array $item) => $item['student'] !== null);
        }

        $inbox = Message::where('recipient_id', $user->id)->with('sender')->latest()->take(5)->get();
        $unreadCount = Message::where('recipient_id', $user->id)->whereNull('read_at')->count();

        $logs = $user->isRole('admin')
            ? SystemLog::with('user')->latest()->take(8)->get()
            : collect();

        // Student extras: assigned instructors for enrolled subjects.
        $assignedInstructors = collect();
        if ($user->isRole('student')) {
            $instructorIds = $records->pluck('instructor_id')->filter()->unique()->values();
            $assignedInstructors = \App\Models\User::whereIn('id', $instructorIds)->orderBy('name')->get();
        }

        return view('dashboard', [
            'records' => $records,
            'counts' => $counts,
            'indicatorAverages' => $indicatorAverages,
            'weakest' => $weakest,
            'sectionCounts' => $sectionCounts,
            'trendLabels' => $trendLabels,
            'trendCounts' => $trendCounts,
            'monitoredStudents' => $monitoredStudents,
            'atRiskStudents' => $atRiskStudents,
            'inbox' => $inbox,
            'unreadCount' => $unreadCount,
            'logs' => $logs,
            'assignedInstructors' => $assignedInstructors,
        ]);
    }

    /** JSON payload used by the dashboard's periodic live refresh. */
    public function analytics(Request $request)
    {
        $records = $request->user()->scopedRecords()->get();
        $statuses = [PredictionEngine::PASSED, PredictionEngine::AT_RISK, PredictionEngine::FAILED, PredictionEngine::NO_PREDICTION];
        $counts = collect($statuses)->mapWithKeys(fn (string $status) => [$status => $records->where('prediction', $status)->count()]);
        $labels = config('prediction.indicator_labels');
        $indicatorAverages = collect(PredictionEngine::INDICATORS)->map(function (string $indicator) use ($records, $labels) {
            return [
                'key' => $indicator,
                'label' => $labels[$indicator] ?? ucfirst(str_replace('_', ' ', $indicator)),
                'value' => round((float) ($records->pluck($indicator)->filter(fn ($value) => $value !== null)->avg() ?? 0), 1),
            ];
        })->values();
        $sectionCounts = $records->groupBy('section')->map->count()->sortKeys();
        $trendLabels = [];
        $trendCounts = [];
        for ($i = 7; $i >= 0; $i--) {
            $start = now()->startOfWeek()->subWeeks($i);
            $trendLabels[] = $start->format('M d');
            $trendCounts[] = $records->filter(fn ($record) => $record->created_at && $record->created_at->between($start, (clone $start)->endOfWeek()))->count();
        }

        return response()->json([
            'counts' => $counts,
            'indicatorAverages' => $indicatorAverages,
            'sectionCounts' => $sectionCounts,
            'sectionLabels' => $sectionCounts->keys(),
            'trendCounts' => $trendCounts,
            'monitoredStudents' => $records->pluck('student_id')->unique()->count(),
            'updatedAt' => now()->toIso8601String(),
        ]);
    }
}