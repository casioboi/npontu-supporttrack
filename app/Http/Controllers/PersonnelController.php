<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ActivityUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class PersonnelController extends Controller
{
    public function index() { return view('personnel.index', ['users' => User::withCount(['createdActivities','activityUpdates'])->orderBy('name')->paginate(15), 'summary' => ['total' => User::count(), 'active' => User::where('is_active', true)->count(), 'admins' => User::where('role', 'admin')->where('is_active', true)->count(), 'updates' => ActivityUpdate::count()]]); }
    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required','string','max:255'], 'email' => ['required','email','max:255','unique:users,email'], 'password' => ['required','string','min:12','confirmed'], 'role' => ['required',Rule::in(['admin','staff'])]]);
        $data['password'] = Hash::make($data['password']);
        User::create($data + ['is_active' => true]);
        return back()->with('success','Personnel account created.');
    }
    public function toggle(User $user)
    {
        abort_if($user->is(request()->user()), 422, 'You cannot deactivate your own account.');
        abort_if($user->isAdmin() && User::where('role', 'admin')->where('is_active', true)->count() <= 1, 422, 'The last active administrator cannot be deactivated.');
        $user->update(['is_active' => ! $user->is_active]);
        return back()->with('success','Account status updated.');
    }
    public function password(Request $request, User $user)
    {
        $data = $request->validate(['password' => ['required','string','min:12','confirmed']]);
        $user->update(['password' => Hash::make($data['password'])]);
        return back()->with('success','Password reset successfully.');
    }

    public function update(Request $request, User $user)
    {
        abort_if($user->is($request->user()) && $request->input('role') !== 'admin', 422, 'You cannot remove your own administrator role.');
        $data = $request->validate(['name' => ['required','string','max:255'], 'email' => ['required','email','max:255',Rule::unique('users', 'email')->ignore($user->id)], 'role' => ['required',Rule::in(['admin','staff'])]]);
        abort_if($user->isAdmin() && $data['role'] === 'staff' && User::where('role', 'admin')->where('is_active', true)->count() <= 1, 422, 'The last active administrator cannot be demoted.');
        $user->update($data);
        return back()->with('success','Personnel details updated.');
    }
}
