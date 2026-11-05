@extends('layouts.app')

@section('content')
@php($title = 'Support issue')
<div class="page-heading"><div><div class="eyebrow"><a class="crumb-link" href="{{ route('support-issues.index') }}">SUPPORT ISSUES</a><span class="crumb-separator">/</span> REPORT #{{ $report->id }}</div><h1>{{ $report->title }}</h1><div class="subtle">Reported by {{ $report->reporter?->name ?? 'Former personnel' }} on {{ $report->created_at->format('M j, Y · g:i A') }}</div></div><a class="button" href="{{ route('support-issues.index') }}">Back to reports</a></div>
<div class="activity-detail-grid">
    <section class="card section">
        <div class="detail-meta-grid">
            <div class="detail-meta-item"><span><i>STATUS</i><b>{{ $report->status }}</b></span></div>
            <div class="detail-meta-item"><span><i>PRIORITY</i><b><span class="priority-badge priority-{{ strtolower($report->priority) }}">{{ $report->priority }}</span></b></span></div>
            <div class="detail-meta-item"><span><i>AFFECTED SERVICE</i><b>{{ $report->affected_service ?: 'Not specified' }}</b></span></div>
        </div>
        <h2 class="section-title">Issue details</h2>
        <p class="detail-description" style="white-space:pre-wrap">{{ $report->description }}</p>
        @if($report->resolution_notes)<div class="supporting-context"><b>Resolution notes</b><span style="white-space:pre-wrap">{{ $report->resolution_notes }}</span></div>@endif
    </section>
    @if(auth()->user()->isAdmin())
        <section class="card section">
            <div class="panel-overline">ADMINISTRATOR ACTION</div><h2 class="section-title">Update issue status</h2>
            <form method="post" action="{{ route('support-issues.status', $report) }}" data-busy>
                @csrf @method('PATCH')
                <div class="field"><label for="status">Status</label><select class="input" id="status" name="status" required>@foreach(\App\Models\SupportIssueReport::STATUSES as $status)<option value="{{ $status }}" @selected(old('status', $report->status) === $status)>{{ $status }}</option>@endforeach</select>@error('status')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
                <div class="field"><label for="resolution_notes">Resolution notes <span class="muted">(required when resolved)</span></label><textarea class="input" id="resolution_notes" name="resolution_notes" rows="5" maxlength="5000">{{ old('resolution_notes', $report->resolution_notes) }}</textarea>@error('resolution_notes')<p class="field-error" role="alert">{{ $message }}</p>@enderror</div>
                <button class="button primary" type="submit">Save status</button>
            </form>
        </section>
    @endif
</div>
@endsection
