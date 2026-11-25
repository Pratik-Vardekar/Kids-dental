<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'state_id',
        'state_name',
        'name',
        'status',
    ];

    protected $casts = [
        'state_id' => 'integer',
        'status'   => 'integer',
    ];

    /**
     * State relationship.
     */
    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    /**
     * Scope active cities.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
