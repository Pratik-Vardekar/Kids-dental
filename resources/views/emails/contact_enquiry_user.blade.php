<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You for Contacting Kids-e-Dental</title>
</head>

<body
    style="margin:0;padding:0;background:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1e293b;-webkit-font-smoothing:antialiased;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:40px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                    style="max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05),0 2px 4px -2px rgba(0,0,0,0.05);border:1px solid #e2e8f0;">

                    <!-- Top Brand Header -->
                    <tr>
                        <td style="background:#ffffff;padding:24px 32px;border-bottom:1px solid #e2e8f0;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="left" style="vertical-align:middle;">
                                        <a href="{{ url('/') }}" target="_blank"
                                            style="text-decoration:none;display:inline-block;">
                                            <img src="{{ asset('backend/assets/images/KidseDental_Logo.png') }}"
                                                alt="Kids-e-Dental"
                                                style="height:36px;max-height:36px;width:auto;display:block;border:0;">
                                        </a>
                                    </td>
                                    <td align="right" style="vertical-align:middle;">
                                        <span
                                            style="display:inline-block;background:#ecfdf5;color:#059669;font-size:11px;font-weight:700;letter-spacing:1px;padding:6px 14px;border-radius:20px;text-transform:uppercase;border:1px solid #a7f3d0;">
                                            Received
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Main Content Body -->
                    <tr>
                        <td style="padding:36px 32px 28px;">

                            <!-- Greeting & Intro -->
                            <div style="margin-bottom:28px;">
                                <h1
                                    style="margin:0 0 8px;font-size:22px;font-weight:700;color:#0f172a;line-height:1.3;">
                                    Hi {{ explode(' ', trim($enquiry->full_name))[0] }},
                                </h1>
                                <p style="margin:0;font-size:15px;line-height:1.6;color:#64748b;">
                                    Thank you for reaching out to us! We have received your enquiry and our team will
                                    get back to you shortly.
                                </p>
                            </div>

                            <!-- Summary Card -->
                            <div
                                style="background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;margin-bottom:24px;">
                                <div style="padding:14px 20px;background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                                    <h2
                                        style="margin:0;font-size:14px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:0.5px;">
                                        Your Enquiry Summary
                                    </h2>
                                </div>
                                <div style="padding:18px 20px;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                        style="font-size:14px;">
                                        <tr>
                                            <td style="padding:8px 0;width:35%;color:#64748b;font-weight:500;">Name</td>
                                            <td style="padding:8px 0;text-align:right;font-weight:600;color:#0f172a;">
                                                {{ $enquiry->full_name }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:8px 0;color:#64748b;font-weight:500;">Email</td>
                                            <td style="padding:8px 0;text-align:right;font-weight:600;color:#cadc26;">
                                                {{ $enquiry->email }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:8px 0;color:#64748b;font-weight:500;">Phone</td>
                                            <td style="padding:8px 0;text-align:right;font-weight:600;color:#0f172a;">
                                                {{ $enquiry->contact_no }}
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            @if (filled($enquiry->comment))
                                <!-- Message Card -->
                                <div
                                    style="background:#ffffff;border:1px solid #e2e8f0;border-left:4px solid #059669;border-radius:8px;overflow:hidden;margin-bottom:28px;">
                                    <div style="padding:14px 20px;background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                                        <h2
                                            style="margin:0;font-size:14px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:0.5px;">
                                            Your Message
                                        </h2>
                                    </div>
                                    <div
                                        style="padding:18px 20px;font-size:14px;line-height:1.7;color:#334155;white-space:pre-line;">
                                        {!! nl2br(e($enquiry->comment)) !!}
                                    </div>
                                </div>
                            @endif

                            <div style="text-align:center;padding-top:10px;">
                                <p style="margin:0 0 4px;font-size:14px;color:#64748b;">
                                    Need immediate assistance?
                                </p>
                                <p style="margin:0;font-size:14px;font-weight:600;">
                                    Warm regards,<br>
                                    <span style="color:#0f172a;">Team Kids-e-Dental</span>
                                </p>
                            </div>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td
                            style="background:#f8fafc;padding:24px 32px;border-top:1px solid #e2e8f0;text-align:center;">
                            <p style="margin:0 0 6px;font-size:13px;color:#64748b;">
                                <strong>Kids-e-Dental</strong> • Premium Pediatric Dental Supplies & Solutions
                            </p>
                            <p style="margin:0;font-size:12px;color:#94a3b8;">
                                © {{ date('Y') }} Kids-e-Dental. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>
