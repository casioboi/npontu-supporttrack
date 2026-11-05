<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    private function filtered(Request $request): array
    {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'personnel' => ['nullable', 'integer', 'exists:users,id'],
            'activity' => ['nullable', 'integer', 'exists:activities,id'],
            'activity_name' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:Done,Pending,Awaiting Update'],
            'priority' => ['nullable', Rule::in(Activity::PRIORITIES)],
        ]);
        $asOf = isset($filters['end_date'])
            ? Carbon::parse($filters['end_date'], config('app.timezone'))->endOfDay()
            : now();
        if (! $request->user()->isAdmin()) {
            unset($filters['personnel']);
        }

        $query = Activity::visibleTo($request->user())
            ->select('activities.*')
            ->addSelect([
                'report_status' => ActivityUpdate::query()
                    ->select('status')
                    ->whereColumn('activity_id', 'activities.id')
                    ->where('created_at', '<=', $asOf)
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->limit(1),
            ])
            ->with([
                'assignee',
                'assignees',
                'creator',
                'updates' => function ($updates) use ($asOf, $request) {
                    $updates->where('created_at', '<=', $asOf);
                    if (! $request->user()->isAdmin()) {
                        $updates->where('user_id', $request->user()->id);
                    }
                    $updates->orderBy('created_at')->orderBy('id');
                },
            ]);

        if (! empty($filters['start_date'])) $query->whereDate('activity_date', '>=', $filters['start_date']);
        if (! empty($filters['end_date'])) $query->whereDate('activity_date', '<=', $filters['end_date']);
        if (! empty($filters['activity'])) $query->whereKey($filters['activity']);
        if (! empty($filters['activity_name'])) $query->where('title', 'like', '%'.$filters['activity_name'].'%');
        if (! empty($filters['priority'])) $query->where('priority', $filters['priority']);
        if (! empty($filters['personnel'])) {
            $id = (int) $filters['personnel'];
            $query->where(fn ($activities) => $activities
                ->where('created_by', $id)
                ->orWhere('assigned_to', $id)
                ->orWhereHas('assignees', fn ($assignees) => $assignees->whereKey($id))
                ->orWhereHas('updates', fn ($updates) => $updates
                    ->where('user_id', $id)
                    ->where('created_at', '<=', $asOf)));
        }

        return [$query, $filters, $asOf];
    }

    private function results(Request $request): array
    {
        [$query, $filters] = $this->filtered($request);
        $activities = $query->orderBy('activity_date')->orderBy('id')->get()
            ->map(function (Activity $activity) {
                $activity->setAttribute('report_status', $activity->report_status ?? 'Awaiting Update');
                return $activity;
            })
            ->when(! empty($filters['status']), fn ($activities) => $activities
                ->where('report_status', $filters['status']))
            ->values();

        return [$activities, $filters];
    }

    public function index(Request $request)
    {
        [$activities, $filters] = $this->results($request);
        $updateCount = $activities->sum(fn ($activity) => $activity->updates->count());
        $breakdown = $activities->flatMap->updates
            ->groupBy('user_id')
            ->map(fn ($items) => ['name' => $items->first()->personnel_name, 'count' => $items->count()])
            ->values();

        return view('reports.index', [
            'activities' => $activities,
            'filters' => $filters,
            'personnel' => $request->user()->isAdmin() ? User::where('is_active', true)->orderBy('name')->get() : collect([$request->user()]),
            'activityOptions' => Activity::visibleTo($request->user())->orderByDesc('activity_date')->limit(100)->get(['id', 'title']),
            'total' => $activities->count(),
            'done' => $activities->where('report_status', 'Done')->count(),
            'pending' => $activities->where('report_status', 'Pending')->count(),
            'updateCount' => $updateCount,
            'breakdown' => $breakdown,
        ]);
    }

    public function export(Request $request)
    {
        [$activities] = $this->results($request);
        $filename = 'supporttrack-report-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($activities) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Activity date', 'Activity', 'Priority', 'Status as of report date', 'Created by', 'Assigned to', 'Update time', 'Updated by', 'Update status', 'Work done', 'Findings', 'Outstanding issues', 'Recommended next steps']);
            foreach ($activities as $activity) {
                if ($activity->updates->isEmpty()) {
                    fputcsv($out, [$activity->activity_date->toDateString(), $activity->title, $activity->priority, $activity->report_status, $activity->creator?->name, $activity->assignees->pluck('name')->implode(', ') ?: $activity->assignee?->name, '', '', '', '', '', '', '']);
                }
                foreach ($activity->updates as $update) {
                    fputcsv($out, [
                        $activity->activity_date->toDateString(),
                        $activity->title,
                        $activity->priority,
                        $activity->report_status,
                        $activity->creator?->name,
                        $activity->assignees->pluck('name')->implode(', ') ?: $activity->assignee?->name,
                        $update->created_at->toDateTimeString(),
                        $update->personnel_name,
                        $update->status,
                        $update->work_done ?: $update->remark,
                        $update->findings ?: $update->supporting_details,
                        $update->outstanding_issues,
                        $update->next_steps,
                    ]);
                }
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
