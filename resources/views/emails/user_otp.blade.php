<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Verification Code</title>
</head>

<body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,Helvetica,sans-serif;color:#222;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                    style="max-width:600px;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <!-- Header -->
                    <tr>
                        <td style="background:#fc0;padding:24px 28px;text-align:center;">
                            <img src="{{ asset('backend/assets/images/KidseDental_Logo.png') }}" alt="kids-e-dental"
                                style="height:50px;display:inline-block;">
                            <p style="margin:8px 0 0;font-size:14px;color:#ffe8d6;letter-spacing:0.5px;">Account
                                Verification</p>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding:32px 28px;">
                            <p style="margin:0 0 12px;font-size:16px;color:#222;">Hi
                                <strong>{{ $user->name }}</strong>,
                            </p>
                            <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#555;">
                                Thank you for registering with Kids-e-dental! To complete your registration and verify
                                your account, please use the One-Time Password (OTP) below:
                            </p>

                            <!-- OTP Box -->
                            <div
                                style="text-align:center;margin:28px 0;padding:18px 24px;background:#FFF4EC;border:2px dashed #fc0;border-radius:8px;">
                                <span
                                    style="font-size:13px;text-transform:uppercase;letter-spacing:1px;color:#666;display:block;margin-bottom:6px;">Your
                                    One-Time Password</span>
                                <span
                                    style="font-size:32px;font-weight:bold;letter-spacing:8px;color:#fc0;display:inline-block;">{{ $otp }}</span>
                                <span style="font-size:12px;color:#888;display:block;margin-top:6px;">Valid for 10
                                    minutes</span>
                            </div>

                            <p style="margin:0 0 12px;font-size:14px;line-height:1.5;color:#666;">
                                If you did not request this code, please ignore this email or reach out to our support
                                team.
                            </p>
                            <p
                                style="margin:0;font-size:13px;line-height:1.5;color:#999;border-top:1px solid #eee;padding-top:16px;">
                                For security reasons, never share this OTP with anyone.
                            </p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td
                            style="background:#fafafa;padding:16px 28px;text-align:center;font-size:12px;color:#999;border-top:1px solid #f0f0f0;">
                            &copy; {{ date('Y') }} Kids-e-dental. All rights reserved.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
