<?php

namespace App\Http\Controllers;

use App\Models\SupportIssueReport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupportIssueReportController extends Controller
{
    public function index(Request $request)
    {
        $reports = SupportIssueReport::query()
            ->with('reporter')
            ->when(! $request->user()->isAdmin(), fn ($query) => $query->where('reported_by', $request->user()->id))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('support-issues.index', ['reports' => $reports]);
    }

    public function create()
    {
        return view('support-issues.create', ['priorities' => SupportIssueReport::PRIORITIES]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'affected_service' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:10', 'max:10000'],
            'priority' => ['required', Rule::in(SupportIssueReport::PRIORITIES)],
        ]);
        $data['reported_by'] = $request->user()->id;
        $report = SupportIssueReport::create($data);

        return redirect()->route('support-issues.show', $report)
            ->with('success', 'Support issue report submitted.');
    }

    public function show(Request $request, SupportIssueReport $supportIssue)
    {
        abort_unless(
            $request->user()->isAdmin() || $supportIssue->reported_by === $request->user()->id,
            404,
        );
        $supportIssue->load('reporter');

        return view('support-issues.show', ['report' => $supportIssue]);
    }

    public function updateStatus(Request $request, SupportIssueReport $supportIssue)
    {
        $status = $request->input('status');
        $data = $request->validate([
            'status' => ['required', Rule::in(SupportIssueReport::STATUSES)],
            'resolution_notes' => [$status === 'Resolved' ? 'required' : 'nullable', 'string', 'max:5000'],
        ]);
        $supportIssue->update($data);

        return redirect()->route('support-issues.show', $supportIssue)
            ->with('success', 'Support issue status updated.');
    }
}
