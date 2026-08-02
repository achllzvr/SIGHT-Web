<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Account Created — LUMI</title>
</head>
<body style="margin:0;padding:0;background:#f7fbf4;font-family:Arial,Helvetica,sans-serif;color:#2d2d2d;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f7fbf4;padding:28px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="620" cellspacing="0" cellpadding="0" style="width:620px;max-width:94%;background:#ffffff;border-radius:24px;overflow:hidden;border:4px solid #d4d4d4;box-shadow:0 6px 0 0 #d4d4d4;">
                    <tr>
                        <td style="background:#ffdcf9;border-bottom:4px solid #8168ab;padding:28px;text-align:center;">
                            <img src="{{ rtrim(config('app.url'), '/') }}/assets/lumi_app_icon.png" alt="LUMI" width="64" height="64" style="display:block;margin:0 auto 14px;border-radius:16px;border:3px solid #8168ab;">
                            <div style="font-size:13px;font-weight:700;letter-spacing:0.14em;color:#8168ab;text-transform:uppercase;">LUMI</div>
                            <h1 style="margin:10px 0 0;font-size:26px;line-height:1.15;color:#8168ab;text-transform:uppercase;letter-spacing:0.04em;">Your Account Is Ready</h1>
                            <p style="margin:8px 0 0;font-size:14px;color:#8168ab;opacity:0.9;">LUMI Clinician Portal</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0 0 14px;font-size:15px;">Hello {{ $name }},</p>
                            <p style="margin:0 0 16px;font-size:15px;color:#6b6b6b;">
                                Your clinician account has been created.
                                Use the temporary password below for your first login.
                            </p>

                            <div style="background:#f7fbf4;border:3px solid #d4d4d4;border-radius:16px;padding:14px;margin:0 0 18px;">
                                <p style="margin:0 0 8px;font-size:13px;color:#6b6b6b;text-transform:uppercase;letter-spacing:0.06em;">Email</p>
                                <p style="margin:0 0 12px;font-size:14px;color:#2d2d2d;font-weight:700;font-family:monospace;">{{ $email }}</p>
                                <p style="margin:0 0 8px;font-size:13px;color:#6b6b6b;text-transform:uppercase;letter-spacing:0.06em;">Temporary Password</p>
                                <p style="margin:0;font-size:14px;color:#2d2d2d;font-weight:700;font-family:monospace;">{{ $tempPassword }}</p>
                            </div>

                            <p style="margin:0 0 18px;font-size:14px;color:#6b6b6b;">
                                On your first successful login, you will be required to set a new password before accessing your dashboard.
                            </p>

                            <p style="text-align:center;margin:0;">
                                <a href="{{ $loginUrl }}" style="display:inline-block;background:#ffdcf9;color:#8168ab;text-decoration:none;font-weight:700;font-size:14px;letter-spacing:0.08em;padding:14px 28px;border-radius:9999px;border:4px solid #8168ab;box-shadow:0 4px 0 0 #8168ab;text-transform:uppercase;">
                                    Go to Login
                                </a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px 24px;border-top:3px solid #d4d4d4;font-size:12px;color:#6b6b6b;text-align:center;background:#eef8e8;">
                            <strong style="color:#62b239;letter-spacing:0.08em;text-transform:uppercase;">LUMI</strong>
                            &nbsp;·&nbsp;Eye health companion
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
