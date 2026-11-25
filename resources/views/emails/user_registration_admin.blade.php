<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New User Registration</title>
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
                                        NEW REGISTRATION
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
                                    A new customer has registered on the website.
                                </p>
                            </div>

                            <div style="background:#FBFaf7;border:1px solid #EBE8DF;border-radius:4px;margin-bottom:24px;">
                                <div style="padding:16px 20px;border-bottom:1px solid #EBE8DF;background:#F5F2EA;">
                                    <h2 style="margin:0;font-size:18px;font-weight:bold;color:#333;font-family:Georgia, serif;">User Details</h2>
                                </div>
                                <div style="padding:20px;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;color:#555;">
                                        <tr>
                                            <td style="padding:6px 0;width:40%;">Name</td>
                                            <td style="padding:6px 0;text-align:right;font-weight:600;color:#333;">{{ $user->name }}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding:6px 0;vertical-align:top;">Email</td>
                                            <td style="padding:6px 0;text-align:right;font-weight:600;color:#2563EB;">
                                                <a href="mailto:{{ $user->email }}" style="color:#2563EB;text-decoration:none;">{{ $user->email }}</a>
                                            </td>
                                        </tr>
                                        @if($user->phone)
                                        <tr>
                                            <td style="padding:6px 0;">Phone</td>
                                            <td style="padding:6px 0;text-align:right;font-weight:600;color:#333;">{{ $user->phone }}</td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <td style="padding:6px 0;">Registered At</td>
                                            <td style="padding:6px 0;text-align:right;font-weight:600;color:#333;">{{ $user->created_at?->format('d M Y, h:i A') }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>