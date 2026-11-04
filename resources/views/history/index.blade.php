@extends('layouts.app')

@section('content')
@php($title = 'Activity History')
@push('styles')<link rel="stylesheet" href="{{ asset('css/staff.css') }}">@endpush
<div class="page-heading">
    <div><div class="eyebrow">AUDIT TRAIL · SUPPORT OPERATIONS</div><h1>{{ auth()->user()->isAdmin() ? 'Activity History' : 'My Activity History' }}</h1><div class="subtle">{{ auth()->user()->isAdmin() ? 'Search remarks and status changes across the support team.' : 'Search your activity updates and status changes.' }}</div></div>
</div>
<form class="card filters" method="get" data-busy>
    <div class="field"><label for="start_date">From date</label><input class="input" type="date" id="start_date" name="start_date" value="{{ $filters['start_date'] ?? '' }}"></div>
    <div class="field"><label for="end_date">To date</label><input class="input" type="date" id="end_date" name="end_date" value="{{ $filters['end_date'] ?? '' }}"></div>
    <div class="field search"><label for="activity">Activity name</label><input class="input" id="activity" name="activity" value="{{ $filters['activity'] ?? '' }}" placeholder="Search activity title"></div>
    <div class="field"><label for="status">Update status</label><select id="status" name="status"><option value="">Any status</option>@foreach(['Done', 'Pending'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>@endforeach</select></div>
    @if(auth()->user()->isAdmin())
        <div class="field"><label for="personnel">Updated by</label><select id="personnel" name="personnel"><option value="">Anyone</option>@foreach($personnel as $member)<option value="{{ $member->id }}" @selected(($filters['personnel'] ?? '') == $member->id)>{{ $member->name }}</option>@endforeach</select></div>
    @endif
    <button class="button primary" type="submit">Search history</button><a class="button" href="{{ route('activity-history') }}">Clear</a>
</form>
<div class="history-results-head"><span>{{ $updates->total() }} update records</span><span>Oldest to newest on this page</span></div>
<section class="history-results">
    @forelse($updates as $update)
        <article class="history-result">
            <span class="history-result-node" aria-hidden="true"></span>
            <div class="history-result-head">
                <div><a class="history-result-activity" href="{{ route('activities.show', $update->activity) }}">{{ $update->activity->title }}</a><div class="history-result-person">{{ $update->personnel_name }} · {{ $update->personnel_email }} · {{ ucfirst($update->personnel_role ?? 'staff') }}</div></div>
                <div class="history-result-meta"><span class="badge {{ strtolower($update->status) }}">{{ $update->status }}</span><time datetime="{{ $update->created_at->toIso8601String() }}">{{ $update->created_at->format('M j, Y · g:i:s A T') }}</time></div>
            </div>
            <p><strong>Work done:</strong> {{ $update->work_done ?: $update->remark }}</p>
            @if($update->findings || $update->supporting_details)<div class="history-result-support"><b>Findings:</b> {{ $update->findings ?: $update->supporting_details }}</div>@endif
            @if($update->outstanding_issues)<div class="history-result-support"><b>Outstanding issues:</b> {{ $update->outstanding_issues }}</div>@endif
            @if($update->next_steps)<div class="history-result-support"><b>Recommended next steps:</b> {{ $update->next_steps }}</div>@endif
        </article>
    @empty
        <div class="empty"><strong>No matching updates</strong>Try a wider date range or another filter.</div>
    @endforelse
</section>
<div class="history-pagination">{{ $updates->links() }}</div>
@endsection
