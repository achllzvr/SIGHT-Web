<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use Illuminate\Support\Facades\Log;

class PhpMailerService
{
    /**
     * Shared LUMI arcade email chrome (inline CSS — email-client safe).
     */
    private function brandedEmail(string $headline, string $bodyHtml, string $eyebrow = 'LUMI'): string
    {
        $headline = htmlspecialchars($headline, ENT_QUOTES, 'UTF-8');
        $eyebrow = htmlspecialchars(strtoupper($eyebrow), ENT_QUOTES, 'UTF-8');
        $year = date('Y');
        $logoUrl = rtrim((string) config('app.url'), '/') . '/assets/lumi_app_icon.png';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$headline}</title>
</head>
<body style="margin:0;padding:0;background:#f7fbf4;font-family:Arial,Helvetica,sans-serif;color:#2d2d2d;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f7fbf4;padding:28px 0;">
<tr><td align="center">
<table role="presentation" width="620" cellspacing="0" cellpadding="0" style="width:620px;max-width:94%;background:#ffffff;border-radius:24px;overflow:hidden;border:4px solid #d4d4d4;box-shadow:0 6px 0 0 #d4d4d4;">
<tr>
<td style="background:#ffdcf9;border-bottom:4px solid #8168ab;padding:28px 28px 22px;text-align:center;">
<img src="{$logoUrl}" alt="LUMI" width="64" height="64" style="display:block;margin:0 auto 14px;border-radius:16px;border:3px solid #8168ab;">
<div style="font-size:13px;font-weight:700;letter-spacing:0.14em;color:#8168ab;text-transform:uppercase;">{$eyebrow}</div>
<h1 style="margin:10px 0 0;font-size:26px;line-height:1.15;color:#8168ab;text-transform:uppercase;letter-spacing:0.04em;">{$headline}</h1>
</td>
</tr>
<tr>
<td style="padding:28px;background:#ffffff;">
{$bodyHtml}
</td>
</tr>
<tr>
<td style="padding:16px 28px 24px;border-top:3px solid #d4d4d4;font-size:12px;color:#6b6b6b;text-align:center;background:#eef8e8;">
<strong style="color:#62b239;letter-spacing:0.08em;text-transform:uppercase;">LUMI</strong>
&nbsp;·&nbsp;Eye health companion<br>
&copy; {$year} LUMI. All rights reserved.
</td>
</tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;
    }

    private function ctaButton(string $url, string $label): string
    {
        $url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $label = htmlspecialchars(strtoupper($label), ENT_QUOTES, 'UTF-8');

        return '<p style="text-align:center;margin:28px 0 8px;">'
            . '<a href="' . $url . '" style="display:inline-block;background:#ffdcf9;color:#8168ab;text-decoration:none;font-weight:700;font-size:14px;letter-spacing:0.08em;padding:14px 28px;border-radius:9999px;border:4px solid #8168ab;box-shadow:0 4px 0 0 #8168ab;">'
            . $label
            . '</a></p>';
    }

    private function credentialBox(string $email, string $tempPassword): string
    {
        $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $tempPassword = htmlspecialchars($tempPassword, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<div style="background:#f7fbf4;border:3px solid #d4d4d4;border-radius:16px;padding:18px;margin:18px 0;">
<div style="font-size:12px;font-weight:700;color:#6b6b6b;text-transform:uppercase;letter-spacing:0.06em;">Email Address</div>
<div style="font-size:16px;font-weight:700;color:#2d2d2d;margin:6px 0 14px;font-family:monospace;">{$email}</div>
<div style="font-size:12px;font-weight:700;color:#6b6b6b;text-transform:uppercase;letter-spacing:0.06em;">Temporary Password</div>
<div style="font-size:16px;font-weight:700;color:#2d2d2d;margin:6px 0 0;font-family:monospace;">{$tempPassword}</div>
</div>
HTML;
    }

    public function sendProfessionalVerification($professional, $tempPassword, $verificationUrl)
    {
        $subject = 'Verify Your Professional Account — LUMI';
        $name = htmlspecialchars($professional->display_name ?? 'Clinician', ENT_QUOTES, 'UTF-8');
        $body = "<p style=\"margin:0 0 14px;font-size:15px;\">Dear <strong>{$name}</strong>,</p>"
            . '<p style="margin:0 0 14px;font-size:15px;color:#6b6b6b;">Your LUMI clinician account has been created. Verify your email to activate access.</p>'
            . $this->credentialBox((string) $professional->email, (string) $tempPassword)
            . '<p style="margin:0 0 8px;font-size:14px;color:#f43f5e;"><strong>Important:</strong> Change your password immediately on first login. Do not share this temporary password.</p>'
            . $this->ctaButton((string) $verificationUrl, 'Yes, this is me')
            . '<p style="margin:18px 0 0;font-size:14px;color:#6b6b6b;">Best regards,<br><strong style="color:#8168ab;">The LUMI Team</strong></p>';

        return $this->sendEmail($professional->email, $professional->display_name, $subject, $this->brandedEmail('Verify Your Email', $body));
    }

    protected $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);

        try {
            $this->mail->SMTPDebug = 0;
            $this->mail->isSMTP();
            $this->mail->Host = env('MAIL_HOST', 'smtp.gmail.com');
            $this->mail->SMTPAuth = true;
            $this->mail->Username = env('MAIL_USERNAME');
            $this->mail->Password = env('MAIL_PASSWORD');
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port = env('MAIL_PORT', 587);
            $this->mail->setFrom(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME', 'LUMI'));
        } catch (\Exception $e) {
            Log::error('PHPMailer Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function sendEmail($toEmail, $toName, $subject, $htmlBody)
    {
        try {
            $this->mail->addAddress($toEmail, $toName);
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body = $htmlBody;

            $result = $this->mail->send();
            Log::info("Email sent successfully to {$toEmail}");
            $this->mail->clearAddresses();

            return $result;
        } catch (\Exception $e) {
            Log::error('Email sending failed: ' . ($this->mail->ErrorInfo ?: $e->getMessage()));
            throw new \Exception('Email could not be sent. Error: ' . ($this->mail->ErrorInfo ?: $e->getMessage()));
        }
    }

    public function sendEmailVerificationOtp($user, string $otpCode): bool
    {
        $subject = 'Verify Your Email — LUMI';
        $displayName = htmlspecialchars($user->display_name ?? 'Guardian', ENT_QUOTES, 'UTF-8');
        $otpCode = htmlspecialchars($otpCode, ENT_QUOTES, 'UTF-8');
        $body = "<p style=\"margin:0 0 14px;font-size:15px;\">Hello <strong>{$displayName}</strong>,</p>"
            . '<p style="margin:0 0 14px;font-size:15px;color:#6b6b6b;">Use this code to verify your email address. It expires in 10 minutes.</p>'
            . '<div style="background:#ffdcf9;border:4px solid #8168ab;border-radius:16px;padding:22px;margin:20px 0;font-size:32px;font-weight:700;letter-spacing:8px;text-align:center;color:#8168ab;font-family:monospace;">'
            . $otpCode
            . '</div>'
            . '<p style="margin:0 0 8px;font-size:14px;color:#6b6b6b;">If you did not create a LUMI account, you can ignore this message.</p>'
            . '<p style="margin:18px 0 0;font-size:14px;color:#6b6b6b;">Best regards,<br><strong style="color:#8168ab;">The LUMI Team</strong></p>';

        return $this->sendEmail($user->email, $user->display_name, $subject, $this->brandedEmail('Verify Your Email', $body));
    }

    public function sendProfessionalInvitation($professional, $tempPassword)
    {
        $subject = 'Your LUMI Account is Pending Verification';
        $name = htmlspecialchars($professional->display_name ?? 'Clinician', ENT_QUOTES, 'UTF-8');
        $body = "<p style=\"margin:0 0 14px;font-size:15px;\">Dear <strong>{$name}</strong>,</p>"
            . '<p style="margin:0 0 14px;font-size:15px;color:#6b6b6b;">Your professional account has been created in LUMI. Your email is currently <strong>pending verification</strong> by an administrator.</p>'
            . $this->credentialBox((string) $professional->email, (string) $tempPassword)
            . '<p style="margin:0 0 8px;font-size:14px;color:#f43f5e;"><strong>Important:</strong> You will not be able to access your account until an administrator verifies your email.</p>'
            . '<p style="margin:18px 0 0;font-size:14px;color:#6b6b6b;">Best regards,<br><strong style="color:#8168ab;">The LUMI Team</strong></p>';

        return $this->sendEmail($professional->email, $professional->display_name, $subject, $this->brandedEmail('Account Pending Verification', $body));
    }
}
