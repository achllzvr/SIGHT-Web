<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Facades\Log;

class PhpMailerService
{
    /**
     * Send professional verification email
     */
    public function sendProfessionalVerification($professional, $tempPassword, $verificationUrl)
    {
        $subject = 'Verify Your Professional Account - Sight';
        $htmlBody = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #527267; color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .credentials { background: white; padding: 20px; border-left: 4px solid #527267; margin: 20px 0; }
                .credentials-label { color: #666; font-size: 12px; text-transform: uppercase; }
                .credentials-value { font-size: 16px; font-weight: bold; color: #333; margin: 5px 0 15px 0; font-family: monospace; }
                .button { background: #527267; color: white; text-decoration: none; padding: 12px 30px; border-radius: 4px; display: inline-block; margin: 20px 0; }
                .footer { text-align: center; font-size: 12px; color: #999; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Verify Your Email</h1>
                </div>
                <div class='content'>
                    <p>Dear <strong>{$professional->display_name}</strong>,</p>
                    <p>Your professional account has been created. Please verify your email to activate your account.</p>
                    <div class='credentials'>
                        <div class='credentials-label'>Email Address</div>
                        <div class='credentials-value'>{$professional->email}</div>
                        <div class='credentials-label'>Temporary Password</div>
                        <div class='credentials-value'>{$tempPassword}</div>
                        <p style='font-size: 14px; color: #d9534f; margin-top: 15px;'>
                            ⚠️ <strong>Important:</strong> Please change your password immediately upon first login. Your temporary password should not be shared or reused.
                        </p>
                    </div>
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='" . $verificationUrl . "' class='button'>Yes, this is me</a>
                    </p>
                    <p>If you have any questions or need assistance, please contact the system administrator.</p>
                    <p>Best regards,<br><strong>Sight Team</strong></p>
                </div>
            </div>
        </body>
        </html>";
        return $this->sendEmail($professional->email, $professional->display_name, $subject, $htmlBody);
    }
    protected $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);
        
        try {
            // Server settings
            $this->mail->SMTPDebug = 0; // Set to 2 for debugging
            $this->mail->isSMTP();
            $this->mail->Host = env('MAIL_HOST', 'smtp.gmail.com');
            $this->mail->SMTPAuth = true;
            $this->mail->Username = env('MAIL_USERNAME');
            $this->mail->Password = env('MAIL_PASSWORD');
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port = env('MAIL_PORT', 587);
            
            // Set sender
            $this->mail->setFrom(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
        } catch (\Exception $e) {
            Log::error('PHPMailer Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send email to a recipient
     */
    public function sendEmail($toEmail, $toName, $subject, $htmlBody)
    {
        try {
            $this->mail->addAddress($toEmail, $toName);
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body = $htmlBody;
            
            $result = $this->mail->send();
            Log::info("Email sent successfully to {$toEmail}");
            
            // Clear recipients for next email
            $this->mail->clearAddresses();
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Email sending failed: ' . ($this->mail->ErrorInfo ?: $e->getMessage()));
            throw new \Exception("Email could not be sent. Error: " . ($this->mail->ErrorInfo ?: $e->getMessage()));
        }
    }

    /**
     * Send email verification OTP to a guardian.
     */
    public function sendEmailVerificationOtp($user, string $otpCode): bool
    {
        $subject = 'Verify Your Email — LUMI / SIGHT';
        $displayName = htmlspecialchars($user->display_name ?? 'Guardian', ENT_QUOTES, 'UTF-8');
        $htmlBody = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #527267; color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
                .code { background: white; padding: 20px; border-left: 4px solid #527267; margin: 20px 0; font-size: 28px; font-weight: bold; letter-spacing: 6px; text-align: center; font-family: monospace; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'><h1>Verify Your Email</h1></div>
                <div class='content'>
                    <p>Hello <strong>{$displayName}</strong>,</p>
                    <p>Use this code to verify your email address. It expires in 10 minutes.</p>
                    <div class='code'>{$otpCode}</div>
                    <p>If you did not create an account, you can ignore this message.</p>
                    <p>Best regards,<br><strong>SIGHT Team</strong></p>
                </div>
            </div>
        </body>
        </html>";

        return $this->sendEmail($user->email, $user->display_name, $subject, $htmlBody);
    }

    /**
     * Send professional invitation email
     */
    public function sendProfessionalInvitation($professional, $tempPassword)
{
    $subject = 'Your Sight Account is Pending Verification';
    $htmlBody = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #527267; color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
            .credentials { background: white; padding: 20px; border-left: 4px solid #527267; margin: 20px 0; }
            .credentials-label { color: #666; font-size: 12px; text-transform: uppercase; }
            .credentials-value { font-size: 16px; font-weight: bold; color: #333; margin: 5px 0 15px 0; font-family: monospace; }
            .footer { text-align: center; font-size: 12px; color: #999; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Account Pending Verification</h1>
            </div>
            <div class='content'>
                <p>Dear <strong>{$professional->display_name}</strong>,</p>
                <p>Your professional account has been created in the Sight Eye Health Management System. However, your email is currently <strong>pending verification</strong> by an administrator.</p>
                <div class='credentials'>
                    <div class='credentials-label'>Email Address</div>
                    <div class='credentials-value'>{$professional->email}</div>
                    <div class='credentials-label'>Temporary Password</div>
                    <div class='credentials-value'>{$tempPassword}</div>
                    <p style='font-size: 14px; color: #d9534f; margin-top: 15px;'>
                        ⚠️ <strong>Important:</strong> You will not be able to access your account until your email is verified by an administrator. Please wait for further instructions.
                    </p>
                </div>
                <p>If you have any questions or need assistance, please contact the system administrator.</p>
                <p>Best regards,<br><strong>Sight Team</strong></p>
            </div>
        </div>
    </body>
    </html>";
    return $this->sendEmail($professional->email, $professional->display_name, $subject, $htmlBody);
}
}
