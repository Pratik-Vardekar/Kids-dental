<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [

        'user_id',
        'name',
        'phone',
        'alternate_phone',
        'address',
        'landmark',
        'city',
        'state',
        'country',
        'pincode',
        'type',
        'is_default',
        'status',

    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}