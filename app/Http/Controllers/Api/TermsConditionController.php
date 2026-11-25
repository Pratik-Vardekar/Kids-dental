<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TermsAndCondition;
use Illuminate\Http\Request;

class TermsConditionController extends Controller
{
    public function termsConditions()
    {
        $termsConditions = TermsAndCondition::first();
        return response()->json([
            'status' => true,
            'message' => 'Terms and conditions fetched successfully',
            'data' => $termsConditions
        ]);
    }
}
