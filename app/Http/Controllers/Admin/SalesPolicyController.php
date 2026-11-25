<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalesPolicy;
use Illuminate\Http\Request;

class SalesPolicyController extends Controller
{
    public function index()
    {
        $salesPolicy = SalesPolicy::firstOrCreate([]);
        return view('admin.sales-policy.index', compact('salesPolicy'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'content' => 'nullable|string',
        ]);

        $salesPolicy = SalesPolicy::firstOrCreate([]);
        $salesPolicy->update([
            'content' => $request->content,
        ]);

        return redirect()->back()->with('success', 'Sales Policy updated successfully.');
    }
}
