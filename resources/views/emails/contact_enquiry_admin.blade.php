<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Contact Enquiry — #{{ $enquiry->id }}</title>
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
                                            style="display:inline-block;background:#eff6ff;color:#e5eb25;font-size:11px;font-weight:700;letter-spacing:1px;padding:6px 14px;border-radius:20px;text-transform:uppercase;border:1px solid #dbeafe;">
                                            New Enquiry
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
                                    New Contact Enquiry Received
                                </h1>
                                <p style="margin:0;font-size:15px;line-height:1.6;color:#64748b;">
                                    A customer has submitted an enquiry through the website contact form.
                                </p>
                                <div style="margin-top:14px;">
                                    <span
                                        style="display:inline-block;background:#f8fafc;color:#475569;padding:6px 12px;border-radius:6px;font-size:12px;font-weight:600;border:1px solid #e2e8f0;">
                                        Enquiry #{{ $enquiry->id }} •
                                        {{ optional($enquiry->created_at)->format('d M Y, h:i A') }}
                                    </span>
                                </div>
                            </div>

                            <!-- Customer Information Card -->
                            <div
                                style="background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;margin-bottom:24px;">
                                <div style="padding:14px 20px;background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                                    <h2
                                        style="margin:0;font-size:14px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:0.5px;">
                                        Customer Details
                                    </h2>
                                </div>
                                <div style="padding:18px 20px;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                        style="font-size:14px;">
                                        <tr>
                                            <td style="padding:8px 0;width:35%;color:#64748b;font-weight:500;">Full Name
                                            </td>
                                            <td style="padding:8px 0;text-align:right;font-weight:600;color:#0f172a;">
                                                {{ $enquiry->full_name }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:8px 0;color:#64748b;font-weight:500;">Email Address</td>
                                            <td style="padding:8px 0;text-align:right;font-weight:600;">
                                                <a href="mailto:{{ $enquiry->email }}"
                                                    style="color:#cadc26;text-decoration:none;">
                                                    {{ $enquiry->email }}
                                                </a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:8px 0;color:#64748b;font-weight:500;">Phone Number</td>
                                            <td style="padding:8px 0;text-align:right;font-weight:600;">
                                                <a href="tel:{{ preg_replace('/[^\d+]/', '', $enquiry->contact_no) }}"
                                                    style="color:#0f172a;text-decoration:none;">
                                                    {{ $enquiry->contact_no }}
                                                </a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="padding:8px 0;color:#64748b;font-weight:500;">Submitted At</td>
                                            <td style="padding:8px 0;text-align:right;font-weight:600;color:#0f172a;">
                                                {{ optional($enquiry->created_at)->format('l, d M Y - h:i A') }}
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <!-- Comment / Message Card -->
                            <div
                                style="background:#ffffff;border:1px solid #e2e8f0;border-left:4px solid #e5eb25;border-radius:8px;overflow:hidden;margin-bottom:28px;">
                                <div style="padding:14px 20px;background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                                    <h2
                                        style="margin:0;font-size:14px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:0.5px;">
                                        Message / Comment
                                    </h2>
                                </div>
                                <div
                                    style="padding:18px 20px;font-size:14px;line-height:1.7;color:#334155;white-space:pre-line;">
                                    @if (filled($enquiry->comment))
                                        {!! nl2br(e($enquiry->comment)) !!}
                                    @else
                                        <em style="color:#94a3b8;">No additional comment provided.</em>
                                    @endif
                                </div>
                            </div>

                            <!-- Call to Action Buttons -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                style="margin-bottom:10px;">
                                <tr>
                                    <td align="center">
                                        <table role="presentation" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding-right:12px;">
                                                    <a href="mailto:{{ $enquiry->email }}?subject={{ urlencode('Re: Your Enquiry to Kids-e-Dental') }}"
                                                        style="background:#cadc26;color:#ffffff;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;font-size:14px;">
                                                        Reply to {{ explode(' ', trim($enquiry->full_name))[0] }}
                                                    </a>
                                                </td>
                                                <td>
                                                    <a href="{{ route('admin.contact-enquiries.index') }}"
                                                        style="background:#cadc26;color:#334155;padding:12px 24px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;font-size:14px;border:1px solid #cbd5e1;">
                                                        View Enquiries
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Modern Footer -->
                    <tr>
                        <td
                            style="background:#f8fafc;padding:24px 32px;border-top:1px solid #e2e8f0;text-align:center;">
                            <p style="margin:0 0 6px;font-size:13px;color:#64748b;">
                                This email was sent automatically from <strong>Kids-e-Dental</strong> Admin Portal.
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
