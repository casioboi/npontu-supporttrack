@extends('layouts.app')

@section('content')
@php($title = 'Activity details')
<div class="page-heading activity-detail-heading">
    <div>
        <div class="eyebrow"><a href="{{ route('activities.index') }}" class="crumb-link">ACTIVITIES</a><span class="crumb-separator">/</span> ACTIVITY RECORD</div>
        <h1>{{ $activity->title }}</h1>
        <div class="subtle">{{ $activity->activity_date->format('l, F j, Y') }} <span class="meta-divider">·</span> Created by {{ $activity->creator?->name ?? 'Former personnel' }} <span class="meta-divider">·</span> {{ $activity->created_at->format('M j, Y · g:i A') }}</div>
    </div>
    <span class="badge {{ strtolower($activity->status) }} detail-status">{{ $activity->status }}</span>
</div>

<div class="activity-detail-grid">
    @if($activity->latestUpdate && $activity->latestUpdate->user_id !== auth()->id())
        <div class="staff-collaboration-notice"><span class="collab-notice-icon">↻</span><span><b>Another staff member has updated this activity</b><i>Review the latest update below before submitting yours.</i></span></div>
    @endif

    <section class="card section detail-overview">
        <div class="detail-section-heading"><div><div class="panel-overline">AT A GLANCE</div><h2 class="section-title">Activity overview</h2></div><span class="detail-update-count">{{ $activity->updates->count() }} {{ Str::plural('update', $activity->updates->count()) }}</span></div>
        @if($activity->description)
            <p class="detail-description">{{ $activity->description }}</p>
        @else
            <p class="detail-description detail-description-empty">No additional description was provided for this activity.</p>
        @endif
        <div class="detail-meta-grid">
            <div class="detail-meta-item"><span><i>ASSIGNED TO</i><b>{{ $activity->assignee?->name ?? 'Unassigned' }}</b></span></div>
            <div class="detail-meta-item"><span><i>ACTIVITY DATE</i><b>{{ $activity->activity_date->format('M j, Y') }}</b></span></div>
            <div class="detail-meta-item"><span><i>CREATED BY</i><b>{{ $activity->creator?->name ?? 'Former personnel' }}</b></span></div>
            <div class="detail-meta-item"><span><i>CURRENT STATUS</i><b><span class="badge {{ strtolower($activity->status) }}">{{ $activity->status }}</span></b></span></div>
        </div>
        @if($activity->latestUpdate)
            <div class="latest-remark">
                <div class="latest-remark-top"><span class="latest-mark">LATEST UPDATE</span><span>{{ $activity->latestUpdate->created_at->format('M j · g:i:s A T') }}</span></div>
                <div class="latest-remark-text"><strong>Work done:</strong> {{ $activity->latestUpdate->work_done ?: $activity->latestUpdate->remark }}</div>
                @if($activity->latestUpdate->findings || $activity->latestUpdate->supporting_details)
                    <div class="supporting-context"><b>Findings</b><span>{{ $activity->latestUpdate->findings ?: $activity->latestUpdate->supporting_details }}</span></div>
                @endif
                @if($activity->latestUpdate->outstanding_issues)
                    <div class="supporting-context"><b>Outstanding issues</b><span>{{ $activity->latestUpdate->outstanding_issues }}</span></div>
                @endif
                @if($activity->latestUpdate->next_steps)
                    <div class="supporting-context"><b>Recommended next steps</b><span>{{ $activity->latestUpdate->next_steps }}</span></div>
                @endif
                <div class="latest-remark-by">By <b>{{ $activity->latestUpdate->personnel_name }}</b> · {{ $activity->latestUpdate->status }}</div>
            </div>
        @endif
    </section>

    @if(auth()->user()->isAdmin())
        <section class="card section detail-update-card admin-readonly-card"><div class="panel-overline">ADMINISTRATOR VIEW</div><h2 class="section-title">Activity history is read-only for administrators.</h2><p class="update-intro">Administrators can review activity progress and the complete audit trail. Status updates are submitted by authorised staff.</p></section>
    @else
        @php($updateStatus = old('status', $activity->status))
        <section class="card section detail-update-card">
            <div class="detail-section-heading"><div><div class="panel-overline">RECORD PROGRESS</div><h2 class="section-title">Add an update</h2></div><span class="required-note">* Required</span></div>
            <p class="update-intro">Work Done is always required. Pending updates also require Outstanding Issues and Recommended Next Steps. Findings are optional.</p>
            <form id="add-update" method="post" action="{{ route('activities.update', $activity) }}" data-busy>
                @csrf
                <input type="hidden" name="last_seen_update_id" value="{{ $activity->latestUpdate?->id ?? 0 }}">
                <div class="field"><label for="activity_name">Activity name</label><input class="input" id="activity_name" value="{{ $activity->title }}" readonly aria-readonly="true"></div>
                <div class="field">
                    <label for="status">Activity status <span class="required-star">*</span></label>
                    <select id="status" name="status" required>
                        <option value="Pending" @selected(old('status', $activity->status) === 'Pending')>Pending — follow-up remains</option>
                        <option value="Done" @selected(old('status', $activity->status) === 'Done')>Done — work completed</option>
                    </select>
                </div>
                <div class="field">
                    <label for="work_done">Work done <span class="required-star">*</span></label>
                    <textarea id="work_done" name="work_done" required minlength="2" maxlength="5000" placeholder="Describe the work you performed.">{{ old('work_done') }}</textarea>
                    <div class="remark-footer"><span class="field-hint">Required for every update.</span><span class="character-count" id="workDoneCount">0 / 5000</span></div>
                </div>
                <div class="field"><label for="findings">Findings <span class="muted">(optional)</span></label><textarea id="findings" name="findings" maxlength="5000" placeholder="Record observations, results, or discrepancies...">{{ old('findings') }}</textarea></div>
                <div class="field"><label for="outstanding_issues">Outstanding issues <span class="required-star" data-pending-required>*</span></label><textarea id="outstanding_issues" name="outstanding_issues" maxlength="5000" data-required-when-pending @required($updateStatus === 'Pending') aria-required="{{ $updateStatus === 'Pending' ? 'true' : 'false' }}" placeholder="Explain what remains unresolved...">{{ old('outstanding_issues') }}</textarea></div>
                <div class="field"><label for="next_steps">Recommended next steps <span class="required-star" data-pending-required>*</span></label><textarea id="next_steps" name="next_steps" maxlength="5000" data-required-when-pending @required($updateStatus === 'Pending') aria-required="{{ $updateStatus === 'Pending' ? 'true' : 'false' }}" placeholder="Explain precisely what the next staff member should do...">{{ old('next_steps') }}</textarea></div>
                <button class="button primary detail-submit"><span aria-hidden="true">＋</span> Add to activity history</button>
            </form>
        </section>
    @endif

    <section class="card section detail-history">
        <div class="detail-section-heading"><div><div class="panel-overline">AUDIT TRAIL</div><h2 class="section-title">Update history</h2></div><span class="history-order"><span>Oldest</span><span class="history-order-line"></span><span>Newest</span></span></div>
        <p class="history-intro">Updates are immutable and listed chronologically with the submitter's identity and server timestamp.</p>
        @forelse($activity->updates as $update)
            <article class="history-entry {{ $loop->last ? 'is-latest' : '' }}">
                <span class="history-node" aria-hidden="true"></span>
                <div class="history-entry-head">
                    <div class="history-person"><span class="history-avatar">{{ strtoupper(substr($update->personnel_name, 0, 1)) }}</span><span><b>{{ $update->personnel_name }}</b><i>{{ $update->personnel_email }} · {{ ucfirst($update->personnel_role ?? 'staff') }}</i></span></div>
                    <div class="history-entry-state"><time datetime="{{ $update->created_at->toIso8601String() }}">{{ $update->created_at->format('l, F j, Y · g:i:s A T') }}</time><span class="badge {{ strtolower($update->status) }}">{{ $update->status }}</span>@if($loop->last)<span class="badge latest-badge">LATEST</span>@endif</div>
                </div>
                <p class="history-remark"><b>Work done:</b> {{ $update->work_done ?: $update->remark }}</p>
                @if($update->findings || $update->supporting_details)<div class="history-supporting"><b>Findings</b> {{ $update->findings ?: $update->supporting_details }}</div>@endif
                @if($update->outstanding_issues)<div class="history-supporting"><b>Outstanding issues</b> {{ $update->outstanding_issues }}</div>@endif
                @if($update->next_steps)<div class="history-supporting"><b>Recommended next steps</b> {{ $update->next_steps }}</div>@endif
            </article>
        @empty
            <div class="history-empty"><span class="history-empty-icon">＋</span><b>No updates yet</b><i>The first update will appear here with its timestamp and personnel details.</i></div>
        @endforelse
    </section>
</div>

@push('scripts')
<script>
const workDoneField = document.getElementById('work_done');
const workDoneCount = document.getElementById('workDoneCount');
if (workDoneField && workDoneCount) {
    const updateWorkDoneCount = () => {
        workDoneCount.textContent = `${workDoneField.value.length} / 5000`;
        workDoneCount.classList.toggle('near-limit', workDoneField.value.length > 4500);
    };
    workDoneField.addEventListener('input', updateWorkDoneCount);
    updateWorkDoneCount();
}
const statusField = document.getElementById('status');
if (statusField) {
    const pendingFields = document.querySelectorAll('[data-required-when-pending]');
    const pendingIndicators = document.querySelectorAll('[data-pending-required]');
    const updatePendingRequirements = () => {
        const pending = statusField.value === 'Pending';
        pendingFields.forEach(field => {
            field.required = pending;
            field.setAttribute('aria-required', String(pending));
        });
        pendingIndicators.forEach(indicator => indicator.hidden = !pending);
    };
    statusField.addEventListener('change', updatePendingRequirements);
    updatePendingRequirements();
}
</script>
@endpush
@endsection
