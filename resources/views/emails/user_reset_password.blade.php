<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password - Kids-e-Dental</title>
</head>

<body style="margin:0;padding:0;background:#f5f7fa;font-family:Arial,Helvetica,sans-serif;color:#222;-webkit-font-smoothing:antialiased;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f7fa;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                    style="max-width:600px;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.06);border:1px solid #e5e7eb;">

                    <!-- Brand Header -->
                    <tr>
                        <td style="background:#fc0;padding:26px 28px;text-align:center;">
                            <img src="{{ asset('backend/assets/images/KidseDental_Logo.png') }}" alt="Kids-e-Dental"
                                style="height:48px;max-height:48px;display:inline-block;border:0;">
                            <p style="margin:10px 0 0;font-size:14px;font-weight:600;color:#333;letter-spacing:0.5px;">
                                Password Reset Request
                            </p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding:36px 32px 28px;">
                            <p style="margin:0 0 16px;font-size:16px;color:#111;">
                                Hi <strong>{{ $user->name ?: 'Customer' }}</strong>,
                            </p>

                            <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#4b5563;">
                                You recently requested to reset the password for your <strong>Kids-e-Dental</strong> account associated with <strong>{{ $user->email }}</strong>.
                            </p>

                            <p style="margin:0 0 28px;font-size:15px;line-height:1.6;color:#4b5563;">
                                Please click the button below to choose a new password:
                            </p>

                            <!-- Reset Button -->
                            <div style="text-align:center;margin:32px 0;">
                                <a href="{{ $resetUrl }}" target="_blank"
                                    style="display:inline-block;background:#fc0;color:#111827;font-size:16px;font-weight:bold;letter-spacing:0.5px;padding:14px 36px;border-radius:6px;text-decoration:none;box-shadow:0 2px 4px rgba(0,0,0,0.1);">
                                    Reset Password
                                </a>
                            </div>

                            <!-- Alternate Link -->
                            <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:16px;margin:28px 0 20px;">
                                <p style="margin:0 0 8px;font-size:13px;color:#6b7280;line-height:1.5;">
                                    If the button above doesn't work, copy and paste this link into your web browser:
                                </p>
                                <p style="margin:0;font-size:13px;line-height:1.5;word-break:break-all;">
                                    <a href="{{ $resetUrl }}" target="_blank" style="color:#0284c7;text-decoration:underline;">
                                        {{ $resetUrl }}
                                    </a>
                                </p>
                            </div>

                            <!-- Security Notice -->
                            <div style="background:#fef3c7;border-left:4px solid #f59e0b;padding:12px 16px;margin:24px 0;border-radius:4px;">
                                <p style="margin:0;font-size:13px;line-height:1.5;color:#92400e;">
                                    <strong>Didn't request this?</strong> If you didn't ask to reset your password, you can safely ignore this email. Your current password will remain unchanged and your account is secure.
                                </p>
                            </div>

                            <p style="margin:24px 0 0;font-size:13px;line-height:1.5;color:#9ca3af;border-top:1px solid #f3f4f6;padding-top:16px;">
                                For security reasons, please do not forward or share this link with anyone.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background:#f9fafb;padding:20px 28px;text-align:center;font-size:12px;color:#9ca3af;border-top:1px solid #f3f4f6;">
                            &copy; {{ date('Y') }} Kids-e-Dental. All rights reserved.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
