<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Order</title>
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
                                        NEW ORDER
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
                                    Hi, <span style="color:#D63D3D;">Admin</span>.
                                </h1>
                                <p style="margin:16px 0 0;font-size:16px;line-height:1.6;color:#555;">
                                    A new order has been placed on the website.
                                </p>

                                <div style="margin-top:24px;">
                                    <span style="display:inline-block;background:#EBF5F0;color:#2A6D47;padding:8px 16px;border-radius:24px;font-size:14px;">
                                        ✓ Order #{{ $order->display_order_id }}
                                    </span>
                                </div>
                            </div>

                            <div style="background:#FBFaf7;border:1px solid #EBE8DF;border-radius:4px;margin-bottom:24px;">
                                <div style="padding:16px 20px;border-bottom:1px solid #EBE8DF;background:#F5F2EA;">
                                    <h2 style="margin:0;font-size:18px;font-weight:bold;color:#333;font-family:Georgia, serif;">Customer Details</h2>
                                </div>
                                <div style="padding:20px;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;color:#555;">
                                        <tr>
                                            <td style="padding:6px 0;width:40%;">Name</td>
                                            <td style="padding:6px 0;text-align:right;font-weight:600;color:#333;">{{ $order->user->name ?? ($order->shippingAddress->name ?? '-') }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:6px 0;vertical-align:top;">Email</td>
                                            <td style="padding:6px 0;text-align:right;font-weight:600;color:#2563EB;">
                                                <a href="mailto:{{ $order->user->email ?? '' }}" style="color:#2563EB;text-decoration:none;">{{ $order->user->email ?? '-' }}</a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:6px 0;">Phone</td>
                                            <td style="padding:6px 0;text-align:right;font-weight:600;color:#333;">{{ $order->shippingAddress->phone ?? ($order->user->phone ?? '-') }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <div style="background:#FBFaf7;border:1px solid #EBE8DF;border-radius:4px;margin-bottom:24px;">
                                <div style="padding:16px 20px;border-bottom:1px solid #EBE8DF;background:#F5F2EA;">
                                    <h2 style="margin:0;font-size:18px;font-weight:bold;color:#333;font-family:Georgia, serif;">Order Summary</h2>
                                </div>
                                <div style="padding:20px;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;color:#555;">
                                        <tr>
                                            <td style="padding:6px 0;width:50%;">Payment Method</td>
                                            <td style="padding:6px 0;text-align:right;font-weight:600;color:#333;">
                                                @if(($order->payment_method ?? '') === 'razorpay')
                                                Razorpay ({{ ucfirst($order->payment_status ?? 'pending') }})
                                                @else
                                                Cash on Delivery
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:6px 0;">Order Status</td>
                                            <td style="padding:6px 0;text-align:right;font-weight:600;color:#333;">{{ ucfirst($order->order_status ?? 'pending') }}</td>
                                        </tr>

                                        @if($order->items->isNotEmpty())
                                        <tr>
                                            <td colspan="2" style="padding:16px 0 8px 0;border-bottom:1px solid #EBE8DF;"><strong>Items</strong></td>
                                        </tr>
                                        @foreach($order->items as $item)
                                        <tr>
                                            <td style="padding:8px 0;">{{ $item->product->name ?? 'Product' }} <span style="color:#888;">x{{ $item->quantity }}</span></td>
                                            <td style="padding:8px 0;text-align:right;">₹{{ number_format((float) $item->total, 2) }}</td>
                                        </tr>
                                        @endforeach
                                        @endif

                                        <tr>
                                            <td colspan="2" style="padding:8px 0;border-bottom:1px solid #EBE8DF;"></td>
                                        </tr>

                                        <tr>
                                            <td style="padding:8px 0 4px 0;">Subtotal</td>
                                            <td style="padding:8px 0 4px 0;text-align:right;">₹{{ number_format((float) ($orderSummary['items_subtotal'] ?? $order->subtotal), 2) }}</td>
                                        </tr>
                                        @if((float) ($orderSummary['discount_amount'] ?? $order->discount) > 0)
                                        <tr>
                                            <td style="padding:4px 0;">Discount</td>
                                            <td style="padding:4px 0;text-align:right;color:#D63D3D;">-₹{{ number_format((float) ($orderSummary['discount_amount'] ?? $order->discount), 2) }}</td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <td style="padding:4px 0;">Shipping</td>
                                            <td style="padding:4px 0;text-align:right;">₹{{ number_format((float) ($orderSummary['shipping_charge'] ?? $order->shipping_charge), 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:4px 0;">{{ $orderSummary['gst_rate_label'] ?? 'GST (inclusive)' }}</td>
                                            <td style="padding:4px 0;text-align:right;">₹{{ number_format((float) ($orderSummary['gst_amount'] ?? 0), 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:12px 0 0;font-size:16px;font-weight:bold;color:#333;">Grand Total</td>
                                            <td style="padding:12px 0 0;text-align:right;font-size:16px;font-weight:bold;color:#2A6D47;">₹{{ number_format((float) ($orderSummary['grand_total'] ?? $order->grand_total), 2) }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            @php $shipping = $order->shippingAddress; @endphp
                            @if($shipping)
                            <div style="background:#FBFaf7;border:1px solid #EBE8DF;border-radius:4px;margin-bottom:24px;">
                                <div style="padding:16px 20px;border-bottom:1px solid #EBE8DF;background:#F5F2EA;">
                                    <h2 style="margin:0;font-size:18px;font-weight:bold;color:#333;font-family:Georgia, serif;">Shipping Address</h2>
                                </div>
                                <div style="padding:20px;font-size:14px;line-height:1.6;color:#555;">
                                    <strong>{{ $shipping->name }}</strong><br>
                                    @if(!empty($shipping->phone)){{ $shipping->phone }}<br>@endif
                                    {{ $shipping->address }}
                                    @if(!empty($shipping->landmark)), {{ $shipping->landmark }}@endif<br>
                                    {{ $shipping->city }}@if(!empty($shipping->state)), {{ $shipping->state }}@endif
                                    @if(!empty($shipping->pincode)) — {{ $shipping->pincode }}@endif
                                </div>
                            </div>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>