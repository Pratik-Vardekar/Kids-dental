<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\OrderNote;
use App\Models\OrderAddress;
use App\Models\OrderShippingAddress;
use App\Models\OrderBillingAddress;
use App\Models\Address;
use Illuminate\Http\Request;
use App\Models\Cart;
use Illuminate\Support\Facades\DB;
class OrderController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | My Orders
    |--------------------------------------------------------------------------
    */
    public function createOrder(Request $request)
    {
        $request->validate([
            'address_id' => 'required|exists:addresses,id',
            'payment_method' => 'required'
        ]);
    
        $user = auth('sanctum')->user();
    
        $cartItems = Cart::where('user_id', $user->id)
            ->with('product.gstSetting')
            ->get();
    
        if ($cartItems->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Cart is empty'
            ], 400);
        }
    
        $address = Address::where('id', $request->address_id)
            ->where('user_id', $user->id)
            ->first();
    
        if (!$address) {
            return response()->json([
                'status' => false,
                'message' => 'Address not found'
            ], 404);
        }
    
        DB::beginTransaction();
    
        try {
    
            $subtotal = 0;
            $gstTotal = 0;
    
            foreach ($cartItems as $item) {
    
                if (!$item->product) {
                    continue;
                }
    
                $price = $item->product->selling_price ?: $item->product->price;
                $taxInfo = $item->product->getInclusiveTaxInfo((float) $price);
    
                $subtotal += ($price * $item->quantity);
                $gstTotal += (float) ($taxInfo['tax_amount'] ?? 0) * $item->quantity;
            }
    
            $gstTotal = round($gstTotal, 2);
            $grandTotal = round($subtotal + $gstTotal, 2);
    
            $order = Order::create([
                'user_id' => $user->id,
                'order_id' => Order::temporaryOrderNumber(),

                'subtotal' => $subtotal,
                'discount' => 0,
                'shipping_charge' => 0,
                'cgst_amount' => round($gstTotal / 2, 2),
                'sgst_amount' => round($gstTotal / 2, 2),
                'igst_amount' => 0,
                'taxable_amount' => max(0, round($subtotal - $gstTotal, 2)),
                'grand_total' => $grandTotal,

                'paid_amount' => 0,
                'due_amount' => $grandTotal,

                'payment_method' => 'razorpay',
                'payment_status' => 'pending',
                'order_status' => 'pending',
            ]);
    
            // Save Shipping Address
            OrderAddress::create([
                'order_id' => $order->id,
                'type' => 'shipping',
    
                'name' => $address->name,
                'phone' => $address->phone,
                'alternate_phone' => $address->alternate_phone,
    
                'address' => $address->address,
                'landmark' => $address->landmark,
    
                'city' => $address->city,
                'state' => $address->state,
                'country' => $address->country,
                'pincode' => $address->pincode,
            ]);
    
            // Create Order Items
            foreach ($cartItems as $item) {
    
                if (!$item->product) {
                    continue;
                }
    
                $price = $item->product->selling_price ?: $item->product->price;
                $var = $item->variant ?: ($item->variant_id ? \App\Models\ProductVariant::find($item->variant_id) : null);
                $arch = $var?->arch;
                $size = $var?->size !== null && $var?->size !== '' ? (string) $var->size : null;
                $sku = $var?->sku ?: $item->product->sku;
    
                OrderItem::create([
                    'order_id'      => $order->id,
                    'product_id'    => $item->product_id,
                    'variant_id'    => $item->variant_id,
                    'product_name'  => $item->product->name,
                    'product_image' => $item->product->thumbnail,
                    'product_sku'   => $sku,
                    'arch'          => $arch,
                    'size'          => $size,
                    'quantity'      => $item->quantity,
                    'price'         => $price,
                    'total'         => ($price * $item->quantity)
                ]);
            }
    
            // Clear Cart
            Cart::where('user_id', $user->id)->delete();

            $order->assignOrderNumber();
    
            DB::commit();
    
            return response()->json([
                'status' => true,
                'message' => 'Order created successfully',
                'data' => [
                    'id' => $order->id,
                    'order_id' => $order->display_order_id,
                    'subtotal' => $order->subtotal,
                    'gst_total' => round((float) ($order->cgst_amount ?? 0) + (float) ($order->sgst_amount ?? 0) + (float) ($order->igst_amount ?? 0), 2),
                    'grand_total' => $order->grand_total,
                    'payment_method' => $order->payment_method,
                    'payment_status' => $order->payment_status,
                    'order_status' => $order->order_status
                ]
            ]);
    
        } catch (\Exception $e) {
    
            DB::rollBack();
    
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function paymentStatus($id)
    {
        $user = auth()->user();

        $order = Order::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'order_id' => $order->display_order_id,
                'payment_status' => $order->payment_status,
                'order_status' => $order->order_status,
                'grand_total' => $order->grand_total,
                'paid_amount' => $order->paid_amount,
                'due_amount' => $order->due_amount
            ]
        ]);
    }
