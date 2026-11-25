<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DistributorEnquiry extends Model
{
    use HasFactory;

    protected $table = 'distributor_enquiries';

    protected $fillable = [
        'company_name',
        'contact_person',
        'email',
        'mobile_no',
        'country_city',
        'message',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Alias for mobile_no
     */
    public function getPhoneAttribute()
    {
        return $this->mobile_no;
    }

    /**
     * Alias for contact_person
     */
    public function getNameAttribute()
    {
        return $this->contact_person;
    }

    /**
     * Status label attribute
     */
    public function getStatusLabelAttribute()
    {
        return $this->status ? 'Read' : 'Unread';
    }
}
