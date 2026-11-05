@extends('layouts.app')

@section('content')
@push('after-styles')<link rel="stylesheet" href="{{ asset('css/handover.css') }}">@endpush
@php
    $title = 'Daily Handover';
    $selectedDate = \Carbon\Carbon::parse($date, config('app.timezone'));
    $pendingItems = $activities->where('handover_status', 'Pending');
    $doneItems = $activities->where('handover_status', 'Done');
    $handoverFilters = array_filter([
        'status' => $filters['status'] ?? null,
        'priority' => $filters['priority'] ?? null,
        'personnel' => $staffView ? null : ($filters['personnel'] ?? null),
    ], fn ($value) => filled($value));
@endphp

<div class="page-heading handover-page-heading">
    <div class="handover-heading-copy">
        <div class="eyebrow">SHIFT TRANSITION · OPERATIONS BRIEF</div>
        <h1>Daily Handover</h1>
        <div class="subtle">A date-specific record of activity, progress, outstanding work, and the next action.</div>
        <div class="handover-selected-date">Handover for <strong>{{ $selectedDate->format('l, F j, Y') }}</strong></div>
    </div>
    @if(! $staffView)
        <a class="button primary handover-create-link" href="{{ route('activities.create', ['activity_date' => $date]) }}">＋ &nbsp;Add activity for this day</a>
    @endif
</div>

