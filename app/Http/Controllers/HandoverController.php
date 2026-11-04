<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class HandoverController extends Controller
{
    public function __invoke(Request $request)
    {
        $filters = $request->validate([
            'date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:Done,Pending,Awaiting Update'],
            'personnel' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        $date = $filters['date'] ?? now()->toDateString();
        $dayStart = Carbon::parse($date, config('app.timezone'))->startOfDay();
        $dayEnd = $dayStart->copy()->endOfDay();
        $user = $request->user();
        $staffFilter = $user->isAdmin() ? ($filters['personnel'] ?? null) : null;

        $activities = Activity::query()
            ->with([
                'assignee',
                'creator',
                'updates' => fn ($updates) => $updates
                    ->where('created_at', '<=', $dayEnd)
                    ->orderBy('created_at')
                    ->orderBy('id'),
            ])
            ->whereDate('activity_date', '<=', $date)
            ->where('created_at', '<=', $dayEnd)
            ->orderBy('title')
            ->get()
            ->map(function (Activity $activity) use ($date, $dayStart, $dayEnd, $user, $staffFilter, $filters) {
                $activityUpdates = $activity->updates;
                $latestUpdate = $activityUpdates->last();
                $statusAtEndOfDay = $latestUpdate?->status ?? 'Awaiting Update';
                $createdOnDay = $activity->created_at >= $dayStart && $activity->created_at <= $dayEnd;
                $updatedOnDay = $activityUpdates->contains(fn ($update) =>
                    $update->created_at >= $dayStart && $update->created_at <= $dayEnd
                );
                $carriedForward = ! $createdOnDay;

                if (! $createdOnDay && ! $updatedOnDay && ! in_array($statusAtEndOfDay, ['Pending', 'Awaiting Update'], true)) {
                    return null;
                }

                if (! $user->isAdmin()
                    && $activity->assigned_to !== $user->id
                    && $activity->created_by !== $user->id
                    && ! in_array($statusAtEndOfDay, ['Pending', 'Awaiting Update'], true)
                    && ! $updatedOnDay) {
                    return null;
                }

                if ($staffFilter && $activity->created_by !== (int) $staffFilter
                    && $activity->assigned_to !== (int) $staffFilter
                    && ! $activityUpdates->contains(fn ($update) => $update->user_id === (int) $staffFilter)) {
                    return null;
                }

                if (! empty($filters['status']) && $statusAtEndOfDay !== $filters['status']) {
                    return null;
                }

                $activity->setAttribute('handover_status', $statusAtEndOfDay);
                $activity->setAttribute('handover_latest_update', $latestUpdate);
                $activity->setAttribute('is_carried_forward', $carriedForward);
                $activity->setAttribute('is_awaiting_update', $latestUpdate === null);
                $activity->setAttribute('can_submit_update', Gate::forUser($user)->allows('update', $activity));

                return $activity;
            })
            ->filter()
            ->sortBy(fn (Activity $activity) => match ($activity->handover_status) {
                'Pending' => '0-'.$activity->title,
                'Awaiting Update' => '1-'.$activity->title,
                default => '2-'.$activity->title,
            })
            ->values();

        return view('handover', [
            'activities' => $activities,
            'date' => $date,
            'personnel' => $user->isAdmin() ? User::orderBy('name')->get() : collect([$user]),
            'filters' => $filters,
            'staffView' => ! $user->isAdmin(),
            'carriedForwardCount' => $activities->where('is_carried_forward', true)->count(),
            'awaitingUpdateCount' => $activities->where('is_awaiting_update', true)->count(),
        ]);
    }
}
