<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderPayment;
use Illuminate\Http\Request;

class OrderPaymentController extends Controller
{
    /*
    |--------------------------------------------------
    | STORE PAYMENT
    |--------------------------------------------------
    */
    public function store(Request $request, Order $order)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string',
            'transaction_id' => 'nullable|string',
        ]);

        // Current paid & due
        $paid = $order->orderPayments()->sum('amount');
        $due  = $order->grand_total - $paid;

        // Prevent overpayment
        if ($request->amount > $due) {
            return back()->with('error', 'Payment exceeds due amount');
        }

        // Create payment record
        OrderPayment::create([
            'order_id' => $order->id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'transaction_id' => $request->transaction_id,
            'status' => 'success',
            'paid_at' => now(),
        ]);

        // Recalculate total paid
        $paid = $order->orderPayments()->sum('amount');

        // Update order financials
        $order->paid_amount = $paid;
        $order->due_amount = max(0, $order->grand_total - $paid);

        // Auto payment status
        if ($order->due_amount <= 0) {
            $order->payment_status = 'paid';
        } elseif ($paid > 0) {
            $order->payment_status = 'partial';
        } else {
            $order->payment_status = 'pending';
        }

        $order->save();

        return back()->with('success', 'Payment added successfully');
    }

    /*
    |--------------------------------------------------
    | OPTIONAL: DELETE PAYMENT (if needed)
    |--------------------------------------------------
    */
    public function destroy(OrderPayment $payment)
    {
        $order = $payment->order;

        $payment->delete();

        // Recalculate after deletion
        $paid = $order->orderPayments()->sum('amount');

        $order->paid_amount = $paid;
        $order->due_amount = max(0, $order->grand_total - $paid);

        if ($order->due_amount <= 0) {
            $order->payment_status = 'paid';
        } elseif ($paid > 0) {
            $order->payment_status = 'partial';
        } else {
            $order->payment_status = 'pending';
        }

        $order->save();

        return back()->with('success', 'Payment deleted successfully');
    }
}