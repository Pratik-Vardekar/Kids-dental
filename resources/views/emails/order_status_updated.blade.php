<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status Update</title>
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
                                        ORDER STATUS
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
                                    Hi, <span style="color:#D63D3D;">{{ $isAdmin ? 'Admin' : ($order->user->name ?? 'Customer') }}</span>.
                                </h1>
                                <p style="margin:16px 0 0;font-size:16px;line-height:1.6;color:#555;">
                                    @if($isAdmin)
                                    The status for order <strong>#{{ $order->display_order_id }}</strong> has been updated to <strong>{{ $status }}</strong>.
                                    @else
                                    Your order <strong>#{{ $order->display_order_id }}</strong> status has been updated to <strong>{{ $status }}</strong>.
                                    @endif
                                </p>

                                <div style="margin-top:24px;">
                                    <span style="display:inline-block;background:#EBF5F0;color:#2A6D47;padding:8px 16px;border-radius:24px;font-size:14px;">
                                        ✓ Status: {{ $status }}
                                    </span>
                                </div>
                            </div>

                            <div style="background:#FBFaf7;border:1px solid #EBE8DF;border-radius:4px;">
                                <div style="padding:16px 20px;border-bottom:1px solid #EBE8DF;background:#F5F2EA;">
                                    <h2 style="margin:0;font-size:18px;font-weight:bold;color:#333;font-family:Georgia, serif;">Order Details</h2>
                                </div>
                                <div style="padding:20px;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;color:#555;">
                                        <tr>
                                            <td style="padding:6px 0;width:40%;">Order No.</td>
                                            <td style="padding:6px 0;text-align:right;font-weight:600;color:#333;">{{ $order->display_order_id }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:6px 0;">Date</td>
                                            <td style="padding:6px 0;text-align:right;font-weight:600;color:#333;">{{ $order->created_at->format('d M Y, h:i A') }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:6px 0;vertical-align:top;">Billed to</td>
                                            <td style="padding:6px 0;text-align:right;font-weight:600;color:#2563EB;">
                                                <a href="mailto:{{ $order->user->email ?? '' }}" style="color:#2563EB;text-decoration:none;">{{ $order->user->email ?? '' }}</a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:6px 0;">Total Amount</td>
                                            <td style="padding:6px 0;text-align:right;font-weight:600;color:#333;">₹{{ number_format((float) $order->grand_total, 2) }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <p style="margin:40px 0 0;font-size:13px;line-height:1.6;color:#888;text-align:center;">
                                @if(!$isAdmin)
                                If you have any questions, just reply to this email. Thank you for shopping with Kids-e-Dental!
                                @endif
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>