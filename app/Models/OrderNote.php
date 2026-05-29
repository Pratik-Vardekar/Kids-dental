<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderNote extends Model
{
    protected $fillable = [

        'order_id',
        'note',
        'is_customer_visible',

    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}