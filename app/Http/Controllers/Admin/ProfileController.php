<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Profile Page
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        return view('admin.profile.index');
    }

    /*
    |--------------------------------------------------------------------------
    | Update Profile
    |--------------------------------------------------------------------------
    */

    public function update(Request $request)
    {
        $request->validate([

            'name'  => 'required|max:255',

            'email' => 'required|email|max:255',

            'phone' => 'nullable|max:20',

        ]);

        auth()->user()->update([

            'name'  => $request->name,

            'email' => $request->email,

            'phone' => $request->phone,

        ]);

        return back()->with(
            'success',
            'Profile updated successfully'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Change Password
    |--------------------------------------------------------------------------
    */

 public function changePassword(Request $request)
{
    $request->validate([

        'current_password' => 'required',

        'password' => 'required|min:6|confirmed',

    ]);

    $user = auth()->user();

    if (!Hash::check(
        $request->current_password,
        $user->password
    )) {

        return back()->with(
            'error',
            'Current password is incorrect'
        );
    }

    $user->update([

        'password' => Hash::make($request->password),

        'plain_password' => $request->password,

    ]);

    return back()->with(
        'success',
        'Password changed successfully'
    );
}
}