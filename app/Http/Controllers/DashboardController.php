<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Models\User;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $today = Carbon::today();
        $user = request()->user();
        if (! $user->isAdmin()) {
            $assigned = Activity::assignedTo($user);
            $todayItems = Activity::with(['assignee', 'assignees', 'latestUpdate'])
                ->where(fn ($query) => $query
                    ->where(fn ($assignedQuery) => $assignedQuery->assignedTo($user)->whereDate('activity_date', $today))
                    ->orWhere(fn ($pendingQuery) => $pendingQuery->assignedTo($user)->where('status', 'Pending')->whereDate('activity_date', '<', $today)))
                ->orderByRaw("CASE WHEN status = 'Pending' THEN 0 ELSE 1 END")
                ->orderBy('title')->get();

            return view('dashboard.staff', [
                'totalAssigned' => (clone $assigned)->count(),
                'pendingAssigned' => (clone $assigned)->where('status', 'Pending')->count(),
                'doneToday' => (clone $assigned)->whereDate('activity_date', $today)->where('status', 'Done')->whereHas('latestUpdate', fn ($query) => $query->where('status', 'Done')->whereDate('created_at', $today))->count(),
                'overdue' => (clone $assigned)->where('status', 'Pending')->whereDate('activity_date', '<', $today)->count(),
                'carriedForward' => Activity::where('status', 'Pending')->whereDate('activity_date', '<', $today)->count(),
                'awaitingUpdates' => Activity::where('status', 'Pending')->whereDoesntHave('updates')->count(),
                'todayItems' => $todayItems,
                'recent' => ActivityUpdate::with('activity')->whereHas('activity', fn ($q) => $q->assignedTo($user))->latest()->limit(6)->get(),
            ]);
        }
        return view('dashboard', [
            'total' => Activity::count(), 'todayTotal' => Activity::whereDate('activity_date', $today)->count(), 'done' => Activity::where('status', 'Done')->count(),
            'pending' => Activity::where('status', 'Pending')->count(),
            'updatesToday' => ActivityUpdate::whereDate('created_at', $today)->count(),
            'activePersonnel' => User::where('is_active', true)->count(),
            'team' => User::where('is_active', true)->withCount('activityUpdates')->orderByDesc('activity_updates_count')->limit(4)->get(),
            'recent' => ActivityUpdate::with('activity')->latest()->limit(6)->get(),
            'pendingItems' => Activity::with(['latestUpdate', 'assignee', 'assignees'])->where('status', 'Pending')->orderByDesc('activity_date')->limit(5)->get(),
            'week' => collect(range(6, 0))->map(fn ($offset) => ['label' => $today->copy()->subDays($offset)->format('D'), 'date' => $today->copy()->subDays($offset)->format('M j'), 'done' => Activity::whereDate('activity_date', $today->copy()->subDays($offset))->where('status', 'Done')->count(), 'pending' => Activity::whereDate('activity_date', $today->copy()->subDays($offset))->where('status', 'Pending')->count()]),
        ]);
    }
}
