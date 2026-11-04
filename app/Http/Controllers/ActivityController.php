<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::visibleTo($request->user())->with(['assignee', 'creator', 'latestUpdate'])->latest('activity_date')->latest('id');
        if ($request->filled('search')) $query->where(fn ($q) => $q->where('title', 'like', '%'.$request->string('search').'%')->orWhere('description', 'like', '%'.$request->string('search').'%'));
        if ($request->filled('date')) $query->whereDate('activity_date', $request->date);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('assigned_to')) $query->where('assigned_to', $request->integer('assigned_to'));
        return view('activities.index', ['activities' => $query->paginate(12)->withQueryString(), 'personnel' => $request->user()->isAdmin() ? User::where('is_active', true)->orderBy('name')->get() : User::whereKey($request->user()->id)->get(), 'staffView' => ! $request->user()->isAdmin()]);
    }
    public function myActivities(Request $request)
    {
        if (! $request->filled('date')) $request->merge(['date' => now()->toDateString()]);
        return $this->index($request);
    }
    public function create(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $filters = $request->validate(['activity_date' => ['nullable', 'date']]);

        return view('activities.create', [
            'personnel' => User::where('is_active', true)->orderBy('name')->get(),
            'activityDate' => $filters['activity_date'] ?? null,
        ]);
    }
    public function store(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate(['title' => ['required','string','max:255'], 'description' => ['nullable','string','max:10000'], 'activity_date' => ['required','date'], 'assigned_to' => ['nullable', Rule::exists('users', 'id')->where('is_active', true)]]);
        $data['created_by'] = $request->user()->id;
        $data['status'] = 'Pending';
        $activity = Activity::create($data);
        return redirect()->route('activities.show', $activity)->with('success', 'Activity created.');
    }
    public function show(Activity $activity)
    {
        Gate::authorize('view', $activity);
        $activity->load(['creator','assignee','updates','latestUpdate']);
        return view('activities.show', compact('activity'));
    }
    public function update(Request $request, Activity $activity)
    {
        Gate::authorize('update', $activity);
        $pending = $request->input('status') === 'Pending';
        $data = $request->validate([
            'status' => ['required', 'in:Done,Pending'],
            'work_done' => ['required', 'string', 'min:2', 'max:5000'],
            'findings' => ['nullable', 'string', 'max:5000'],
            'outstanding_issues' => [$pending ? 'required' : 'nullable', 'string', 'max:5000'],
            'next_steps' => [$pending ? 'required' : 'nullable', 'string', 'max:5000'],
            'last_seen_update_id' => ['nullable', 'integer', 'min:0'],
        ]);
        $expectedUpdateId = (int) ($data['last_seen_update_id'] ?? 0);
        unset($data['last_seen_update_id']);
        DB::transaction(function () use ($request, $activity, $data, $expectedUpdateId) {
            $locked = Activity::whereKey($activity->id)->lockForUpdate()->firstOrFail();
            $latestUpdateId = (int) ($locked->updates()->max('id') ?? 0);
            if ($latestUpdateId !== $expectedUpdateId) {
                throw ValidationException::withMessages(['remark' => 'Another staff member has updated this activity. Review the latest note, then submit your update again.']);
            }
            $user = $request->user();
            $locked->updates()->create([
                'user_id' => $user->id,
                'personnel_name' => $user->name,
                'personnel_email' => $user->email,
                'personnel_role' => $user->role,
                'status' => $data['status'],
                'remark' => $data['work_done'],
                'work_done' => $data['work_done'],
                'findings' => $data['findings'] ?? null,
                'outstanding_issues' => $data['outstanding_issues'] ?? null,
                'next_steps' => $data['next_steps'] ?? null,
            ]);
            $locked->update(['status' => $data['status']]);
        });
        return redirect()->route('activities.show', $activity)->with('success', 'Update added to the activity history.');
    }
}
