<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityUpdate;
use App\Models\User;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function __invoke(Request $request)
    {
        $filters = $request->validate([
            'start_date' => ['nullable','date'],
            'end_date' => ['nullable','date','after_or_equal:start_date'],
            'activity' => ['nullable','string','max:255'],
            'status' => ['nullable','in:Done,Pending'],
            'personnel' => ['nullable','integer','exists:users,id'],
        ]);

        $updates = ActivityUpdate::with('activity')
            ->whereHas('activity', fn ($query) => $query->visibleTo($request->user()))
            ->when(!empty($filters['start_date']), fn ($query) => $query->whereDate('created_at', '>=', $filters['start_date']))
            ->when(!empty($filters['end_date']), fn ($query) => $query->whereDate('created_at', '<=', $filters['end_date']))
            ->when(!empty($filters['activity']), fn ($query) => $query->whereHas('activity', fn ($activity) => $activity->where('title', 'like', '%'.$filters['activity'].'%')))
            ->when(!empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(!empty($filters['personnel']) && $request->user()->isAdmin(), fn ($query) => $query->where('user_id', $filters['personnel']))
            ->when(! $request->user()->isAdmin(), fn ($query) => $query->where('user_id', $request->user()->id))
            ->orderBy('created_at')->orderBy('id')->paginate(20)->withQueryString();

        return view('history.index', [
            'updates' => $updates,
            'filters' => $filters,
            'personnel' => $request->user()->isAdmin() ? User::where('is_active', true)->orderBy('name')->get() : collect([$request->user()]),
        ]);
    }
}
