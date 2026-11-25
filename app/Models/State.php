<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory;

    protected $fillable = [
        'country',
        'country_code',
        'name',
        'code',
        'type',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    /**
     * Cities belonging to this state.
     */
    public function cities()
    {
        return $this->hasMany(City::class, 'state_id')->orderBy('name', 'asc');
    }

    /**
     * Scope active states.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope India.
     */
    public function scopeIndia($query)
    {
        return $query->where(function ($q) {
            $q->where('country_code', 'IN')
              ->orWhere('country', 'India');
        });
    }
}
