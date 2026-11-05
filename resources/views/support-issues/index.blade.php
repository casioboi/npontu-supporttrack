@extends('layouts.app')

@section('content')
@php($title = 'Support issues')
<div class="page-heading">
    <div><div class="eyebrow">UNPLANNED WORK · ISSUE REGISTER</div><h1>Support issues</h1><div class="subtle">{{ auth()->user()->isAdmin() ? 'Review all reported support issues and coordinate their resolution.' : 'Track the unplanned support issues you have reported.' }}</div></div>
    <a class="button primary" href="{{ route('support-issues.create') }}">＋ &nbsp;Report an issue</a>
</div>
<section class="card table-wrap">
    <table>
        <thead><tr><th>Issue</th><th>Reported</th><th>Priority</th><th>Status</th><th>Reporter</th><th></th></tr></thead>
        <tbody>
        @forelse($reports as $report)
            <tr>
                <td><a class="activity-title" href="{{ route('support-issues.show', $report) }}">{{ $report->title }}</a>@if($report->affected_service)<div class="activity-meta">{{ $report->affected_service }}</div>@endif</td>
                <td>{{ $report->created_at->format('M j, Y · g:i A') }}</td>
                <td><span class="priority-badge priority-{{ strtolower($report->priority) }}">{{ $report->priority }}</span></td>
                <td><span class="badge {{ $report->status === 'Resolved' ? 'done' : ($report->status === 'In Progress' ? 'admin' : 'pending') }}">{{ $report->status }}</span></td>
                <td>{{ $report->reporter?->name ?? 'Former personnel' }}</td>
                <td><a class="button small" href="{{ route('support-issues.show', $report) }}">Open</a></td>
            </tr>
        @empty
            <tr><td colspan="6"><div class="empty"><strong>No support issues reported</strong>Use “Report an issue” to record an unplanned operational problem.</div></td></tr>
        @endforelse
        </tbody>
    </table>
</section>
<div style="margin-top:16px">{{ $reports->links() }}</div>
@endsection
