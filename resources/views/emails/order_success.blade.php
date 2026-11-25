<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed</title>
</head>

<body style="margin:0;padding:0;background:#F9F9F8;font-family:'Helvetica Neue', Helvetica, Arial, sans-serif;color:#333;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F9F9F8;padding:40px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border:1px solid #EBE8DF;">
                    <!-- Header -->
                    <tr>
                        <td style="background:#F5F2EA;padding:24px;border-bottom:1px solid #EBE8DF;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="left" style="vertical-align:middle;">
                                        <img src="{{ asset('backend/assets/images/KidseDental_Logo.png') }}" alt="Kids-e-Dental" style="height:32px;display:block;">
                                    </td>
                                    <td align="right" style="vertical-align:middle;font-size:11px;letter-spacing:1.5px;color:#4F6253;text-transform:uppercase;">
                                        ORDER CONFIRMED
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding:40px;">
                            <div style="text-align:center;margin-bottom:32px;">
                                <h1 style="margin:0;font-size:26px;font-weight:400;color:#222;font-family:Georgia, serif;">
                                    Thank you, <span style="color:#D63D3D;">{{ $order->user->name ?? 'Customer' }}</span>.
                                </h1>
                                <p style="margin:16px 0 0;font-size:16px;line-height:1.6;color:#555;">
                                    Your order has been received successfully. We’ll start packing your items shortly.
                                    @if(!empty($order->invoice_no))
                                    Your invoice <strong>{{ $order->display_invoice_no }}</strong> is attached.
                                    @else
                                    Your invoice PDF is attached.
                                    @endif
                                </p>

                                <div style="margin-top:24px;">
                                    <span style="display:inline-block;background:#EBF5F0;color:#2A6D47;padding:8px 16px;border-radius:24px;font-size:14px;">
                                        ✓ Order #{{ $order->display_order_id }} Confirmed
                                    </span>
                                </div>
                            </div>

                            <div style="background:#FBFaf7;border:1px solid #EBE8DF;border-radius:4px;margin-bottom:24px;">
                                <div style="padding:16px 20px;border-bottom:1px solid #EBE8DF;background:#F5F2EA;">
                                    <h2 style="margin:0;font-size:18px;font-weight:bold;color:#333;font-family:Georgia, serif;">Order Invoice</h2>
                                </div>
                                <div style="padding:20px;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;color:#555;">
                                        <tr>
                                            <td style="padding:6px 0;width:40%;">Invoice no.</td>
                                            <td style="padding:6px 0;text-align:right;font-weight:600;color:#333;">{{ $order->display_invoice_no ?: $order->display_order_id }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:6px 0;">Date</td>
                                            <td style="padding:6px 0;text-align:right;font-weight:600;color:#333;">{{ $order->created_at ? $order->created_at->format('d M Y, h:i A') : '' }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:6px 0;vertical-align:top;">Billed to</td>
                                            <td style="padding:6px 0;text-align:right;font-weight:600;color:#2563EB;">
                                                <a href="mailto:{{ $order->user->email ?? '' }}" style="color:#2563EB;text-decoration:none;">{{ $order->user->email ?? '' }}</a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:6px 0;">Payment method</td>
                                            <td style="padding:6px 0;text-align:right;font-weight:600;color:#333;">
                                                @if(($order->payment_method ?? '') === 'razorpay')
                                                Razorpay ({{ ucfirst($order->payment_status ?? 'pending') }})
                                                @else
                                                Cash on Delivery
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:6px 0;">Order status</td>
                                            <td style="padding:6px 0;text-align:right;font-weight:600;color:#333;">{{ ucfirst($order->order_status ?? 'pending') }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            @if($order->items->isNotEmpty())
                            <h2 style="margin:0 0 12px;font-size:16px;font-family:Georgia, serif;">Items</h2>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:20px;font-size:14px;color:#555;">
                                <thead>
                                    <tr>
                                        <th align="left" style="padding:8px 0;border-bottom:1px solid #EBE8DF;color:#888;font-weight:normal;">Product</th>
                                        <th align="center" style="padding:8px 0;border-bottom:1px solid #EBE8DF;color:#888;font-weight:normal;">Qty</th>
                                        <th align="right" style="padding:8px 0;border-bottom:1px solid #EBE8DF;color:#888;font-weight:normal;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->items as $item)
                                    <tr>
                                        <td style="padding:10px 0;border-bottom:1px solid #F5F2EA;">
                                            {{ $item->product->name ?? 'Product' }}
                                        </td>
                                        <td align="center" style="padding:10px 0;border-bottom:1px solid #F5F2EA;">{{ $item->quantity }}</td>
                                        <td align="right" style="padding:10px 0;border-bottom:1px solid #F5F2EA;font-weight:600;color:#333;">₹{{ number_format((float) $item->total, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @endif

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;color:#555;margin-bottom:24px;">
                                <tr>
                                    <td style="padding:4px 0;">Subtotal</td>
                                    <td style="padding:4px 0;text-align:right;font-weight:600;color:#333;">₹{{ number_format((float) ($orderSummary['items_subtotal'] ?? $order->subtotal), 2) }}</td>
                                </tr>
                                @if((float) ($orderSummary['discount_amount'] ?? $order->discount) > 0)
                                <tr>
                                    <td style="padding:4px 0;">Discount</td>
                                    <td style="padding:4px 0;text-align:right;font-weight:600;color:#D63D3D;">-₹{{ number_format((float) ($orderSummary['discount_amount'] ?? $order->discount), 2) }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="padding:4px 0;">Shipping</td>
                                    <td style="padding:4px 0;text-align:right;font-weight:600;color:#333;">₹{{ number_format((float) ($orderSummary['shipping_charge'] ?? $order->shipping_charge), 2) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:4px 0;">{{ $orderSummary['gst_rate_label'] ?? 'GST (inclusive)' }}</td>
                                    <td style="padding:4px 0;text-align:right;font-weight:600;color:#333;">₹{{ number_format((float) ($orderSummary['gst_amount'] ?? 0), 2) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0 0;font-size:16px;font-weight:bold;color:#333;">Grand total</td>
                                    <td style="padding:10px 0 0;text-align:right;font-size:16px;font-weight:bold;color:#2A6D47;">₹{{ number_format((float) ($orderSummary['grand_total'] ?? $order->grand_total), 2) }}</td>
                                </tr>
                            </table>

                            @php $shipping = $order->shippingAddress ?? $order->addresses->where('type', 'shipping')->first(); @endphp
                            @if($shipping)
                            <h2 style="margin:0 0 8px;font-size:16px;font-family:Georgia, serif;">Shipping address</h2>
                            <p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#555;">
                                {{ $shipping->name }}<br>
                                @if(!empty($shipping->phone)){{ $shipping->phone }}<br>@endif
                                {{ $shipping->address }}
                                @if(!empty($shipping->landmark)), {{ $shipping->landmark }}@endif<br>
                                {{ $shipping->city }}@if(!empty($shipping->state)), {{ $shipping->state }}@endif
                                @if(!empty($shipping->pincode)) — {{ $shipping->pincode }}@endif
                            </p>
                            @endif

                            <p style="margin:0;font-size:13px;line-height:1.6;color:#888;text-align:center;border-top:1px solid #EBE8DF;padding-top:20px;">
                                If you have any questions, just reply to this email. Thank you for shopping with Kids-e-Dental!
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>