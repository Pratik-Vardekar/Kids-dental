<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You for Your Distributor Enquiry — Kids-e-Dental</title>
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
                                    Hi {{ explode(' ', trim($enquiry->contact_person))[0] }},
                                </h1>
                                <p style="margin:0;font-size:15px;line-height:1.6;color:#64748b;">
                                    Thank you for your interest in becoming an official distributor for <strong>Kids-e-Dental</strong>. We have received your application on behalf of <strong>{{ $enquiry->company_name }}</strong> and our partnership team will review it shortly.
                                </p>
                            </div>

                            <!-- Application Summary -->
                            <div
                                style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:20px;margin-bottom:24px;">
                                <h2
                                    style="margin:0 0 14px;font-size:14px;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:0.5px;">
                                    Your Submission Summary
                                </h2>
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                    style="font-size:14px;">
                                    <tr>
                                        <td style="padding:6px 0;width:40%;color:#64748b;">Company Name</td>
                                        <td style="padding:6px 0;font-weight:600;color:#0f172a;">{{ $enquiry->company_name }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:6px 0;color:#64748b;">Contact Person</td>
                                        <td style="padding:6px 0;font-weight:600;color:#0f172a;">{{ $enquiry->contact_person }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:6px 0;color:#64748b;">Email</td>
                                        <td style="padding:6px 0;font-weight:600;color:#0f172a;">{{ $enquiry->email }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:6px 0;color:#64748b;">Mobile No.</td>
                                        <td style="padding:6px 0;font-weight:600;color:#0f172a;">{{ $enquiry->mobile_no }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:6px 0;color:#64748b;">Country / City</td>
                                        <td style="padding:6px 0;font-weight:600;color:#0f172a;">{{ $enquiry->country_city }}</td>
                                    </tr>
                                </table>
                            </div>

                            <p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#64748b;">
                                If you need immediate assistance or have questions, feel free to reach out to us directly.
                            </p>

                            <!-- Button -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <a href="{{ url('/') }}"
                                            style="background:#2563eb;color:#ffffff;padding:12px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;font-size:14px;">
                                            Visit Our Website
                                        </a>
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td
                            style="background:#f8fafc;padding:24px 32px;border-top:1px solid #e2e8f0;text-align:center;">
                            <p style="margin:0 0 6px;font-size:13px;color:#64748b;">
                                <strong>Kids-e-Dental</strong> — Leading Pediatric Dental Solutions
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
