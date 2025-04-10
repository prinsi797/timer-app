<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TimeLog;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $activeTimeLog = $user->activeTimeLog();

        $todayLogs = TimeLog::where('user_id', $user->id)
            ->whereDate('check_in', Carbon::today())
            ->get();

        $totalToday = 0;
        foreach ($todayLogs as $log) {
            if ($log->check_out) {
                $totalToday += $log->check_out->diffInSeconds($log->check_in);
            } else {
                $totalToday += Carbon::now()->diffInSeconds($log->check_in);
            }
        }

        $totalTodayFormatted = sprintf(
            '%02d hours %02d minutes',
            floor($totalToday / 3600),
            floor(($totalToday % 3600) / 60)
        );

        $lastActivity = 0;
        if ($activeTimeLog) {
            $lastActivity = Carbon::now()->diffInSeconds($activeTimeLog->check_in);
        }

        $lastActivityFormatted = sprintf(
            '%02d hours %02d minutes',
            floor($lastActivity / 3600),
            floor(($lastActivity % 3600) / 60)
        );

        return view('dashboard', [
            'activeTimeLog' => $activeTimeLog,
            'totalTodayFormatted' => $totalTodayFormatted,
            'lastActivityFormatted' => $lastActivityFormatted,
            'timerActive' => $activeTimeLog !== null
        ]);
    }
}