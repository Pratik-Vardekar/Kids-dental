<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Kids-e-Dental</title>
</head>

<body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,Helvetica,sans-serif;color:#222;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:8px;overflow:hidden;">
                    <tr>
                        <td style="background:#0d6efd;padding:24px 28px;text-align:center;">
                            <img src="{{ asset('backend/assets/images/KidseDental_Logo.png') }}" alt="Kids-e-Dental" style="height:32px;display:inline-block;">
                            <p style="margin:8px 0 0;font-size:14px;color:#e0edff;">Welcome aboard!</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0 0 12px;font-size:16px;">Hi {{ $user->name }},</p>
                            <p style="margin:0 0 20px;font-size:15px;line-height:1.5;color:#444;">
                                Thank you for registering with Kids-e-Dental. Your account is ready — you can shop our products anytime.
                            </p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px;font-size:14px;">
                                <tr>
                                    <td style="padding:6px 0;color:#666;">Email</td>
                                    <td style="padding:6px 0;text-align:right;font-weight:bold;">{{ $user->email }}</td>
                                </tr>
                                @if($user->phone)
                                <tr>
                                    <td style="padding:6px 0;color:#666;">Phone</td>
                                    <td style="padding:6px 0;text-align:right;font-weight:bold;">{{ $user->phone }}</td>
                                </tr>
                                @endif
                            </table>
                            <p style="margin:0;font-size:14px;line-height:1.5;color:#666;">
                                Add your billing and shipping addresses from your profile to speed up checkout.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>