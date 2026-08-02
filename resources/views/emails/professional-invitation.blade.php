<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to LUMI</title>
</head>
<body style="margin:0;padding:0;background:#f7fbf4;font-family:Arial,Helvetica,sans-serif;color:#2d2d2d;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f7fbf4;padding:28px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="620" cellspacing="0" cellpadding="0" style="width:620px;max-width:94%;background:#ffffff;border-radius:24px;overflow:hidden;border:4px solid #d4d4d4;box-shadow:0 6px 0 0 #d4d4d4;">
                    <tr>
                        <td style="background:#ffdcf9;border-bottom:4px solid #8168ab;padding:28px;text-align:center;">
                            @include('emails.partials.logo')
                            <div style="font-size:13px;font-weight:700;letter-spacing:0.14em;color:#8168ab;text-transform:uppercase;">LUMI</div>
                            <h1 style="margin:10px 0 0;font-size:26px;line-height:1.15;color:#8168ab;text-transform:uppercase;letter-spacing:0.04em;">Welcome to Lumi</h1>
                            <p style="margin:8px 0 0;font-size:14px;color:#8168ab;opacity:0.9;">Professional Account Created</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0 0 14px;font-size:15px;">Dear <strong>{{ $professional->display_name }}</strong>,</p>
                            <p style="margin:0 0 16px;font-size:15px;color:#6b6b6b;">
                                Your professional account has been successfully created in LUMI. Your account details are below:
                            </p>

                            <div style="background:#f7fbf4;border:3px solid #d4d4d4;border-radius:16px;padding:18px;margin:0 0 18px;">
                                <div style="font-size:12px;font-weight:700;color:#6b6b6b;text-transform:uppercase;letter-spacing:0.06em;">Email Address</div>
                                <div style="font-size:16px;font-weight:700;margin:6px 0 14px;font-family:monospace;">{{ $professional->email }}</div>
                                <div style="font-size:12px;font-weight:700;color:#6b6b6b;text-transform:uppercase;letter-spacing:0.06em;">Temporary Password</div>
                                <div style="font-size:16px;font-weight:700;margin:6px 0 0;font-family:monospace;">{{ $tempPassword }}</div>
                                <p style="font-size:14px;color:#f43f5e;margin:15px 0 0;">
                                    <strong>Important:</strong> Please change your password immediately upon first login. Your temporary password should not be shared or reused.
                                </p>
                            </div>

                            <p style="margin:0 0 8px;"><strong>Your Account Details:</strong></p>
                            <ul style="margin:0 0 18px;padding-left:20px;color:#6b6b6b;">
                                <li><strong>Specialty:</strong> {{ $professional->specialty }}</li>
                                <li><strong>Clinic:</strong> {{ $professional->clinic }}</li>
                                <li><strong>License Number:</strong> {{ $professional->license_number }}</li>
                                <li><strong>Location:</strong> {{ $professional->location }}</li>
                            </ul>

                            <p style="text-align:center;margin:30px 0;">
                                <a href="{{ $loginUrl }}" style="display:inline-block;background:#ffdcf9;color:#8168ab;text-decoration:none;font-weight:700;font-size:14px;letter-spacing:0.08em;padding:14px 28px;border-radius:9999px;border:4px solid #8168ab;box-shadow:0 4px 0 0 #8168ab;text-transform:uppercase;">
                                    Log In to Your Account
                                </a>
                            </p>

                            <p style="margin:0;font-size:14px;color:#6b6b6b;">If you have any questions or need assistance, please contact the system administrator.</p>
                            <p style="margin:18px 0 0;font-size:14px;color:#6b6b6b;">Best regards,<br><strong style="color:#8168ab;">The LUMI Team</strong></p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px 24px;border-top:3px solid #d4d4d4;font-size:12px;color:#6b6b6b;text-align:center;background:#eef8e8;">
                            &copy; {{ date('Y') }} LUMI. All rights reserved.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
