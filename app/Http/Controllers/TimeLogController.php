<?php

namespace App\Http\Controllers;

use App\Models\TimeLog;
use App\Models\Project;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TimeLogController extends Controller
{
    public function startTimer(Request $request)
    {
        $user = auth()->user();

        // Check if there's already an active timer
        if ($user->isTimerRunning()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You already have an active timer running.'
            ]);
        }

        // Get default project or create one if it doesn't exist
        $project = Project::where('user_id', $user->id)
            ->where('is_default', true)
            ->first();

        if (!$project) {
            $project = Project::create([
                'user_id' => $user->id,
                'name' => 'Default Project',
                'description' => 'Default project for time logging',
                'is_default' => true,
            ]);
        }

        // Create new time log
        $timeLog = TimeLog::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'check_in' => Carbon::now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Timer started successfully',
            'data' => $timeLog
        ]);
    }

    public function stopTimer(Request $request)
    {
        $user = auth()->user();
        $activeTimeLog = $user->activeTimeLog();

        if (!$activeTimeLog) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active timer to stop.'
            ]);
        }

        $activeTimeLog->update([
            'check_out' => Carbon::now(),
            'notes' => $request->notes
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Timer stopped successfully',
            'data' => $activeTimeLog
        ]);
    }

    public function getTimerStatus()
    {
        $user = auth()->user();
        $activeTimeLog = $user->activeTimeLog();

        $data = [
            'is_active' => false,
            'started_at' => null,
            'duration' => 0,
            'formatted_duration' => '00:00',
            'project' => null
        ];

        if ($activeTimeLog) {
            $data = [
                'is_active' => true,
                'started_at' => $activeTimeLog->check_in->toDateTimeString(),
                'duration' => $activeTimeLog->duration,
                'formatted_duration' => $activeTimeLog->formatted_duration,
                'project' => $activeTimeLog->project
            ];
        }

        return response()->json($data);
    }

    public function getMonthlyStats(Request $request)
    {
        $user = auth()->user();
        $month = $request->month ?? Carbon::now()->month;
        $year = $request->year ?? Carbon::now()->year;

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $dailyStats = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dailyStats[$date->format('Y-m-d')] = 0;
        }

        $timeLogs = TimeLog::where('user_id', $user->id)
            ->whereDate('check_in', '>=', $startDate)
            ->whereDate('check_in', '<=', $endDate)
            ->get();

        foreach ($timeLogs as $log) {
            $date = $log->check_in->format('Y-m-d');

            // Calculate duration in hours
            $duration = $log->check_out
                ? $log->check_out->diffInSeconds($log->check_in) / 3600
                : Carbon::now()->diffInSeconds($log->check_in) / 3600;

            if (isset($dailyStats[$date])) {
                $dailyStats[$date] += $duration;
            }
        }

        // Format for chart
        $chartData = [];
        foreach ($dailyStats as $date => $hours) {
            $chartData[] = [
                'date' => $date,
                'hours' => round($hours, 2)
            ];
        }

        return response()->json($chartData);
    }
}