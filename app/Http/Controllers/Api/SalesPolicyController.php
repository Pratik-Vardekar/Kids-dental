<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesPolicy;
use Illuminate\Http\Request;

class SalesPolicyController extends Controller
{
    public function salesPolicy()
    {
        $salesPolicy = SalesPolicy::first();
        return response()->json([
            'status' => true,
            'message' => 'Sales policy fetched successfully',
            'data' => $salesPolicy
        ]);
    }
}
