<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\OrderPayment;
use Illuminate\Support\Facades\DB;
use App\Models\OrderItem;

class OrderController extends Controller
{
    /*
    |--------------------------------------------------
    | STORE ORDER (CLEAN + GST READY)
    |--------------------------------------------------
    */
    public function storeOrder(Request $request)
    {
        DB::beginTransaction();

        try {

            $user = auth()->user();

            // 1. Cart Items
            $cartItems = \App\Models\Cart::with('product')
                ->where('user_id', $user->id)
                ->get();

            if ($cartItems->isEmpty()) {
                return back()->with('error', 'Cart is empty');
            }

            // 2. Subtotal Calculation
            $subtotal = 0;

            foreach ($cartItems as $item) {
                $subtotal += $item->product->selling_price * $item->quantity;
            }

            $discount = $request->discount ?? 0;
            $shipping = $request->shipping_charge ?? 0;
            $gstRate  = $request->gst_rate ?? 0;

            // 3. GST Calculation
            $taxableAmount = $subtotal - $discount;
            $gstAmount = ($taxableAmount * $gstRate) / 100;

            // 4. Grand Total
            $grandTotal = $taxableAmount + $shipping + $gstAmount;

            // 5. Create Order
            $order = Order::create([
                'user_id' => $user->id,
                'order_id' => 'ORD-' . date('Ymd') . rand(1000, 9999),

                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping_charge' => $shipping,

                // GST
                'gst_rate' => $gstRate,
                'gst_amount' => $gstAmount,

                'grand_total' => $grandTotal,

                'paid_amount' => 0,
                'due_amount' => $grandTotal,

                'payment_method' => $request->payment_method,
                'payment_type' => 'full',
                'payment_status' => 'pending',
                'order_status' => 'pending',
            ]);

            // 6. Order Items
            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'variant_id' => $item->variant_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->selling_price,
                    'total' => $item->product->selling_price * $item->quantity,
                ]);
            }

            // 7. Clear Cart
            \App\Models\Cart::where('user_id', $user->id)->delete();

            DB::commit();

            return redirect()
                ->route('order.success', $order->id)
                ->with('success', 'Order placed successfully');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------
    | ORDER LIST
    |--------------------------------------------------
    */
    public function index(Request $request)
    {
        $query = Order::with('user');

        if ($request->filled('search')) {
            $query->where('order_id', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('order_status', $request->status);
        }

        $orders = $query->latest()->paginate(10);

        return view('admin.order.index', compact('orders'));
    }

    /*
    |--------------------------------------------------
    | ORDER DETAILS
    |--------------------------------------------------
    */
    public function show(Order $order)
    {
        $order->load('user', 'items.product', 'orderPayments');

        return view('admin.order.show', compact('order'));
    }

    /*
    |--------------------------------------------------
    | ORDER STATUS UPDATE
    |--------------------------------------------------
    */
    public function statusUpdate(Request $request, Order $order)
    {
        $request->validate([
            'order_status' => 'required',
        ]);

        $order->update([
            'order_status' => $request->order_status,
        ]);

        return back()->with('success', 'Order Status Updated Successfully');
    }

    /*
    |--------------------------------------------------
    | PAYMENT STATUS UPDATE
    |--------------------------------------------------
    */
    public function paymentStatusUpdate(Request $request, Order $order)
    {
        $request->validate([
            'payment_status' => 'required',
        ]);

        $order->update([
            'payment_status' => $request->payment_status,
        ]);

        return back()->with('success', 'Payment Status Updated Successfully');
    }

    /*
    |--------------------------------------------------
    | INVOICE
    |--------------------------------------------------
    */
    public function invoice(Order $order)
    {
       $order->load([ 'user', 'items.product', 'items.variant', 'orderPayments', 'refunds' ]);

        return view('admin.order.invoice', compact('order'));
    }

    /*
    |--------------------------------------------------
    | PRINT INVOICE
    |--------------------------------------------------
    */
    public function printInvoice(Order $order)
    {
$order->load([ 'user', 'items.product', 'items.variant', 'orderPayments', 'refunds' ]);
        return view('admin.order.print', compact('order'));
    }

    /*
    |--------------------------------------------------
    | PDF DOWNLOAD
    |--------------------------------------------------
    */
    public function downloadPdf(Order $order)
    {
        $order->load('user', 'items.product');

        $pdf = Pdf::loadView('admin.order.invoice', compact('order'));

        return $pdf->download('invoice-' . $order->order_id . '.pdf');
    }

    /*
    |--------------------------------------------------
    | EXPORT CSV
    |--------------------------------------------------
    */
    public function export(Request $request)
    {
        $query = Order::with('user');

        if ($request->filled('search')) {
            $query->where('order_id', 'like', '%' . $request->search . '%');
        }

        $orders = $query->latest()->get();

        $fileName = 'orders_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');

            fputs($file, "\xEF\xBB\xBF");

            fputcsv($file, [
                'Order ID',
                'Customer Name',
                'Email',
                'Payment Method',
                'Status',
                'Grand Total',
                'Date',
            ]);

            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->order_id,
                    $order->user->name ?? 'Guest',
                    $order->user->email ?? '-',
                    $order->payment_method ?? '-',
                    $order->payment_status ?? '-',
                    $order->grand_total ?? 0,
                    $order->created_at->format('d-m-Y'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}