<div class="handover-date-tools">
    <a class="button handover-step" href="{{ route('handover', ['date' => $selectedDate->copy()->subDay()->toDateString()] + $handoverFilters) }}" aria-label="Previous day, {{ $selectedDate->copy()->subDay()->format('F j, Y') }}">
        <span aria-hidden="true">←</span><span>Previous day</span><small>{{ $selectedDate->copy()->subDay()->format('M j') }}</small>
    </a>
    <form class="toolbar handover-filter-form" method="get" data-busy>
        <div class="field">
            <label for="date">Handover date</label>
            <input class="input" type="date" id="date" name="date" value="{{ $date }}" required>
        </div>
        <div class="field">
            <label for="status_filter">Status</label>
            <select class="input" id="status_filter" name="status">
                <option value="">All statuses</option>
                @foreach(['Pending', 'Done', 'Awaiting Update'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="priority_filter">Priority</label>
            <select class="input" id="priority_filter" name="priority">
                <option value="">All priorities</option>
                @foreach(\App\Models\Activity::PRIORITIES as $priority)
                    <option value="{{ $priority }}" @selected(($filters['priority'] ?? '') === $priority)>{{ $priority }}</option>
                @endforeach
            </select>
        </div>
        @if(! $staffView)
            <div class="field">
                <label for="personnel_filter">Personnel</label>
                <select class="input" id="personnel_filter" name="personnel">
                    <option value="">Anyone</option>
                    @foreach($personnel as $person)
                        <option value="{{ $person->id }}" @selected(($filters['personnel'] ?? '') == $person->id)>
                            {{ $person->name }}{{ $person->is_active ? '' : ' (inactive)' }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif
        <button class="button primary" type="submit">View day</button>
    </form>
    <a class="button handover-today" href="{{ route('handover', ['date' => now()->toDateString()] + $handoverFilters) }}">Today</a>
    <a class="button handover-step" href="{{ route('handover', ['date' => $selectedDate->copy()->addDay()->toDateString()] + $handoverFilters) }}" aria-label="Next day, {{ $selectedDate->copy()->addDay()->format('F j, Y') }}">
        <span>Next day</span><span aria-hidden="true">→</span><small>{{ $selectedDate->copy()->addDay()->format('M j') }}</small>
    </a>
</div>

<section class="handover-stats" aria-label="Handover summary">
    <div class="card stat"><div class="stat-label">Total activities</div><div class="stat-value">{{ $activities->count() }}</div></div>
    <div class="card stat"><div class="stat-label">Completed</div><div class="stat-value handover-done-count">{{ $doneItems->count() }}</div></div>
    <div class="card stat"><div class="stat-label">Pending</div><div class="stat-value handover-pending-count">{{ $pendingItems->count() }}</div></div>
    <div class="card stat"><div class="stat-label">Carried forward</div><div class="stat-value">{{ $carriedForwardCount }}</div></div>
    <div class="card stat"><div class="stat-label">Awaiting updates</div><div class="stat-value">{{ $awaitingUpdateCount }}</div></div>
</section>

<section class="card section handover-activity-section" aria-labelledby="handover-activities-title">
    <div class="handover-section-heading">
        <div><div class="panel-overline">DAILY ACTIVITY REGISTER</div><h2 class="section-title" id="handover-activities-title">Activities for {{ $selectedDate->format('M j') }}</h2></div>
        <span class="handover-total-label">{{ $activities->count() }} {{ Str::plural('activity', $activities->count()) }}</span>
    </div>

    @forelse($activities as $activity)
        @php($status = $activity->handover_status)
        @php($latest = $activity->handover_latest_update)
        @php($workDone = $latest?->work_done ?: $latest?->remark)
        <article class="handover-record {{ $status === 'Pending' ? 'handover-record-pending' : '' }}">
            <div class="handover-record-heading">
                <div>
                    <div class="handover-record-labels">
                        <span class="handover-origin">{{ $activity->is_carried_forward ? 'Carried forward' : 'New activity' }}</span>
                        <span class="handover-reference">Activity #{{ $activity->id }}</span>
                        <span class="priority-badge priority-{{ strtolower($activity->priority) }}">{{ $activity->priority }} priority</span>
                    </div>
                    <h3><a href="{{ route('activities.show', $activity) }}">{{ $activity->title }}</a></h3>
                    <p class="handover-assignee">{{ $activity->assignees->pluck('name')->implode(', ') ?: ($activity->assignee?->name ? 'Assigned to '.$activity->assignee->name : 'Unassigned team activity') }}</p>
                </div>
                <span class="badge {{ $status === 'Awaiting Update' ? 'awaiting-update' : strtolower($status) }}">{{ $status }}</span>
            </div>

            @if($activity->description)<p class="handover-activity-description">{{ $activity->description }}</p>@endif

            <div class="handover-record-meta">
                <span>Activity date <strong>{{ $activity->activity_date->format('M j, Y') }}</strong></span>
                <span>Record created <strong>{{ $activity->created_at->format('M j, Y · g:i A T') }}</strong></span>
                @if($latest)
                    <span>Last updated by <strong>{{ $latest->personnel_name }}</strong></span>
                    <time datetime="{{ $latest->created_at->toIso8601String() }}">Updated {{ $latest->created_at->format('M j, Y · g:i:s A T') }}</time>
                @else
                    <span>Last updated by <strong>—</strong></span>
                    <span>No update has been submitted</span>
                @endif
            </div>

            @if($latest)
                <div class="handover-latest-details">
                    <div class="handover-detail-block">
                        <strong>Work done</strong>
                        <p>{{ $workDone ?: 'No work details recorded.' }}</p>
                    </div>
                    @if($latest->findings || $latest->supporting_details)
                        <div class="handover-detail-block">
                            <strong>Findings</strong>
                            <p>{{ $latest->findings ?: $latest->supporting_details }}</p>
                        </div>
                    @endif
                    @if($latest->outstanding_issues)
                        <div class="handover-detail-block handover-detail-attention">
                            <strong>Outstanding issues</strong>
                            <p>{{ $latest->outstanding_issues }}</p>
                        </div>
                    @endif
                    @if($latest->next_steps)
                        <div class="handover-detail-block">
                            <strong>Recommended next steps</strong>
                            <p>{{ $latest->next_steps }}</p>
                        </div>
                    @endif
                </div>
            @else
                <div class="handover-detail-block handover-awaiting-note">
                    <strong>Awaiting first update</strong>
                    <p>This activity has no submitted progress yet.</p>
                </div>
            @endif

            <details class="handover-history">
                <summary>View full activity history <span>({{ $activity->updates->count() }} {{ Str::plural('update', $activity->updates->count()) }} through {{ $selectedDate->format('M j') }})</span></summary>
                <div class="timeline">
                    @forelse($activity->updates as $update)
                        <article class="timeline-item">
                            <div class="timeline-meta"><time datetime="{{ $update->created_at->toIso8601String() }}">{{ $update->created_at->format('l, F j, Y · g:i:s A T') }}</time> @if($loop->last)<span class="badge admin">LATEST ON THIS DATE</span>@endif</div>
                            <div class="timeline-name">{{ $update->personnel_name }} · {{ $update->personnel_email }} · {{ ucfirst($update->personnel_role ?? 'staff') }}</div>
                            <span class="badge {{ strtolower($update->status) }}">{{ $update->status }}</span>
                            <p class="timeline-remark"><strong>Work done:</strong> {{ $update->work_done ?: $update->remark }}</p>
                            @if($update->findings || $update->supporting_details)<p class="timeline-remark"><strong>Findings:</strong> {{ $update->findings ?: $update->supporting_details }}</p>@endif
                            @if($update->outstanding_issues)<p class="timeline-remark"><strong>Outstanding issues:</strong> {{ $update->outstanding_issues }}</p>@endif
                            @if($update->next_steps)<p class="timeline-remark"><strong>Recommended next steps:</strong> {{ $update->next_steps }}</p>@endif
                        </article>
                    @empty
                        <div class="handover-no-updates"><strong>No updates recorded yet</strong><span>This activity is awaiting its first progress update.</span></div>
                    @endforelse
                </div>
            </details>

            <div class="handover-record-actions">
                <a class="button small" href="{{ route('activities.show', $activity) }}">Review activity &amp; history <span aria-hidden="true">→</span></a>
                @if($activity->can_submit_update)
                    <a class="button small primary" href="{{ route('activities.show', $activity) }}#add-update">Add an update</a>
                @endif
            </div>
        </article>
    @empty
        <div class="empty handover-empty">
            <strong>No activities for this date</strong>
            <span>Try another date or adjust the selected filters.</span>
            @unless($staffView)<a class="button primary" href="{{ route('activities.create', ['activity_date' => $date]) }}">＋ &nbsp;Create activity for this day</a>@endunless
        </div>
    @endforelse
</section>
@endsection