public function myOrders()
{
    $userId = auth('sanctum')->id() ?: auth()->id();
    $orders = Order::with(['items.product', 'items.variant'])
        ->where('user_id', $userId)
        ->where('order_status', '!=', 'pending')
        ->latest()
        ->get();

    $data = $orders->map(function ($order) {
        return [
            'order_id'       => $order->display_order_id,
            'invoice_no'     => $order->display_invoice_no,
            'order_date'     => $order->created_at->format('d M Y'),
            'grand_total'    => $order->grand_total,
            'payment_status' => $order->payment_status,
            'order_status'   => $order->order_status,
            'items_count'    => $order->items->count(),
            'invoice_url'    => route('user.invoice', $order->id),
            'products'       => $order->items->map(function ($item) {
                $product = $item->product;
                $variant = $item->variant;
                $arch = $item->arch ?: ($variant?->arch ?: null);
                $size = $item->size !== null && $item->size !== '' ? (string) $item->size : ($variant?->size !== null && $variant->size !== '' ? (string) $variant->size : null);
                $sku = $item->product_sku ?: ($variant?->sku ?: ($product?->sku ?? null));

                $variantTitle = null;
                if ($arch || $size) {
                    $parts = array_filter([$arch, $size ? 'Size ' . $size : null]);
                    $variantTitle = implode(' - ', $parts);
                } elseif ($variant) {
                    $variantTitle = $variant->variant_title ?? ($variant->sku ?? null);
                }

                $img = $variant?->image ?: ($product?->thumbnail ?: $item->product_image);

                return [
                    'id'            => $item->id,
                    'product_id'    => $item->product_id,
                    'variant_id'    => $item->variant_id,
                    'product_name'  => $product?->name ?? ($item->product_name ?? ''),
                    'slug'          => $product?->slug ?? '',
                    'sku'           => $sku,
                    'arch'          => $arch,
                    'size'          => $size,
                    'selected_arch' => $arch,
                    'selected_size' => $size,
                    'variant_title' => $variantTitle,
                    'product_image' => $img ? url('backend/assets/images/product/' . $img) : null,
                    'quantity'      => (int) $item->quantity,
                    'price'         => (float) $item->price,
                    'total'         => (float) $item->total,
                ];
            }),
        ];
    });

    return response()->json([
        'status'  => true,
        'message' => 'My orders fetched successfully',
        'data'    => $data
    ]);
}
    

     
    /*
    |--------------------------------------------------------------------------
    | Order View
    |--------------------------------------------------------------------------
    */

    // public function orderView($id)
    // {
    //     $order = Order::with([
    //             'items.product',
    //             'billingAddress',
    //             'shippingAddress',
    //             'notes'
    //         ])
    //         ->where('user_id', auth()->id())
    //         ->where('id', $id)
    //         ->first();

    //     if (!$order) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Order not found'
    //         ]);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'data' => $order
    //     ]);
    // }

   public function orderView($id)
{
    $userId = auth('sanctum')->id() ?: auth()->id();

    $order = Order::with([
        'user',
        'shippingAddress',
        'items.product',
        'items.variant'
    ])
    ->where(function ($q) use ($userId) {
        if ($userId) {
            $q->where('user_id', $userId);
        }
    })
    ->where('id', $id)
    ->first();

    if (!$order) {
        return response()->json([
            'status' => false,
            'message' => 'Order not found'
        ], 404);
    }

    $address = $order->shippingAddress;

    // Calculate subtotal from order items
    $subtotal = $order->items->sum(function ($item) {
        return $item->price * $item->quantity;
    });

    // Calculate coupon discount exactly like checkout
    $couponDiscount = 0;

    if ($order->coupon_code) {

        $coupon = DB::table('coupons')
            ->where('code', $order->coupon_code)
            ->where('status', 1)
            ->first();

        if ($coupon && $subtotal >= $coupon->minimum_amount) {

            if ($coupon->type === 'fixed') {

                $couponDiscount = (float) $coupon->value;

            } else {

                $couponDiscount = ($subtotal * $coupon->value) / 100;

                if ($coupon->maximum_discount) {
                    $couponDiscount = min(
                        $couponDiscount,
                        $coupon->maximum_discount
                    );
                }
            }

            $couponDiscount = min($couponDiscount, $subtotal);
        }
    }

    $couponDiscount = round($couponDiscount, 2);

    // Calculate full payment discount exactly like checkout
    $fullPaymentDiscount = 0;

    if ($order->payment_type === 'full') {

        $amountAfterCoupon =
            $subtotal
            + ($order->shipping_charge ?? 0)
            + ($order->cgst_amount ?? 0)
            + ($order->sgst_amount ?? 0)
            + ($order->igst_amount ?? 0)
            - $couponDiscount;

        $fullPaymentDiscount = round($amountAfterCoupon * 0.05, 2);
    }

    $totalDiscount = round(
        $couponDiscount + $fullPaymentDiscount,
        2
    );
    $gstTotal = round(
        (float) ($order->cgst_amount ?? 0)
        + (float) ($order->sgst_amount ?? 0)
        + (float) ($order->igst_amount ?? 0),
        2
    );

    return response()->json([
        'status' => true,
        'data' => [

            // ORDER INFO
            'order_id' => $order->order_id,
            'delivery_date' => $order->shipped_at?->format('d M Y'),

            // USER INFO
            'user_name' => $order->user?->name ?? '',
            'contact_number' => $order->user?->phone ?? '',

            // DELIVERY ADDRESS
            'delivery_address' => [
                'name' => $address?->name ?? '',
                'phone' => $address?->phone ?? '',
                'address' => $address?->address ?? '',
                'city' => $address?->city ?? '',
                'state' => $address?->state ?? '',
                'pincode' => $address?->pincode ?? '',
                'country' => $address?->country ?? '',
            ],

            // PAYMENT INFO
            'subtotal' => $subtotal,
            'coupon_discount' => $couponDiscount,
            'full_payment_discount' => $fullPaymentDiscount,
            'total_discount' => $totalDiscount,
            'gst_total' => $gstTotal,
            'shipping_charge' => $order->shipping_charge,
            'grand_total' => $order->grand_total,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,

            // INVOICE
            'invoice_url' => route('user.invoice', $order->id),

            // PRODUCTS
            'products' => $order->items->map(function ($item) {

                $product = $item->product;
                $variant = $item->variant;

                // Resolve arch and size from item columns, variant relation, or SKU
                $arch = $item->arch ?: ($variant?->arch ?: null);
                $size = $item->size !== null && $item->size !== '' ? (string) $item->size : ($variant?->size !== null && $variant->size !== '' ? (string) $variant->size : null);
                $sku = $item->product_sku ?: ($variant?->sku ?: ($product?->sku ?? null));

                // Fallback to variant attributes if still missing
                if ((!$arch || !$size) && $variant && $variant->relationLoaded('attributes')) {
                    foreach ($variant->attributes as $attrRel) {
                        $attrName = strtolower($attrRel->attribute?->name ?? '');
                        $attrVal = $attrRel->attributeValue?->value ?? '';
                        if ($attrName === 'arch' && !$arch) $arch = $attrVal;
                        if ($attrName === 'size' && !$size) $size = (string) $attrVal;
                    }
                }

                // Fallback parse from SKU if still missing
                if ((!$arch || !$size) && $sku) {
                    $skuUpper = strtoupper($sku);
                    if (!$arch) {
                        if (str_contains($skuUpper, '-LL-') || str_ends_with($skuUpper, '-LL')) $arch = 'Lower Left';
                        elseif (str_contains($skuUpper, '-LR-') || str_ends_with($skuUpper, '-LR')) $arch = 'Lower Right';
                        elseif (str_contains($skuUpper, '-UL-') || str_ends_with($skuUpper, '-UL')) $arch = 'Upper Left';
                        elseif (str_contains($skuUpper, '-UR-') || str_ends_with($skuUpper, '-UR')) $arch = 'Upper Right';
                    }
                    if (!$size && preg_match('/-(?:LL|LR|UL|UR)-([0-9]+)/', $skuUpper, $matches)) {
                        $size = $matches[1];
                    }
                }

                $variantTitle = null;
                if ($arch || $size) {
                    $parts = array_filter([$arch, $size ? 'Size ' . $size : null]);
                    $variantTitle = implode(' - ', $parts);
                } elseif ($variant) {
                    $variantTitle = $variant->variant_title ?? ($variant->sku ?? null);
                }

                $productImage = null;
                if ($variant?->image) {
                    $productImage = url('backend/assets/images/product/' . $variant->image);
                } elseif ($product?->thumbnail) {
                    $productImage = url('backend/assets/images/product/' . $product->thumbnail);
                } elseif ($item->product_image) {
                    $productImage = url('backend/assets/images/product/' . $item->product_image);
                }

                return [
                    'id'            => $item->id,
                    'product_id'    => $item->product_id,
                    'variant_id'    => $item->variant_id,
                    'product_name'  => $product?->name ?? ($item->product_name ?? ''),
                    'slug'          => $product?->slug ?? '',
                    'sku'           => $sku,
                    'product_sku'   => $sku,
                    'arch'          => $arch,
                    'size'          => $size,
                    'selected_arch' => $arch,
                    'selected_size' => $size,
                    'variant_title' => $variantTitle,
                    'product_image' => $productImage,
                    'quantity'      => (int) $item->quantity,
                    'price'         => (float) $item->price,
                    'total'         => (float) $item->total,
                    'variant'       => $variant ? [
                        'id'            => $variant->id,
                        'product_id'    => $variant->product_id,
                        'arch'          => $arch,
                        'size'          => $size,
                        'selected_arch' => $arch,
                        'selected_size' => $size,
                        'sku'           => $sku,
                        'price'         => (float) $variant->price,
                        'selling_price' => (float) ($variant->selling_price ?? $variant->price),
                        'variant_title' => $variantTitle,
                    ] : null,
                ];
            }),
        ]
    ]);
}
    /*
    |--------------------------------------------------------------------------
    | Cancel Order
    |--------------------------------------------------------------------------
    */

    public function cancelOrder($id)
    {
        $order = Order::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Order not found'
            ]);
        }

        // Prevent cancel if delivered
        if ($order->order_status === 'delivered') {
            return response()->json([
                'status' => false,
                'message' => 'Delivered order cannot be cancelled'
            ]);
        }

        $order->update([
            'order_status' => 'cancelled',
            'cancelled_at' => now()
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Order cancelled successfully'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Track Order
    |--------------------------------------------------------------------------
    */

    public function trackOrder($id)
    {
        $order = Order::where('id', $id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Order not found'
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'tracking_id' => $order->tracking_id,
                'courier_name' => $order->courier_name,
                'tracking_url' => $order->tracking_url,
                'order_status' => $order->order_status,
                'shipped_at' => $order->shipped_at,
                'delivered_at' => $order->delivered_at
            ]
        ]);
    }
}
