<?php

namespace App\Models;

use App\Models\OrderPayment;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [

        'user_id',
        'order_id',
        'invoice_no',

        'subtotal',
        'discount',
        'shipping_charge',
        'grand_total',

        'paid_amount',
        'due_amount',

        'payment_method',
        'payment_type',
        'payment_status',
        'order_status',

        'transaction_id',
        'payment_gateway',
        'payment_id',

        'invoice_date',
        'due_date',

        'tracking_id',
        'courier_name',
        'tracking_url',

        'admin_note',
        'notes',

        'delivered_at',
        'cancelled_at',
        'shipped_at',
    ];

    protected $casts = [

        'invoice_date' => 'date',
        'due_date' => 'date',

        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'shipped_at' => 'datetime',

    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

  
public function orderPayments()
{
    return $this->hasMany(OrderPayment::class);
}
    public function notes()
    {
        return $this->hasMany(OrderNote::class);
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }
    public function getTotalPaidAttribute()
{
    return $this->orderPayments()->sum('amount');
}

public function getDueAmountAttribute()
{
    return $this->grand_total - $this->total_paid;
}

public function getPaymentStatusAutoAttribute()
{
    if ($this->total_paid <= 0) {
        return 'pending';
    }

    if ($this->total_paid < $this->grand_total) {
        return 'partial';
    }

    return 'paid';
}

    
}