<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LocationController extends Controller
{
    public function check(Request $request)
    {
        $ip = $request->ip();

        // Local development
        if (in_array($ip, ['127.0.0.1', '::1'])) {
            return response()->json([
                'status' => true,
                'country_code' => 'IN',
                'is_india' => true,
                'message' => 'India visitor'
            ]);
        }

        $cacheKey = 'visitor_location_' . md5($ip);

        $location = Cache::remember(
            $cacheKey,
            now()->addHours(24),
            function () use ($ip) {

                $response = Http::timeout(10)
                    ->get("https://ipwho.is/{$ip}");

                if (!$response->successful()) {
                    throw new \Exception(
                        'Location provider HTTP error: ' . $response->status()
                    );
                }

                $data = $response->json();

                if (
                    empty($data['success']) ||
                    empty($data['country_code'])
                ) {
                    throw new \Exception('Invalid location response');
                }

                return strtoupper($data['country_code']);
            }
        );

        return response()->json([
            'status' => true,
            'country_code' => $location,
            'is_india' => $location === 'IN',
            'message' => $location === 'IN'
                ? 'India visitor'
                : 'International visitor'
        ]);

    }
}