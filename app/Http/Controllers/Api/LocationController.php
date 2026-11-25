<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class LocationController extends Controller
{
    /**
     * Check visitor IP country.
     * Returns country_code, country_name, country_flag, is_india, and status.
     */
    public function check(Request $request)
    {
        // Support real IP behind proxies (Cloudflare, reverse proxy, etc.) or query parameter for testing
        $ip = $request->input('ip')
            ?: ($request->header('CF-Connecting-IP')
                ?: ($request->header('X-Forwarded-For')
                    ? trim(explode(',', $request->header('X-Forwarded-For'))[0])
                    : ($request->header('X-Real-IP') ?: $request->ip())));

        $ip = trim((string) $ip);

        // Local development or private IP
        if (empty($ip) || in_array($ip, ['127.0.0.1', '::1']) || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return response()->json([
                'status'       => true,
                'country_code' => 'IN',
                'country_name' => 'India',
                'country_flag' => 'https://cdn.ipwhois.io/flags/in.svg',
                'is_india'     => true,
                'message'      => 'India visitor',
            ]);
        }

        $cacheKey = 'visitor_location_v2_' . md5($ip);

        try {
            $locationData = Cache::remember(
                $cacheKey,
                now()->addHours(24),
                function () use ($ip) {
                    return $this->lookupIp($ip);
                }
            );
        } catch (\Throwable $e) {
            $locationData = $this->lookupIp($ip);
        }

        $countryCode = $locationData['country_code'] ?? 'IN';
        $countryName = $locationData['country_name'] ?? $this->getCountryNameByCode($countryCode);
        $countryFlag = $locationData['country_flag'] ?? ('https://cdn.ipwhois.io/flags/' . strtolower($countryCode) . '.svg');
        $isIndia     = ($countryCode === 'IN');

        return response()->json([
            'status'       => true,
            'country_code' => $countryCode,
            'country_name' => $countryName,
            'country_flag' => $countryFlag,
            'is_india'     => $isIndia,
            'message'      => $isIndia ? 'India visitor' : 'International visitor',
        ]);
    }

    /**
     * Lookup country details from IP.
     */
    protected function lookupIp(string $ip): array
    {
        try {
            $response = Http::timeout(6)->get("https://ipwho.is/{$ip}");

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['success']) && !empty($data['country_code'])) {
                    $code = strtoupper($data['country_code']);
                    $name = $data['country'] ?? $this->getCountryNameByCode($code);
                    $flag = $data['flag']['img'] ?? ('https://cdn.ipwhois.io/flags/' . strtolower($code) . '.svg');

                    return [
                        'country_code' => $code,
                        'country_name' => $name,
                        'country_flag' => $flag,
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Fallback
        }

        return [
            'country_code' => 'IN',
            'country_name' => 'India',
            'country_flag' => 'https://cdn.ipwhois.io/flags/in.svg',
        ];
    }

    /**
     * Helper to get country name by ISO country code.
     */
    protected function getCountryNameByCode(string $code): string
    {
        $countries = [
            'IN' => 'India',
            'US' => 'United States',
            'GB' => 'United Kingdom',
            'AE' => 'United Arab Emirates',
            'CA' => 'Canada',
            'AU' => 'Australia',
            'DE' => 'Germany',
            'FR' => 'France',
            'SG' => 'Singapore',
            'SA' => 'Saudi Arabia',
            'NP' => 'Nepal',
            'BD' => 'Bangladesh',
            'LK' => 'Sri Lanka',
            'MY' => 'Malaysia',
            'NZ' => 'New Zealand',
            'ZA' => 'South Africa',
            'JP' => 'Japan',
            'CN' => 'China',
            'BR' => 'Brazil',
            'RU' => 'Russia',
            'IT' => 'Italy',
            'ES' => 'Spain',
            'NL' => 'Netherlands',
            'CH' => 'Switzerland',
            'SE' => 'Sweden',
        ];

        return $countries[strtoupper($code)] ?? strtoupper($code);
    }

    /**
     * Get Location Details API
     *
     * 1. By default (/api/location-details):
     *    Returns all Indian states & union territories for state dropdown.
     *
     * 2. When state is selected (/api/location-details?state=Maharashtra):
     *    Returns only cities for that state in City. Does NOT return states array.
     */
    public function getLocationDetails(Request $request): JsonResponse
    {
        // Extract state parameter from query (?state=..., ?selected_state=..., ?state_name=..., ?state_id=...)
        $requestedState = $request->input('state')
            ?? $request->input('selected_state')
            ?? $request->input('state_name')
            ?? $request->input('state_id')
            ?? $request->input('state_code');

        // ========================================================
        // 1. IF STATE IS SELECTED: Show ONLY single 'city' array for that state
        // ========================================================
        if (!empty($requestedState)) {
            $cityInfo = $this->fetchCitiesForState($requestedState);
            $selectedStateName = $cityInfo['state_name'];
            $selectedStateCode = $cityInfo['state_code'];
            $cities = $cityInfo['cities'];

            return response()->json([
                'status'         => true,
                'message'        => "Cities for '{$selectedStateName}' fetched successfully",
                'country'        => 'India',
                'country_code'   => 'IN',
                'country_name'   => 'India',
                'country_flag'   => 'https://cdn.ipwhois.io/flags/in.svg',
                'selected_state' => $selectedStateName,
                'state_code'     => $selectedStateCode,
                'city'           => $cities,
                'total_cities'   => count($cities),
            ], 200);
        }

        // ========================================================
        // 2. BY DEFAULT (NO STATE): Show all 28 states & 8 UTs in India
        // ========================================================
        $states = $this->fetchIndianStates();

        return response()->json([
            'status'       => true,
            'message'      => 'All states in India fetched successfully',
            'country'      => 'India',
            'country_code' => 'IN',
            'country_name' => 'India',
            'country_flag' => 'https://cdn.ipwhois.io/flags/in.svg',
            'state'        => $states,
            'total_states' => count($states),
        ], 200);
    }

    /**
     * Fetch all Indian States & Union Territories sorted alphabetically.
     */
    protected function fetchIndianStates(): array
    {
        try {
            if (class_exists(State::class) && Schema::hasTable('states')) {
                $dbStates = State::with(['cities' => function ($q) {
                    $q->where('status', 1)->orderBy('name', 'asc');
                }])
                    ->where('status', 1)
                    ->orderBy('name', 'asc')
                    ->get(['id', 'country', 'country_code', 'name', 'code', 'type']);

                if ($dbStates->isNotEmpty()) {
                    return $dbStates->map(function ($s) {
                        $cities = $s->cities ? $s->cities->pluck('name')->all() : [];
                        if (empty($cities)) {
                            $staticFound = $this->findStaticState($s->name);
                            $cities = $staticFound['cities'] ?? [];
                        }
                        sort($cities, SORT_NATURAL | SORT_FLAG_CASE);

                        return [
                            'id'           => (int) $s->id,
                            'name'         => $s->name,
                            'code'         => $s->code,
                            'type'         => $s->type,
                            'country'      => $s->country ?? 'India',
                            'country_code' => $s->country_code ?? 'IN',
                            'cities'       => array_values(array_unique($cities)),
                        ];
                    })->values()->all();
                }
            }
        } catch (\Throwable $e) {
            // Fallback to static dataset if database table is pending migration
        }

        $static = $this->getStaticIndianStatesDataset();
        $statesList = array_map(function ($item, $idx) {
            $cities = $item['cities'] ?? [];
            sort($cities, SORT_NATURAL | SORT_FLAG_CASE);

            return [
                'id'           => $idx + 1,
                'name'         => $item['name'],
                'code'         => $item['code'],
                'type'         => $item['type'],
                'country'      => 'India',
                'country_code' => 'IN',
                'cities'       => array_values(array_unique($cities)),
            ];
        }, $static, array_keys($static));

        usort($statesList, fn($a, $b) => strcmp($a['name'], $b['name']));

        return array_values($statesList);
    }

    /**
     * Fetch cities belonging strictly to the requested state.
     */
    protected function fetchCitiesForState($stateIdentifier): array
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
            $found = $this->findStaticState($stateIdentifier);
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
     * Find state in static dataset by ID, code, or name.
     */
    protected function findStaticState($identifier): ?array
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
                'cities' => ['Patna', 'Gaya', 'Bhagalpur', 'Muzaffarpur', 'Purnia', 'Darbhanga', 'Bihar Sharif', 'Arrah', 'Begusarai', 'Katihar', 'Munger', 'Chhapra', 'Danapur', 'Saharsa', 'Sasaram', 'Hajipur', 'Dehri', 'Bettiah', 'Motihari', 'Bagaha', 'Siwan', 'Kishanganj', 'Jamalpur', 'Buxar', 'Jehanabad', 'Chhatrapati Sambhajinagar', 'Nawada'],
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
                'cities' => ['Mumbai', 'Pune', 'Nagpur', 'Thane', 'Pimpri-Chinchwad', 'Nashik', 'Kalyan-Dombivli', 'Vasai-Virar', 'Chhatrapati Sambhajinagar', 'Navi Mumbai', 'Solapur', 'Mira-Bhayandar', 'Bhiwandi', 'Amravati', 'Nanded', 'Kolhapur', 'Akola', 'Ulhasnagar', 'Sangli', 'Malegaon', 'Jalgaon', 'Latur', 'Dhule', 'Ahilyanagar', 'Chandrapur', 'Parbhani', 'Ichalkaranji', 'Jalna', 'Ambarnath', 'Bhusawal', 'Panvel', 'Badlapur', 'Beed', 'Gondia', 'Satara', 'Barshi', 'Yavatmal', 'Achalpur', 'Dharashiv', 'Nandurbar', 'Wardha', 'Udgir', 'Hinganghat'],
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
