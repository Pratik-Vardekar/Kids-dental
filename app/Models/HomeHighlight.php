<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeHighlight extends Model
{
    protected $fillable = [
        'title',
        'icon',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'sort_order' => 'integer',
    ];
}
