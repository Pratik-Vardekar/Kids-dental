<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Address;
use App\Models\BuyNow;
use App\Models\Cart;
use App\Models\City;
use App\Models\State;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Schema;
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
     * All-in-one API: Returns addresses, State Dropdown (all 28 Indian States & 8 Union Territories sorted A-Z),
     * and City Dropdown based on selected state, without creating separate APIs.
     */
    public function index(Request $request)
    {
        $user = $this->resolveUser($request);
        $userId = $user?->id;

        // 1. Fetch all 28 states and 8 union territories sorted alphabetically with their nested cities
        $states = $this->fetchIndianStatesFromDatabase();

        // 2. Build cities_by_state dictionary { "Maharashtra": ["Mumbai", ...], ... } for instant lookup
        $citiesByState = [];
        foreach ($states as $st) {
            $citiesByState[$st['name']] = $st['cities'] ?? [];
        }

        // 3. Check requested state from query (e.g. ?state=Maharashtra)
        $requestedState = $request->input('state')
            ?? $request->input('state_name')
            ?? $request->input('state_id');

        // If guest (not authenticated)
        if (!$userId) {
            $selectedStateName = null;
            $selectedCities = [];

            if (!empty($requestedState)) {
                $cityInfo = $this->fetchCitiesFromDatabase($requestedState);
                $selectedStateName = $cityInfo['state_name'];
                $selectedCities = $cityInfo['cities'];
            }

            return response()->json([
                'status'              => true,
                'message'             => 'No addresses found.',
                'user_id'             => null,
                'selected_address_id' => null,
                'country'             => 'India',
                'country_code'        => 'IN',
                'states'              => $states,
                'state_dropdown'      => $states,
                'selected_state'      => $selectedStateName,
                'cities'              => $selectedCities,
                'city_dropdown'       => $selectedCities,
                'cities_by_state'     => $citiesByState,
                'data'                => []
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

        // Determine active state: query param takes priority, else user's selected/default address state
        $selectedStateName = null;
        $selectedCities = [];

        if (!empty($requestedState)) {
            $cityInfo = $this->fetchCitiesFromDatabase($requestedState);
            $selectedStateName = $cityInfo['state_name'];
            $selectedCities = $cityInfo['cities'];
        } else {
            // Find state from selected address or first address
            $activeAddr = $addresses->firstWhere('is_selected', true) ?: $addresses->first();
            if ($activeAddr && !empty($activeAddr->state)) {
                $cityInfo = $this->fetchCitiesFromDatabase($activeAddr->state);
                $selectedStateName = $cityInfo['state_name'];
                $selectedCities = $cityInfo['cities'];
            }
        }

        return response()->json([
            'status'              => true,
            'message'             => 'User addresses fetched successfully',
            'user_id'             => (int) $userId,
            'selected_address_id' => $selectedAddressId ? (int) $selectedAddressId : null,
            'country'             => 'India',
            'country_code'        => 'IN',
            'states'              => $states,
            'state_dropdown'      => $states,
            'selected_state'      => $selectedStateName,
            'cities'              => $selectedCities,
            'city_dropdown'       => $selectedCities,
            'cities_by_state'     => $citiesByState,
            'data'                => $addresses
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
            'name',
            'email',
            'phone',
            'alternate_phone',
            'address',
            'flat',
            'area',
            'landmark',
            'city',
            'state',
            'country',
            'pincode',
            'type',
            'is_default',
            'status'
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

    /**
     * Query all 28 states & 8 Union Territories from the database, sorted alphabetically.
     * Falls back to built-in dataset if DB table is pending migration.
     */
    protected function fetchIndianStatesFromDatabase(): array
    {
        try {
            if (class_exists(State::class) && Schema::hasTable('states')) {
                $dbStates = State::where('status', 1)
                    ->orderBy('name', 'asc')
                    ->get(['id', 'country', 'country_code', 'name', 'code', 'type']);

                if ($dbStates->isNotEmpty()) {
                    return $dbStates->map(function ($s) {
                        return [
                            'id'           => (int) $s->id,
                            'name'         => $s->name,
                            'code'         => $s->code,
                            'type'         => $s->type,
                            'country'      => $s->country ?? 'India',
                            'country_code' => $s->country_code ?? 'IN',
                        ];
                    })->values()->all();
                }
            }
        } catch (\Throwable $e) {
            // Log or fallback
        }

        // Fallback to static sorted list of all 28 states & 8 UTs
        $static = $this->getStaticIndianStatesDataset();
        $statesList = array_map(function ($item, $idx) {
            return [
                'id'           => $idx + 1,
                'name'         => $item['name'],
                'code'         => $item['code'],
                'type'         => $item['type'],
                'country'      => 'India',
                'country_code' => 'IN',
            ];
        }, $static, array_keys($static));

        usort($statesList, fn($a, $b) => strcmp($a['name'], $b['name']));
        return array_values($statesList);
    }

    /**
     * Fetch cities belonging strictly to the selected state.
     */
    protected function fetchCitiesFromDatabase($stateIdentifier): array
    {
        $stateName = null;
        $stateCode = null;
        $cities = [];

        try {
            if (class_exists(State::class) && Schema::hasTable('states')) {
                $query = State::query();
                if (is_numeric($stateIdentifier)) {
                    $query->where('id', (int) $stateIdentifier);
                } else {
                    $clean = trim((string) $stateIdentifier);
                    $query->where('name', 'like', $clean)
                        ->orWhere('code', 'like', $clean);
                }

                $stateObj = $query->first();

                if ($stateObj) {
                    $stateName = $stateObj->name;
                    $stateCode = $stateObj->code;

                    if (class_exists(City::class) && Schema::hasTable('cities')) {
                        $cities = City::where('state_id', $stateObj->id)
                            ->where('status', 1)
                            ->orderBy('name', 'asc')
                            ->pluck('name')
                            ->all();
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fallback
        }

        if (empty($cities)) {
            $found = $this->findStaticCitiesForState($stateIdentifier);
            if ($found) {
                $stateName = $found['name'];
                $stateCode = $found['code'];
                $cities = $found['cities'];
            }
        }

        sort($cities, SORT_NATURAL | SORT_FLAG_CASE);

        return [
            'state_name' => $stateName ?: (string) $stateIdentifier,
            'state_code' => $stateCode,
            'cities'     => array_values(array_unique($cities)),
        ];
    }

    /**
     * Find state in the static dataset by ID, Code, or Name.
     */
    protected function findStaticCitiesForState($identifier): ?array
    {
        $all = $this->getStaticIndianStatesDataset();

        if (is_numeric($identifier)) {
            $idx = ((int) $identifier) - 1;
            if (isset($all[$idx])) {
                return $all[$idx];
            }
        }

        $needle = strtolower(trim((string) $identifier));
        foreach ($all as $item) {
            if (strtolower($item['name']) === $needle || strtolower($item['code']) === $needle) {
                return $item;
            }
        }

        foreach ($all as $item) {
            if (str_contains(strtolower($item['name']), $needle) || str_contains($needle, strtolower($item['name']))) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Static dataset containing all 28 Indian States and 8 Union Territories with major cities.
     */
    protected function getStaticIndianStatesDataset(): array
    {
        return [
            [
                'name' => 'Andaman and Nicobar Islands',
                'code' => 'AN',
                'type' => 'union_territory',
                'cities' => ['Port Blair', 'Garacharma', 'Bambooflat', 'Diglipur', 'Mayabunder', 'Rangat', 'Prothrapur'],
            ],
            [
                'name' => 'Andhra Pradesh',
                'code' => 'AP',
                'type' => 'state',
                'cities' => ['Visakhapatnam', 'Vijayawada', 'Guntur', 'Nellore', 'Kurnool', 'Kakinada', 'Rajahmundry', 'Tirupati', 'Kadapa', 'Anantapur', 'Vizianagaram', 'Eluru', 'Ongole', 'Nandyal', 'Machilipatnam', 'Adoni', 'Tenali', 'Proddatur', 'Chittoor', 'Hindupur', 'Bhimavaram', 'Madanapalle', 'Guntakal', 'Srikakulam', 'Dharmavaram', 'Gudivada', 'Narasaraopet', 'Tadipatri', 'Mangalagiri', 'Chilakaluripet'],
            ],
            [
                'name' => 'Arunachal Pradesh',
                'code' => 'AR',
                'type' => 'state',
                'cities' => ['Itanagar', 'Naharlagun', 'Pasighat', 'Namsai', 'Roing', 'Ziro', 'Tawang', 'Bomdila', 'Tezu', 'Along (Aalo)', 'Khonsa', 'Seppa', 'Changlang'],
            ],
            [
                'name' => 'Assam',
                'code' => 'AS',
                'type' => 'state',
                'cities' => ['Guwahati', 'Silchar', 'Dibrugarh', 'Jorhat', 'Nagaon', 'Tinsukia', 'Tezpur', 'Bongaigaon', 'Karimganj', 'Sivasagar', 'Goalpara', 'Barpeta', 'Dhubri', 'North Lakhimpur', 'Diphu', 'Lumding', 'Mangaldai', 'Haflong'],
            ],
            [
                'name' => 'Bihar',
                'code' => 'BR',
                'type' => 'state',
                'cities' => ['Patna', 'Gaya', 'Bhagalpur', 'Muzaffarpur', 'Purnia', 'Darbhanga', 'Bihar Sharif', 'Arrah', 'Begusarai', 'Katihar', 'Munger', 'Chhapra', 'Danapur', 'Saharsa', 'Sasaram', 'Hajipur', 'Dehri', 'Bettiah', 'Motihari', 'Bagaha', 'Siwan', 'Kishanganj', 'Jamalpur', 'Buxar', 'Jehanabad', 'Aurangabad', 'Nawada'],
            ],
            [
                'name' => 'Chandigarh',
                'code' => 'CH',
                'type' => 'union_territory',
                'cities' => ['Chandigarh'],
            ],
            [
                'name' => 'Chhattisgarh',
                'code' => 'CG',
                'type' => 'state',
                'cities' => ['Raipur', 'Bhilai', 'Bilaspur', 'Korba', 'Rajnandgaon', 'Jagdalpur', 'Raigarh', 'Ambikapur', 'Durg', 'Dhamtari', 'Mahasamund', 'Chirmiri', 'Bhatapara'],
            ],
            [
                'name' => 'Dadra and Nagar Haveli and Daman and Diu',
                'code' => 'DN',
                'type' => 'union_territory',
                'cities' => ['Daman', 'Diu', 'Silvassa', 'Amli'],
            ],
            [
                'name' => 'Delhi',
                'code' => 'DL',
                'type' => 'union_territory',
                'cities' => ['Delhi', 'New Delhi', 'North Delhi', 'South Delhi', 'East Delhi', 'West Delhi', 'Central Delhi', 'Dwarka', 'Rohini', 'Saket', 'Connaught Place', 'Karol Bagh', 'Vasant Kunj', 'Pitampura', 'Janakpuri', 'Lajpat Nagar', 'Shahdara'],
            ],
            [
                'name' => 'Goa',
                'code' => 'GA',
                'type' => 'state',
                'cities' => ['Panaji', 'Margao', 'Vasco da Gama', 'Mapusa', 'Ponda', 'Bicholim', 'Curchorem', 'Canacona', 'Valpoi', 'Pernem', 'Cuncolim'],
            ],
            [
                'name' => 'Gujarat',
                'code' => 'GJ',
                'type' => 'state',
                'cities' => ['Ahmedabad', 'Surat', 'Vadodara', 'Rajkot', 'Bhavnagar', 'Jamnagar', 'Junagadh', 'Gandhinagar', 'Anand', 'Navsari', 'Morbi', 'Nadiad', 'Surendranagar', 'Bharuch', 'Mehsana', 'Bhuj', 'Porbandar', 'Vapi', 'Valsad', 'Godhra', 'Palanpur', 'Ankleshwar', 'Veraval', 'Patan', 'Dahod', 'Botad', 'Amreli', 'Gandhidham', 'Deesa'],
            ],
            [
                'name' => 'Haryana',
                'code' => 'HR',
                'type' => 'state',
                'cities' => ['Faridabad', 'Gurugram (Gurgaon)', 'Panipat', 'Ambala', 'Yamunanagar', 'Rohtak', 'Hisar', 'Karnal', 'Sonipat', 'Panchkula', 'Bhiwani', 'Sirsa', 'Bahadurgarh', 'Jind', 'Thanesar', 'Kaithal', 'Rewari', 'Palwal', 'Hansi', 'Narnaul'],
            ],
            [
                'name' => 'Himachal Pradesh',
                'code' => 'HP',
                'type' => 'state',
                'cities' => ['Shimla', 'Dharamshala', 'Solan', 'Mandi', 'Kullu', 'Manali', 'Baddi', 'Nahan', 'Paonta Sahib', 'Sundarnagar', 'Chamba', 'Una', 'Hamirpur', 'Bilaspur', 'Kangra', 'Palampur'],
            ],
            [
                'name' => 'Jammu and Kashmir',
                'code' => 'JK',
                'type' => 'union_territory',
                'cities' => ['Srinagar', 'Jammu', 'Anantnag', 'Baramulla', 'Kathua', 'Sopore', 'Udhampur', 'Rajouri', 'Punch', 'Pulwama', 'Kupwara', 'Badgam', 'Samba', 'Ganderbal', 'Bandipora'],
            ],
            [
                'name' => 'Jharkhand',
                'code' => 'JH',
                'type' => 'state',
                'cities' => ['Ranchi', 'Jamshedpur', 'Dhanbad', 'Bokaro Steel City', 'Deoghar', 'Phusro', 'Hazaribagh', 'Giridih', 'Ramgarh', 'Medininagar (Daltonganj)', 'Chirkunda', 'Chaibasa', 'Gumla', 'Dumka', 'Sahibganj'],
            ],
            [
                'name' => 'Karnataka',
                'code' => 'KA',
                'type' => 'state',
                'cities' => ['Bengaluru (Bangalore)', 'Mysuru (Mysore)', 'Hubballi-Dharwad', 'Mangaluru (Mangalore)', 'Belagavi (Belgaum)', 'Kalaburagi (Gulbarga)', 'Davanagere', 'Ballari (Bellary)', 'Vijayapura (Bijapur)', 'Shivamogga (Shimoga)', 'Tumakuru (Tumkur)', 'Raichur', 'Bidar', 'Hosapete', 'Gadag-Betageri', 'Hassan', 'Udupi', 'Robertsonpet', 'Bhadravati', 'Chitradurga', 'Kolar', 'Mandya', 'Chikmagalur', 'Gangawati', 'Bagalkot', 'Ranebennur'],
            ],
            [
                'name' => 'Kerala',
                'code' => 'KL',
                'type' => 'state',
                'cities' => ['Thiruvananthapuram', 'Kochi', 'Kozhikode', 'Kollam', 'Thrissur', 'Kannur', 'Alappuzha', 'Kottayam', 'Palakkad', 'Manjeri', 'Thalassery', 'Ponnani', 'Vatakara', 'Kanhangad', 'Payyanur', 'Koyilandy', 'Neyyattinkara', 'Kayamkulam', 'Malappuram', 'Kasaragod'],
            ],
            [
                'name' => 'Ladakh',
                'code' => 'LA',
                'type' => 'union_territory',
                'cities' => ['Leh', 'Kargil', 'Diskit', 'Padum', 'Dras', 'Khalatse'],
            ],
            [
                'name' => 'Lakshadweep',
                'code' => 'LD',
                'type' => 'union_territory',
                'cities' => ['Kavaratti', 'Agatti', 'Amini', 'Andrott', 'Minicoy', 'Kalpeni', 'Kadmat', 'Kiltan', 'Chetlat'],
            ],
            [
                'name' => 'Madhya Pradesh',
                'code' => 'MP',
                'type' => 'state',
                'cities' => ['Indore', 'Bhopal', 'Jabalpur', 'Gwalior', 'Ujjain', 'Sagar', 'Dewas', 'Satna', 'Ratlam', 'Rewa', 'Murwara (Katni)', 'Singrauli', 'Burhanpur', 'Khandwa', 'Bhind', 'Chhindwara', 'Guna', 'Shivpuri', 'Vidisha', 'Chhatarpur', 'Damoh', 'Mandsaur', 'Khargone', 'Neemuch', 'Pithampur', 'Hoshangabad', 'Itarsi', 'Sehore'],
            ],
            [
                'name' => 'Maharashtra',
                'code' => 'MH',
                'type' => 'state',
                'cities' => ['Mumbai', 'Pune', 'Nagpur', 'Thane', 'Pimpri-Chinchwad', 'Nashik', 'Kalyan-Dombivli', 'Vasai-Virar', 'Aurangabad (Chhatrapati Sambhajinagar)', 'Navi Mumbai', 'Solapur', 'Mira-Bhayandar', 'Bhiwandi', 'Amravati', 'Nanded', 'Kolhapur', 'Akola', 'Ulhasnagar', 'Sangli', 'Malegaon', 'Jalgaon', 'Latur', 'Dhule', 'Ahmednagar (Ahilyanagar)', 'Chandrapur', 'Parbhani', 'Ichalkaranji', 'Jalna', 'Ambarnath', 'Bhusawal', 'Panvel', 'Badlapur', 'Beed', 'Gondia', 'Satara', 'Barshi', 'Yavatmal', 'Achalpur', 'Osmanabad (Dharashiv)', 'Nandurbar', 'Wardha', 'Udgir', 'Hinganghat'],
            ],
            [
                'name' => 'Manipur',
                'code' => 'MN',
                'type' => 'state',
                'cities' => ['Imphal', 'Thoubal', 'Kakching', 'Ukhrul', 'Churachandpur', 'Bishnupur', 'Senapati', 'Tamenglong', 'Jiribam', 'Moreh'],
            ],
            [
                'name' => 'Meghalaya',
                'code' => 'ML',
                'type' => 'state',
                'cities' => ['Shillong', 'Tura', 'Nongpoh', 'Jowai', 'Baghmara', 'Williamnagar', 'Resubelpara', 'Mairang', 'Cherrapunji (Sohra)', 'Nongstoin'],
            ],
            [
                'name' => 'Mizoram',
                'code' => 'MZ',
                'type' => 'state',
                'cities' => ['Aizawl', 'Lunglei', 'Saiha', 'Champhai', 'Kolasib', 'Serchhip', 'Lawngtlai', 'Mamit', 'Hnahthial', 'Khawzawl', 'Saitual'],
            ],
            [
                'name' => 'Nagaland',
                'code' => 'NL',
                'type' => 'state',
                'cities' => ['Kohima', 'Dimapur', 'Mokokchung', 'Tuensang', 'Wokha', 'Zunheboto', 'Mon', 'Phek', 'Kiphire', 'Longleng', 'Peren', 'Chumukedima'],
            ],
            [
                'name' => 'Odisha',
                'code' => 'OD',
                'type' => 'state',
                'cities' => ['Bhubaneswar', 'Cuttack', 'Rourkela', 'Berhampur', 'Sambalpur', 'Puri', 'Balasore', 'Bhadrak', 'Baripada', 'Jharsuguda', 'Bargarh', 'Jeypore', 'Rayagada', 'Bolangir', 'Dhenkanal', 'Kendrapara', 'Angul', 'Paradeep'],
            ],
            [
                'name' => 'Puducherry',
                'code' => 'PY',
                'type' => 'union_territory',
                'cities' => ['Puducherry', 'Karaikal', 'Mahe', 'Yanam', 'Ozhukarai'],
            ],
            [
                'name' => 'Punjab',
                'code' => 'PB',
                'type' => 'state',
                'cities' => ['Ludhiana', 'Amritsar', 'Jalandhar', 'Patiala', 'Bathinda', 'Hoshiarpur', 'Mohali (SAS Nagar)', 'Batala', 'Pathankot', 'Moga', 'Abohar', 'Malerkotla', 'Khanna', 'Phagwara', 'Muktsar', 'Barnala', 'Rajpura', 'Firozpur', 'Kapurthala', 'Sunam', 'Mansa'],
            ],
            [
                'name' => 'Rajasthan',
                'code' => 'RJ',
                'type' => 'state',
                'cities' => ['Jaipur', 'Jodhpur', 'Kota', 'Bikaner', 'Ajmer', 'Udaipur', 'Bhilwara', 'Alwar', 'Bharatpur', 'Sikar', 'Pali', 'Sri Ganganagar', 'Beawar', 'Hanumangarh', 'Kishangarh', 'Tonk', 'Dholpur', 'Sawai Madhopur', 'Churu', 'Chittorgarh', 'Bundi', 'Baran', 'Nagaur', 'Jhunjhunu', 'Hindaun', 'Gangapur City'],
            ],
            [
                'name' => 'Sikkim',
                'code' => 'SK',
                'type' => 'state',
                'cities' => ['Gangtok', 'Namchi', 'Geyzing', 'Mangan', 'Rangpo', 'Singtam', 'Jorethang', 'Ravangla', 'Soreng', 'Pakyong'],
            ],
            [
                'name' => 'Tamil Nadu',
                'code' => 'TN',
                'type' => 'state',
                'cities' => ['Chennai', 'Coimbatore', 'Madurai', 'Tiruchirappalli', 'Salem', 'Tiruppur', 'Erode', 'Tirunelveli', 'Vellore', 'Thoothukudi', 'Dindigul', 'Thanjavur', 'Ranipet', 'Sivakasi', 'Karur', 'Udhagamandalam (Ooty)', 'Hosur', 'Nagercoil', 'Kanchipuram', 'Kumarapalayam', 'Karaikudi', 'Neyveli', 'Cuddalore', 'Kumbakonam', 'Tiruvannamalai', 'Pollachi', 'Rajapalayam', 'Gudiyatham', 'Pudukkottai', 'Vaniyambadi', 'Ambur', 'Nagapattinam'],
            ],
            [
                'name' => 'Telangana',
                'code' => 'TG',
                'type' => 'state',
                'cities' => ['Hyderabad', 'Warangal', 'Nizamabad', 'Khammam', 'Karimnagar', 'Ramagundam', 'Mahbubnagar', 'Nalgonda', 'Adilabad', 'Suryapet', 'Miryalaguda', 'Siddipet', 'Jagtial', 'Mancherial', 'Nirmal', 'Kamareddy', 'Kothagudem', 'Bodhan', 'Palwancha', 'Mandamarri'],
            ],
            [
                'name' => 'Tripura',
                'code' => 'TR',
                'type' => 'state',
                'cities' => ['Agartala', 'Dharmanagar', 'Udaipur', 'Kailashahar', 'Teliamura', 'Khowai', 'Belonia', 'Melaghar', 'Ambassa', 'Ranirbazar', 'Bishalgarh', 'Santirbazar', 'Kumarghat'],
            ],
            [
                'name' => 'Uttar Pradesh',
                'code' => 'UP',
                'type' => 'state',
                'cities' => ['Lucknow', 'Kanpur', 'Ghaziabad', 'Agra', 'Varanasi', 'Meerut', 'Prayagraj (Allahabad)', 'Bareilly', 'Aligarh', 'Moradabad', 'Saharanpur', 'Gorakhpur', 'Noida', 'Firozabad', 'Jhansi', 'Muzaffarnagar', 'Mathura', 'Ayodhya', 'Rampur', 'Shahjahanpur', 'Farrukhabad', 'Budaun', 'Maunath Bhanjan', 'Hapur', 'Etawah', 'Mirzapur', 'Sambhal', 'Amroha', 'Hardoi', 'Fatehpur', 'Raebareli', 'Orai', 'Sitapur', 'Bahraich', 'Modinagar', 'Unnao', 'Jaunpur', 'Lakhimpur', 'Hathras', 'Banda', 'Pilibhit', 'Barabanki', 'Khurja', 'Gonda', 'Mainpuri', 'Lalitpur', 'Etah', 'Deoria', 'Ghazipur', 'Sultanpur', 'Azamgarh', 'Bijnor', 'Basti', 'Chandausi', 'Greater Noida'],
            ],
            [
                'name' => 'Uttarakhand',
                'code' => 'UK',
                'type' => 'state',
                'cities' => ['Dehradun', 'Haridwar', 'Roorkee', 'Haldwani', 'Rudrapur', 'Kashipur', 'Rishikesh', 'Kotdwar', 'Ramnagar', 'Manglaur', 'Pithoragarh', 'Kichha', 'Mussoorie', 'Tehri', 'Pauri', 'Almora', 'Nainital', 'Champawat', 'Bageshwar', 'Chamoli', 'Uttarkashi'],
            ],
            [
                'name' => 'West Bengal',
                'code' => 'WB',
                'type' => 'state',
                'cities' => ['Kolkata', 'Asansol', 'Siliguri', 'Durgapur', 'Bardhaman', 'Malda', 'Baharampur', 'Habra', 'Kharagpur', 'Shantipur', 'Dankuni', 'Dhulian', 'Ranaghat', 'Haldia', 'Raiganj', 'Krishnanagar', 'Nabadwip', 'Midnapore', 'Jalpaiguri', 'Balurghat', 'Basirhat', 'Bankura', 'Darjeeling', 'Purulia', 'Alipurduar', 'Cooch Behar'],
            ],
        ];
    }
}
