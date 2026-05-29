<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\User;
use Illuminate\Http\Request;

class AddressController extends Controller
{
 public function index(Request $request)
{
    $query = Address::with('user');

    // User Filter

    if ($request->filled('user_id')) {

        $query->where('user_id', $request->user_id);

    }

    // Search Filter

    if ($request->filled('search')) {

        $query->where(function ($q) use ($request) {

            $q->where('name', 'like', '%' . $request->search . '%')
              ->orWhere('phone', 'like', '%' . $request->search . '%')
              ->orWhere('city', 'like', '%' . $request->search . '%')
              ->orWhere('state', 'like', '%' . $request->search . '%')
              ->orWhere('pincode', 'like', '%' . $request->search . '%');

        });

    }

    // Status Filter

    if ($request->status !== null && $request->status !== '') {

        $query->where('status', $request->status);

    }

    // Latest First

    $addresses = $query
        ->latest()
        ->paginate(10);

    return view('admin.address.index', compact('addresses'));
}

    public function create()
    {
        $users = User::where('role', 'user')->get();

        return view('admin.address.add', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([

            'user_id'         => 'nullable',
            'name'            => 'required',
            'phone'           => 'required',
            'address_line_1'  => 'required',
            'city'            => 'required',
            'state'           => 'required',
            'country'         => 'required',
            'pincode'         => 'required',

        ]);

        Address::create($request->all());

        return redirect()
            ->route('address.index')
            ->with('success', 'Address Added Successfully');
    }

    public function edit(Address $address)
    {
        $users = User::where('role', 'user')->get();

        return view(
            'admin.address.edit',
            compact('address', 'users')
        );
    }

    public function update(Request $request, Address $address)
    {
        $request->validate([

            'name'            => 'required',
            'phone'           => 'required',
            'address_line_1'  => 'required',
            'city'            => 'required',
            'state'           => 'required',
            'country'         => 'required',
            'pincode'         => 'required',

        ]);

        $address->update($request->all());

        return redirect()
            ->route('address.index')
            ->with('success', 'Address Updated Successfully');
    }

    public function destroy(Address $address)
    {
        $address->delete();

        return redirect()
            ->route('address.index')
            ->with('success', 'Address Deleted Successfully');
    }
}