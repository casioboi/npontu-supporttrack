@extends('layouts.app')

@section('content')
@php($title = 'Reports')
<div class="page-heading">
    <div><div class="eyebrow">HISTORICAL ACTIVITY · ANALYTICS</div><h1>Reports</h1><div class="subtle">{{ auth()->user()->isAdmin() ? 'Filter team activity and its update history for any reporting period.' : 'Review and export activities available to you and their progress.' }}</div></div>
    <a class="button primary" href="{{ route('reports.export', request()->query()) }}">↓ &nbsp;Export CSV</a>
</div>
<form class="card filters" method="get" data-busy>
    <div class="field"><label for="start_date">From activity date</label><input class="input" type="date" id="start_date" name="start_date" value="{{ $filters['start_date'] ?? '' }}"></div>
    <div class="field"><label for="end_date">To activity date / status as of</label><input class="input" type="date" id="end_date" name="end_date" value="{{ $filters['end_date'] ?? '' }}"></div>
    @if(auth()->user()->isAdmin())
        <div class="field"><label for="personnel">Personnel</label><select id="personnel" name="personnel"><option value="">Everyone</option>@foreach($personnel as $person)<option value="{{ $person->id }}" @selected(($filters['personnel'] ?? '') == $person->id)>{{ $person->name }}</option>@endforeach</select></div>
    @endif
    <div class="field"><label for="activity_name">Activity name</label><input class="input" type="search" id="activity_name" name="activity_name" value="{{ $filters['activity_name'] ?? '' }}" placeholder="Search activity title"></div>
    <div class="field"><label for="activity">Exact activity</label><select id="activity" name="activity"><option value="">All activities</option>@foreach($activityOptions as $option)<option value="{{ $option->id }}" @selected(($filters['activity'] ?? '') == $option->id)>{{ Str::limit($option->title, 45) }}</option>@endforeach</select></div>
    <div class="field"><label for="status">Status as of report date</label><select id="status" name="status"><option value="">Any status</option>@foreach(['Done', 'Pending', 'Awaiting Update'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>@endforeach</select></div>
    <div class="field"><label for="priority">Priority</label><select id="priority" name="priority"><option value="">All priorities</option>@foreach(\App\Models\Activity::PRIORITIES as $priority)<option value="{{ $priority }}" @selected(($filters['priority'] ?? '') === $priority)>{{ $priority }}</option>@endforeach</select></div>
    <button class="button primary">Run report</button><a class="button" href="{{ route('reports') }}">Reset</a>
</form>

<div class="stats">
    <div class="card stat"><div class="stat-label">Activities</div><div class="stat-value">{{ $total }}</div></div>
    <div class="card stat"><div class="stat-label">Completed</div><div class="stat-value" style="color:#18804a">{{ $done }}</div></div>
    <div class="card stat"><div class="stat-label">Pending</div><div class="stat-value" style="color:#b17616">{{ $pending }}</div></div>
    <div class="card stat"><div class="stat-label">Matching updates</div><div class="stat-value" style="color:#4269f5">{{ $updateCount }}</div></div>
</div>

<section class="card section" style="margin-bottom:16px">
    <h2 class="section-title">{{ auth()->user()->isAdmin() ? 'Personnel activity breakdown' : 'Personnel who submitted updates' }}</h2>
    @forelse($breakdown as $person)
        <div style="display:flex;align-items:center;gap:12px;margin:12px 0">
            <span class="activity-title" style="width:180px">{{ $person['name'] }}</span>
            <div style="height:7px;background:#e8edff;border-radius:8px;flex:1;overflow:hidden"><div style="height:100%;width:{{ $breakdown->max('count') ? max(4, $person['count'] / $breakdown->max('count') * 100) : 0 }}%;background:#5376f4;border-radius:8px"></div></div>
            <span class="activity-meta">{{ $person['count'] }} updates</span>
        </div>
    @empty
        <div class="muted">No updates match these filters.</div>
    @endforelse
</section>

<section class="card table-wrap">
    <table>
        <thead><tr><th>Activity</th><th>Date</th><th>Priority</th><th>Status at report date</th><th>Personnel</th><th>Updates</th><th>History</th></tr></thead>
        <tbody>
            @forelse($activities as $activity)
                <tr>
                    <td><a class="activity-title" style="text-decoration:none" href="{{ route('activities.show', $activity) }}">{{ $activity->title }}</a><div class="activity-meta">{{ Str::limit($activity->description, 75) }}</div></td>
                    <td>{{ $activity->activity_date->format('M j, Y') }}</td>
                    <td><span class="priority-badge priority-{{ strtolower($activity->priority) }}">{{ $activity->priority }}</span></td>
                    <td><span class="badge {{ $activity->report_status === 'Awaiting Update' ? 'admin' : strtolower($activity->report_status) }}">{{ $activity->report_status }}</span></td>
                    <td>{{ $activity->assignees->pluck('name')->implode(', ') ?: $activity->assignee?->name ?: 'Unassigned' }}</td>
                    <td>{{ $activity->updates->count() }}</td>
                    <td>
                        <details>
                            <summary class="activity-title" style="cursor:pointer">View history</summary>
                            <div style="padding:10px 0;min-width:240px">
                                @forelse($activity->updates as $update)
                                    <div style="padding:9px 0;border-bottom:1px solid #f0f2f6">
                                        <div class="activity-meta">{{ $update->created_at->format('M j, Y · g:i:s A T') }}</div>
                                        <div class="activity-title" style="margin:4px 0">{{ $update->personnel_name }} · {{ $update->status }}</div>
                                        <div class="timeline-remark"><b>Work done:</b> {{ $update->work_done ?: $update->remark }}</div>
                                        @if($update->findings || $update->supporting_details)<div class="history-result-support"><b>Findings:</b> {{ $update->findings ?: $update->supporting_details }}</div>@endif
                                        @if($update->outstanding_issues)<div class="history-result-support"><b>Outstanding issues:</b> {{ $update->outstanding_issues }}</div>@endif
                                        @if($update->next_steps)<div class="history-result-support"><b>Recommended next steps:</b> {{ $update->next_steps }}</div>@endif
                                    </div>
                                @empty
                                    <div class="activity-meta">No updates yet.</div>
                                @endforelse
                            </div>
                        </details>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty"><strong>No matching records</strong>Adjust the filters and run the report again.</div></td></tr>
            @endforelse
        </tbody>
    </table>
</section>
@endsection
