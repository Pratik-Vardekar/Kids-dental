<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [

        'order_id',
        'transaction_id',
        'payment_gateway',
        'amount',
        'payment_method',
        'status',
        'note',

    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}