<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\OrderPayment;
use App\Models\OrderAddress;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'invoice_no',

        'subtotal',
        'discount',
        'shipping_charge',
        'gst_rate',
        'cgst_amount',
        'sgst_amount',
        'igst_amount',
        'taxable_amount',
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
        'razorpay_order_id',
        'remaining_payment_link',

        'invoice_date',
        'due_date',

        'tracking_id',
        'courier_name',
        'tracking_url',

        'admin_note',
        'notes',
        'order_source',
        'coupon_code',
        'coupon_discount',

        'delivered_at',
        'cancelled_at',
        'shipped_at',

        // Cancel request workflow
        'cancel_requested_at',
        'cancel_request_reason',
        'cancel_request_note',
        'cancel_approved_at',
        'cancel_rejected_at',
        'cancel_decision_note',

        // Return/exchange workflow
        'return_requested_at',
        'return_request_type',
        'return_reason',
        'return_request_note',
        'return_approved_at',
        'return_rejected_at',
        'return_decision_note',
        'returnable_until',
        'pickup_date',
        'returned_at',

        'full_payment_discount',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',

        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'shipped_at' => 'datetime',

        'cancel_requested_at' => 'datetime',
        'cancel_approved_at' => 'datetime',
        'cancel_rejected_at' => 'datetime',

        'return_requested_at' => 'datetime',
        'return_approved_at' => 'datetime',
        'return_rejected_at' => 'datetime',
        'returned_at' => 'datetime',

        'returnable_until' => 'date',
        'pickup_date' => 'date',
    ];

    protected $appends = [
        'discount_amount',
        'total_paid',
        'payment_status_auto',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

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

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function addresses()
    {
        return $this->hasMany(OrderAddress::class);
    }

    public function notes()
    {
        return $this->hasMany(OrderNote::class);
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    public function billingAddress()
    {
        return $this->hasOne(OrderAddress::class)
            ->where('type', 'billing');
    }

    public function shippingAddress()
    {
        return $this->hasOne(OrderAddress::class)
            ->where('type', 'shipping');
    }
    public function firstProduct()
    {
        $item = $this->relationLoaded('items')
            ? $this->items->first()
            : $this->items()->with('product')->first();

        return $item?->product;
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getDiscountAmountAttribute()
    {
        return (float) (
            ($this->discount ?? 0)
            + ($this->full_payment_discount ?? 0)
        );
    }

    public function getTotalPaidAttribute()
    {
        return $this->orderPayments()->sum('amount');
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

    public function getGstTotalAttribute(): float
    {
        $storedGst = (float) ($this->cgst_amount ?? 0)
            + (float) ($this->sgst_amount ?? 0)
            + (float) ($this->igst_amount ?? 0);

        if ($storedGst > 0) {
            return round($storedGst, 2);
        }

        $total = 0.0;

        foreach ($this->items as $item) {
            $product = $item->product;
            if (!$product) {
                continue;
            }

            $taxInfo = $product->getInclusiveTaxInfo((float) $item->price);
            $total += ($taxInfo['tax_amount'] ?? 0) * $item->quantity;
        }

        return round($total, 2);
    }

    public function getDisplayPaymentStatusAttribute(): string
    {
        $status = strtolower((string) ($this->payment_status ?? 'pending'));
        $method = strtolower((string) ($this->payment_method ?? ''));

        if ($status === 'paid') {
            return 'Paid';
        }

        if ($status === 'partial') {
            return 'Partial';
        }

        $totalPaid = (float) $this->orderPayments()->where('status', 'success')->sum('amount');
        $grandTotal = (float) ($this->grand_total ?? 0);

        if ($totalPaid >= $grandTotal && $totalPaid > 0) {
            return 'Paid';
        }

        if ((float) ($this->paid_amount ?? 0) >= $grandTotal && $grandTotal > 0) {
            return 'Paid';
        }

        if ($status === 'processing' && $method === 'razorpay') {
            return 'Processing';
        }

        if ($status === 'failed') {
            return 'Failed';
        }

        return 'Pending';
    }

    public function getDisplayOrderStatusAttribute(): string
    {
        $status = strtolower((string) ($this->order_status ?? ''));

        if ($status === 'pending' && in_array(strtolower((string) ($this->payment_method ?? '')), ['cod', 'razorpay'], true)) {
            return 'processing';
        }

        return $status;
    }

    public function getOrderSummary(): array
    {
        $itemsSubtotal = round(
            $this->items->sum(fn ($item) => (float) ($item->total ?? ($item->price * $item->quantity))),
            2
        );
        $discountAmount = (float) ($this->discount ?? 0);
        $shippingCharge = (float) ($this->shipping_charge ?? 0);
        $gstAmount = $this->gst_total;
        $gstRateLabel = $this->getGstRateLabel();
        $grandTotal = (float) ($this->grand_total ?? max(0, $itemsSubtotal + $gstAmount - $discountAmount + $shippingCharge));
        $totalPaid = round(max(
            (float) ($this->paid_amount ?? 0),
            (float) $this->orderPayments()->where('status', 'success')->sum('amount')
        ), 2);
        $isFullyPaid = strtolower((string) ($this->payment_status ?? '')) === 'paid' || $totalPaid >= $grandTotal;

        return [
            'items_subtotal' => $itemsSubtotal,
            'discount_amount' => $discountAmount,
            'shipping_charge' => $shippingCharge,
            'gst_amount' => $gstAmount,
            'gst_rate_label' => $gstRateLabel,
            'grand_total' => $grandTotal,
            'total_paid' => $totalPaid,
            'is_fully_paid' => $isFullyPaid,
        ];
    }

    public function getGstRateLabel(): string
    {
        $gstRates = [];

        foreach ($this->items as $item) {
            $product = $item->product;
            if (!$product) {
                continue;
            }

            $taxInfo = $product->getInclusiveTaxInfo((float) $item->price);
            if (!empty($taxInfo['gst_percentage'])) {
                $gstRates[] = (float) $taxInfo['gst_percentage'];
            }
        }

        if (empty($gstRates)) {
            return 'GST (inclusive)';
        }

        $uniqueRates = array_values(array_unique($gstRates));

        return count($uniqueRates) === 1
            ? $uniqueRates[0] . '% GST (inclusive)'
            : 'GST (inclusive)';
    }

    public function returnDeadline()
    {
        if ($this->returnable_until) {
            return $this->returnable_until;
        }

        if ($this->delivered_at) {
            return $this->delivered_at->copy()->addDays(7);
        }

        if (strtolower($this->order_status ?? '') === 'delivered' && $this->created_at) {
            return $this->created_at->copy()->addDays(7);
        }

        return null;
    }

    public function expectedDeliveryDate()
    {
        if ($this->delivered_at) {
            return $this->delivered_at;
        }

        if ($this->shipped_at) {
            return $this->shipped_at->copy()->addDays(3);
        }

        if ($this->created_at) {
            return $this->created_at->copy()->addDays(7);
        }

        return null;
    }

    public function hasPendingCancelRequest(): bool
    {
        return (bool) ($this->cancel_requested_at && ! $this->cancel_approved_at && ! $this->cancel_rejected_at);
    }

    public function hasPendingReturnRequest(): bool
    {
        return (bool) ($this->return_requested_at && ! $this->return_approved_at && ! $this->return_rejected_at);
    }

    public function canRequestReturn(): bool
    {
        if (strtolower($this->order_status ?? '') !== 'delivered') {
            return false;
        }

        if ($this->hasPendingReturnRequest() || $this->return_approved_at) {
            return false;
        }

        $deadline = $this->returnDeadline();

        return $deadline && now()->lte($deadline);
    }

    public function canRequestCancel(): bool
    {
        return in_array(strtolower($this->order_status ?? ''), ['pending', 'confirmed', 'processing'], true)
            && ! $this->hasPendingCancelRequest()
            && ! $this->cancel_approved_at;
    }

    public static function paddedDocumentId(int $id): string
    {
        return str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }

    public static function buildOrderNumber(int $id): string
    {
        return 'ORD' . self::paddedDocumentId($id);
    }

    public static function buildInvoiceNumber(int $id): string
    {
        return 'KED' . self::paddedDocumentId($id);
    }

    public static function temporaryOrderNumber(): string
    {
        return 'TMP-' . str_replace('.', '', (string) microtime(true));
    }

    public function assignOrderNumber(): void
    {
        if (!$this->id) {
            return;
        }

        $this->update([
            'order_id' => self::buildOrderNumber((int) $this->id),
            'invoice_no' => self::buildInvoiceNumber((int) $this->id),
        ]);
    }

    public function normalizeDocumentNumbers(): void
    {
        if (!$this->id) {
            return;
        }

        $orderId = self::buildOrderNumber((int) $this->id);
        $invoiceNo = self::buildInvoiceNumber((int) $this->id);

        if ($this->order_id === $orderId && $this->invoice_no === $invoiceNo) {
            return;
        }

        $this->update([
            'order_id' => $orderId,
            'invoice_no' => $invoiceNo,
        ]);
    }

    public function getDisplayOrderIdAttribute(): string
    {
        return self::buildOrderNumber((int) $this->id);
    }

    public function getDisplayInvoiceNoAttribute(): string
    {
        return self::buildInvoiceNumber((int) $this->id);
    }

    public function invoicePdfFilename(): string
    {
        return 'invoice-' . $this->display_order_id . '.pdf';
    }
}