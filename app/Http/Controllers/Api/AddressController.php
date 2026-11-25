<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Address;
use App\Models\BuyNow;
use App\Models\Cart;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    /**
     * Resolve authenticated user from Sanctum token, Bearer token, or request parameters
     */
    private function resolveUser(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            $rawToken = $request->bearerToken();

            if (!$rawToken) {
                $authHeader = $request->header('Authorization')
                    ?: $request->header('authorization')
                    ?: ($request->server('HTTP_AUTHORIZATION')
                        ?: ($request->server('REDIRECT_HTTP_AUTHORIZATION')
                            ?: (function_exists('apache_request_headers') ? (apache_request_headers()['Authorization'] ?? apache_request_headers()['authorization'] ?? null) : null)));

                if ($authHeader) {
                    $rawToken = $authHeader;
                }
            }

            if (!$rawToken) {
                $rawToken = $request->header('token')
                    ?: $request->header('Token')
                    ?: $request->header('auth_token')
                    ?: $request->header('auth-token')
                    ?: $request->header('x-auth-token')
                    ?: $request->header('x-token')
                    ?: $request->input('token')
                    ?: $request->input('auth_token')
                    ?: $request->input('access_token')
                    ?: $request->query('token')
                    ?: $request->query('auth_token')
                    ?: $request->cookie('token')
                    ?: $request->cookie('auth_token');
            }

            if ($rawToken) {
                $rawToken = trim($rawToken);
                if (preg_match('/^Bearer\s+(.*)$/i', $rawToken, $matches)) {
                    $rawToken = trim($matches[1]);
                }
                $rawToken = trim($rawToken, "\"' ");

                $accessToken = PersonalAccessToken::findToken($rawToken);
                if ($accessToken) {
                    $user = $accessToken->tokenable;
                }
            }
        }

        if (!$user) {
            $uid = $request->input('user_id')
                ?? $request->input('userId')
                ?? $request->header('user-id')
                ?? $request->header('user_id')
                ?? $request->header('userId')
                ?? $request->header('x-user-id')
                ?? $request->cookie('user_id')
                ?? $request->cookie('userId')
                ?? $request->input('user.id')
                ?? $request->input('userData.id');

            if (!empty($uid) && is_numeric($uid)) {
                $user = User::find($uid);
            }
        }

        if (!$user) {
            $sid = $request->header('session-id')
                ?: $request->header('session_id')
                ?: $request->input('session_id')
                ?: $request->query('session_id')
                ?: $request->cookie('session_id');

            if ($sid && str_starts_with($sid, 'user_')) {
                $extractedId = substr($sid, 5);
                if (is_numeric($extractedId)) {
                    $user = User::find($extractedId);
                }
            }
        }

        if (!$user) {
            $user = auth()->user();
        }

        return $user;
    }

    /**
     * Get all addresses for user (used in Cart and Buy Now page)
     */
    public function index(Request $request)
    {
        $user = $this->resolveUser($request);
        $userId = $user?->id;

        // If user is not authenticated/guest, return empty data gracefully without throwing 401 error
        if (!$userId) {
            return response()->json([
                'status' => true,
                'message' => 'No addresses found.',
                'user_id' => null,
                'selected_address_id' => null,
                'data' => []
            ], 200);
        }

        $candidates = [
            $request->header('session-id'),
            $request->header('Session-Id'),
            $request->header('session_id'),
            $request->header('Session_Id'),
            $request->header('sessionId'),
            $request->header('guest-id'),
            $request->header('guest_id'),
            $request->header('device-id'),
            $request->header('device_id'),
            $request->input('session_id'),
            $request->input('sessionId'),
            $request->input('session-id'),
            $request->input('guest_id'),
            $request->query('session_id'),
            $request->cookie('guest_session_id'),
            $request->cookie('session_id'),
            'user_' . $userId,
        ];

        $sessionId = null;
        foreach ($candidates as $cand) {
            if (!empty($cand) && is_string($cand)) {
                $sessionId = trim($cand);
                break;
            }
        }

        // Check which address is currently linked to Buy Now or Cart
        $selectedAddressId = BuyNow::where('user_id', $userId)
            ->whereNotNull('address_id')
            ->latest()
            ->value('address_id')
            ?: Cart::where('user_id', $userId)
                ->whereNotNull('address_id')
                ->latest()
                ->value('address_id');

        $addresses = Address::where('user_id', $userId)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($addr) use ($selectedAddressId) {
                $addr->is_selected = ($selectedAddressId && (int) $addr->id === (int) $selectedAddressId);
                return $addr;
            });

        return response()->json([
            'status' => true,
            'message' => 'User addresses fetched successfully',
            'user_id' => (int) $userId,
            'selected_address_id' => $selectedAddressId ? (int) $selectedAddressId : null,
            'data' => $addresses
        ], 200);
    }

    /**
     * Add a new address (used in Cart and Buy Now page)
     * Automatically links this address to the user's Buy Now and Cart items
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'            => 'required|string|max:255',
            'phone'           => 'required|string|max:25',
            'email'           => 'nullable|email|max:255',
            'alternate_phone' => 'nullable|string|max:25',
            'address'         => 'nullable|string',
            'flat'            => 'nullable|string|max:255',
            'area'            => 'nullable|string|max:255',
            'landmark'        => 'nullable|string|max:255',
            'city'            => 'required|string|max:100',
            'state'           => 'required|string|max:100',
            'country'         => 'nullable|string|max:100',
            'pincode'         => 'required|string|max:20',
            'type'            => 'nullable|string|max:100',
            'is_default'      => 'nullable|boolean',
            'status'          => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $this->resolveUser($request);
        $userId = $user?->id;

        if (!$userId) {
            return response()->json([
                'status' => false,
                'message' => 'User authentication is required to save an address.',
            ], 401);
        }

        $sessionId = $request->header('session-id')
            ?: $request->header('Session-Id')
            ?: $request->header('session_id')
            ?: $request->input('session_id')
            ?: $request->input('sessionId')
            ?: $request->input('session-id');

        // Build composite street/address string
        $street = $request->address;
        if (!$street) {
            $parts = array_filter([$request->flat, $request->area, $request->landmark]);
            $street = implode(', ', $parts);
        }
        if (!$street) {
            $street = $request->city;
        }

        // If this address is set as default, reset other default flags
        if ($request->boolean('is_default')) {
            Address::where('user_id', $userId)->update(['is_default' => 0]);
        }

        $address = Address::create([
            'user_id'         => $userId,
            'name'            => $request->name,
            'email'           => $request->email ?? ($user?->email ?? null),
            'phone'           => $request->phone,
            'alternate_phone' => $request->alternate_phone,
            'address'         => $street,
            'flat'            => $request->flat,
            'area'            => $request->area,
            'landmark'        => $request->landmark,
            'city'            => $request->city,
            'state'           => $request->state,
            'country'         => $request->country ?? 'India',
            'pincode'         => $request->pincode,
            'type'            => $request->type ?? 'home',
            'is_default'      => $request->boolean('is_default') ? 1 : 0,
            'status'          => $request->status ?? 1,
        ]);

        // ========================================================
        // 🔥 AUTO-LINK NEW ADDRESS TO BUY NOW & CART
        // ========================================================
        BuyNow::where('user_id', $userId)->update(['address_id' => $address->id]);
        Cart::where('user_id', $userId)->update(['address_id' => $address->id]);

        if ($sessionId) {
            BuyNow::where('session_id', $sessionId)->update(['address_id' => $address->id]);
            Cart::where('session_id', $sessionId)->update(['address_id' => $address->id]);
        }

        return response()->json([
            'status'     => true,
            'message'    => 'Address created and linked to Buy Now successfully',
            'address_id' => $address->id,
            'data'       => $address
        ], 201);
    }

    /**
     * Show single address
     */
    public function show($id)
    {
        $user = $this->resolveUser(request());
        $userId = $user?->id;

        $address = Address::where('id', $id)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->first();

        if (!$address) {
            return response()->json([
                'status' => false,
                'message' => 'Address not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $address
        ]);
    }

    /**
     * Update address or select an address for Buy Now / Cart
     */
    public function update(Request $request)
    {
        $addressId = $request->input('id') ?? $request->input('address_id');

        if (!$addressId) {
            return response()->json([
                'status' => false,
                'message' => 'Address ID is required',
            ], 422);
        }

        $user = $this->resolveUser($request);
        $userId = $user?->id;

        $sessionId = $request->header('session-id')
            ?: $request->header('Session-Id')
            ?: $request->header('session_id')
            ?: $request->input('session_id')
            ?: $request->input('sessionId')
            ?: $request->input('session-id');

        $address = Address::where('id', $addressId)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->first();

        if (!$address) {
            return response()->json([
                'status' => false,
                'message' => 'Address not found'
            ], 404);
        }

        if ($request->boolean('is_default') && $userId) {
            Address::where('user_id', $userId)->where('id', '!=', $address->id)->update(['is_default' => 0]);
        }

        $fields = [
            'name','email','phone','alternate_phone','address','flat','area',
            'landmark','city','state','country','pincode','type','is_default','status'
        ];

        $updateData = [];
        foreach ($fields as $field) {
            if ($request->has($field)) {
                $updateData[$field] = $request->$field;
            }
        }

        if (empty($updateData['address']) && ($request->has('flat') || $request->has('area') || $request->has('landmark'))) {
            $flat = $request->flat ?? $address->flat;
            $area = $request->area ?? $address->area;
            $landmark = $request->landmark ?? $address->landmark;
            $updateData['address'] = implode(', ', array_filter([$flat, $area, $landmark]));
        }

        if (!empty($updateData)) {
            $address->update($updateData);
        }

        // Auto-link to Buy Now & Cart when selected/active/default or explicitly requested
        if ($request->boolean('set_active') || $request->boolean('select_for_buy_now') || $request->boolean('is_default') || $request->has('set_active') || $request->has('address_id')) {
            if ($userId) {
                BuyNow::where('user_id', $userId)->update(['address_id' => $address->id]);
                Cart::where('user_id', $userId)->update(['address_id' => $address->id]);
            }
            if ($sessionId) {
                BuyNow::where('session_id', $sessionId)->update(['address_id' => $address->id]);
                Cart::where('session_id', $sessionId)->update(['address_id' => $address->id]);
            }
        }

        return response()->json([
            'status'     => true,
            'message'    => 'Address updated and selected for Buy Now successfully',
            'address_id' => $address->id,
            'data'       => $address->fresh()
        ]);
    }

    /**
     * Delete an address
     */
    public function destroy(Request $request)
    {
        $addressId = $request->input('id') ?? $request->input('address_id');

        if (!$addressId) {
            return response()->json([
                'status' => false,
                'message' => 'Address ID is required'
            ], 422);
        }

        $user = $this->resolveUser($request);
        $userId = $user?->id;

        $address = Address::where('id', $addressId)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->first();

        if (!$address) {
            return response()->json([
                'status' => false,
                'message' => 'Address not found'
            ], 404);
        }

        // If BuyNow or Cart was using this address, point to another user address or null
        if ($userId) {
            $nextAddressId = Address::where('user_id', $userId)
                ->where('id', '!=', $addressId)
                ->latest()
                ->value('id');

            BuyNow::where('user_id', $userId)->where('address_id', $addressId)->update(['address_id' => $nextAddressId]);
            Cart::where('user_id', $userId)->where('address_id', $addressId)->update(['address_id' => $nextAddressId]);
        }

        $address->delete();

        return response()->json([
            'status' => true,
            'message' => 'Address deleted successfully'
        ]);
    }
}