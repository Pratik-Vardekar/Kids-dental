<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
   public function index(Request $request)
{
$query = User::withCount('addresses');
    // Search

    if ($request->filled('search')) {

        $query->where(function ($q) use ($request) {

            $q->where('name', 'like', '%' . $request->search . '%')
              ->orWhere('email', 'like', '%' . $request->search . '%')
              ->orWhere('phone', 'like', '%' . $request->search . '%');

        });
    }

    // Role Filter

    if ($request->filled('role')) {

        $query->where('role', $request->role);

    }

    // Status Filter

    if ($request->status !== null && $request->status !== '') {

        $query->where('status', $request->status);

    }

    // Sorting

    if ($request->sort == 'oldest') {

        $query->orderBy('created_at', 'ASC');

    } elseif ($request->sort == 'name_asc') {

        $query->orderBy('name', 'ASC');

    } elseif ($request->sort == 'name_desc') {

        $query->orderBy('name', 'DESC');

    } else {

        $query->orderBy('created_at', 'DESC');

    }

    $users = $query->paginate(10);

    return view('admin.user.index', compact('users'));
}
}