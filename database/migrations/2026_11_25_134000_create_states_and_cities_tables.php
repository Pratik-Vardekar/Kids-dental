<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('states')) {
            Schema::create('states', function (Blueprint $table) {
                $table->id();
                $table->string('country')->default('India');
                $table->string('country_code', 5)->default('IN');
                $table->string('name');
                $table->string('code', 10)->nullable();
                $table->string('type', 20)->default('state')->comment('state or union_territory');
                $table->tinyInteger('status')->default(1);
                $table->timestamps();

                $table->index(['country_code', 'status', 'name']);
            });
        }

        if (!Schema::hasTable('cities')) {
            Schema::create('cities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('state_id')->constrained('states')->onDelete('cascade');
                $table->string('state_name')->nullable();
                $table->string('name');
                $table->tinyInteger('status')->default(1);
                $table->timestamps();

                $table->index(['state_id', 'status', 'name']);
                $table->index(['state_name', 'name']);
            });
        }

        // Seed 28 States, 8 Union Territories and their cities if empty
        if (Schema::hasTable('states') && DB::table('states')->count() === 0) {
            $dataset = $this->getStatesAndCities();
            $now = now();

            foreach ($dataset as $item) {
                $stateId = DB::table('states')->insertGetId([
                    'country'      => 'India',
                    'country_code' => 'IN',
                    'name'         => $item['name'],
                    'code'         => $item['code'],
                    'type'         => $item['type'],
                    'status'       => 1,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);

                if (!empty($item['cities'])) {
                    $cityRows = [];
                    foreach ($item['cities'] as $cityName) {
                        $cityRows[] = [
                            'state_id'   => $stateId,
                            'state_name' => $item['name'],
                            'name'       => trim($cityName),
                            'status'     => 1,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                    foreach (array_chunk($cityRows, 100) as $chunk) {
                        DB::table('cities')->insert($chunk);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
        Schema::dropIfExists('states');
    }

    /**
     * 28 Indian states + 8 Union Territories and their key cities.
     */
    private function getStatesAndCities(): array
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
};
