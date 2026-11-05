<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        return view('profile.index', ['user' => $request->user()]);
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required','current_password'],
            'password' => ['required','string','min:12','confirmed'],
        ]);

        $request->user()->update(['password' => $data['password'], 'must_change_password' => false]);
        return back()->with('success', 'Your password has been changed.');
    }
}
