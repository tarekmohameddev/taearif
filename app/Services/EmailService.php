<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Models\BasicExtended;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Facades\Log;

class EmailService
{
    protected $settings;

    public function __construct()
    {
        $this->settings = BasicExtended::first();
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetCode($email, $name, $code, $userLanguage = 'ar', $templateName = null, $resetUrl = null)
    {
        try {
            // Get template - first try with specific template name, then with user language, then fallback
            $template = null;
            
            if ($templateName) {
                $template = EmailTemplate::where('name', $templateName)
                    ->where('type', 'password_reset')
                    ->where('status', true)
                    ->first();
            }
            
            // If no specific template found, try to get template by user language
            if (!$template) {
                $template = EmailTemplate::where('type', 'password_reset')
                    ->where('language', $userLanguage)
                    ->where('status', true)
                    ->orderBy('created_at', 'desc')
                    ->first();
            }
            
            // If still no template found, try Arabic as fallback
            if (!$template && $userLanguage !== 'ar') {
                $template = EmailTemplate::where('type', 'password_reset')
                    ->where('language', 'ar')
                    ->where('status', true)
                    ->orderBy('created_at', 'desc')
                    ->first();
            }

            // Prepare email content
            if ($template) {
                $subject = $template->subject;
                $content = $template->content;
                
                // Replace variables
                $content = str_replace('{name}', $name, $content);
                $content = str_replace('{code}', $code, $content);
                
                // Add reset link if provided
                if ($resetUrl) {
                    $resetLink = $resetUrl . '?code=' . $code . '&identifier=' . $email;
                    $content = str_replace('{reset_link}', $resetLink, $content);
                }
            } else {
                // Default email content (fallback)
                $subject = 'إعادة تعيين كلمة المرور';
                $content = "مرحباً {$name}،\n\nرمز إعادة تعيين كلمة المرور: {$code}\n\n";
                
                if ($resetUrl) {
                    $resetLink = $resetUrl . '?code=' . $code . '&identifier=' . $email;
                    $content .= "يمكنك أيضاً الضغط على الرابط التالي:\n{$resetLink}\n\n";
                }
                
                $content .= "هذا الرمز صالح لمدة 15 دقيقة.\n\nمع تحيات فريق العمل";
            }

            // Send email
            return $this->sendEmail($email, $subject, $content, $name);

        } catch (\Exception $e) {
            Log::error('EmailService: Failed to send password reset email', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send email using PHPMailer
     */
    protected function sendEmail($to, $subject, $content, $toName = null)
    {
        try {
            $mail = new PHPMailer(true);
            $mail->CharSet = "UTF-8";

            // Configure SMTP if enabled
            if ($this->settings && $this->settings->is_smtp == 1) {
                $mail->isSMTP();
                $mail->Host = $this->settings->smtp_host;
                $mail->SMTPAuth = true;
                $mail->Username = $this->settings->smtp_username;
                $mail->Password = $this->settings->smtp_password;
                
                if ($this->settings->encryption == 'TLS') {
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                }
                
                $mail->Port = $this->settings->smtp_port;
            }

            // Set sender
            $fromEmail = $this->settings->from_mail ?? 'noreply@example.com';
            $fromName = $this->settings->from_name ?? 'System';
            $mail->setFrom($fromEmail, $fromName);

            // Add recipient
            $mail->addAddress($to, $toName);

            // Set content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = nl2br($content); // Convert line breaks to HTML

            // Send email
            $mail->send();

            Log::info('EmailService: Email sent successfully', [
                'to' => $to,
                'subject' => $subject
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('EmailService: Failed to send email', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Test email configuration
     */
    public function testEmailConfiguration($testEmail)
    {
        try {
            $subject = 'اختبار إعدادات البريد الإلكتروني';
            $content = "هذه رسالة اختبار للتأكد من عمل إعدادات البريد الإلكتروني بشكل صحيح.\n\nتم الإرسال في: " . now()->format('Y-m-d H:i:s');
            
            return $this->sendEmail($testEmail, $subject, $content, 'Test User');

        } catch (\Exception $e) {
            Log::error('EmailService: Test email failed', [
                'test_email' => $testEmail,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get available email templates for password reset
     */
    public function getPasswordResetTemplates()
    {
        return EmailTemplate::active()
            ->ofType('password_reset')
            ->get();
    }
}
