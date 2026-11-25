<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrivacyPolicy;
use Illuminate\Http\Request;

class PrivacyPolicyController extends Controller
{
    public function privacyPolicy()
    {
        $privacyPolicy = PrivacyPolicy::first();
        return response()->json([
            'status' => true,
            'message' => 'Privacy policy fetched successfully',
            'data' => $privacyPolicy
        ]);
    }
}
