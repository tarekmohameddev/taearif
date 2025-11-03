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
    public function sendPasswordResetCode($email, $name, $code, $userLanguage = 'ar', $templateName = null, $resetUrl = null, $userId = null)
    {
        // Check master toggle first - if all email notifications are disabled, return early
        if (!$this->settings || !($this->settings->email_notifications_enabled ?? true)) {
            Log::info('Email notifications are disabled by master toggle', [
                'email' => $email,
                'type' => 'password_reset'
            ]);
            return false;
        }

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

            // Get company name from user_basic_settings table (same logic as WhatsApp service)
            $companyName = $name; // Default to provided name
            if ($userId) {
                $userBasicSettings = \App\Models\User\BasicSetting::where('user_id', $userId)->first();
                if ($userBasicSettings && $userBasicSettings->company_name && $userBasicSettings->company_name !== 'N/A') {
                    $companyName = $userBasicSettings->company_name;
                } else {
                    // If company_name is N/A or empty, get username from users table
                    $user = \App\Models\User::find($userId);
                    if ($user && $user->username) {
                        $companyName = $user->username;
                    }
                }
            }

            // Prepare email content
            if ($template) {
                $subject = $template->subject;
                $content = $template->content;

                // Replace variables
                $content = str_replace('{name}', $companyName, $content);
                $content = str_replace('{code}', $code, $content);

                // Add reset link if provided (only code, no identifier)
                if ($resetUrl) {
                    $resetLink = $resetUrl . '?code=' . $code;
                    $content = str_replace('{reset_link}', $resetLink, $content);
                }
            } else {
                // Default email content (fallback) - only code and URL
                $subject = 'إعادة تعيين كلمة المرور';
                $content = "مرحبا {$companyName},\n\nرمز إعادة تعيين كلمة المرور: {$code}\n\nهذا الرمز صالح لمدة 15 دقيقة.";

                if ($resetUrl) {
                    $resetLink = $resetUrl . '?code=' . $code;
                    $content .= "\n\nأو يمكنك الضغط على الرابط التالي:\n{$resetLink}";
                }
            }

            // Send email
            return $this->sendEmail($email, $subject, $content, $companyName);

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
     * Send welcome email to new user
     */
    public function sendWelcomeEmail($email, $name, $userLanguage = 'ar', $templateName = null, $userId = null)
    {
        // Check master toggle first
        if (!$this->settings || !($this->settings->email_notifications_enabled ?? true)) {
            Log::info('Email notifications are disabled by master toggle', [
                'email' => $email,
                'type' => 'welcome'
            ]);
            return false;
        }

        try {
            // Get template - first try with specific template name, then with user language, then fallback
            $template = null;

            if ($templateName) {
                $template = EmailTemplate::where('name', $templateName)
                    ->where('type', 'welcome')
                    ->where('status', true)
                    ->first();
            }

            // If no specific template found, try to get template by user language
            if (!$template) {
                $template = EmailTemplate::where('type', 'welcome')
                    ->where('language', $userLanguage)
                    ->where('status', true)
                    ->orderBy('created_at', 'desc')
                    ->first();
            }

            // If still no template found, try Arabic as fallback
            if (!$template && $userLanguage !== 'ar') {
                $template = EmailTemplate::where('type', 'welcome')
                    ->where('language', 'ar')
                    ->where('status', true)
                    ->orderBy('created_at', 'desc')
                    ->first();
            }

            // Get company name from user_basic_settings table (same logic as password reset)
            $companyName = $name; // Default to provided name
            if ($userId) {
                $userBasicSettings = \App\Models\User\BasicSetting::where('user_id', $userId)->first();
                if ($userBasicSettings && $userBasicSettings->company_name && $userBasicSettings->company_name !== 'N/A') {
                    $companyName = $userBasicSettings->company_name;
                } else {
                    // If company_name is N/A or empty, get username from users table
                    $user = \App\Models\User::find($userId);
                    if ($user && $user->username) {
                        $companyName = $user->username;
                    }
                }
            }

            // Prepare email content
            if ($template) {
                $subject = $template->subject;
                $content = $template->content;

                // Replace variables
                $content = str_replace('{name}', $companyName, $content);
                $content = str_replace('{email}', $email, $content);
            } else {
                // Default email content (fallback)
                $subject = ' مرحباً بك  في منصة تعاريف ';
                $content = "مرحباً {$companyName},\n\nأهلاً وسهلاً بك في منصتنا!\nنتمنى لك تجربة ممتعة.\n\nشكراً لك على التسجيل.";
            }

            // Send email
            return $this->sendEmail($email, $subject, $content, $companyName);

        } catch (\Exception $e) {
            Log::error('EmailService: Failed to send welcome email', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send subscription expiration reminder email
     */
    public function sendSubscriptionExpirationEmail($email, $name, $packageName = null, $expiryDate = null, $userLanguage = 'ar', $templateName = null)
    {
        // Check master toggle first
        if (!$this->settings || !($this->settings->email_notifications_enabled ?? true)) {
            Log::info('Email notifications are disabled by master toggle', [
                'email' => $email,
                'type' => 'subscription_expiration'
            ]);
            return false;
        }

        try {
            // Get template - first try with specific template name, then with user language, then fallback
            $template = null;

            if ($templateName) {
                $template = EmailTemplate::where('name', $templateName)
                    ->where('type', 'subscription_expiration')
                    ->where('status', true)
                    ->first();
            }

            // If no specific template found, try to get template by user language
            if (!$template) {
                $template = EmailTemplate::where('type', 'subscription_expiration')
                    ->where('language', $userLanguage)
                    ->where('status', true)
                    ->orderBy('created_at', 'desc')
                    ->first();
            }

            // If still no template found, try Arabic as fallback
            if (!$template && $userLanguage !== 'ar') {
                $template = EmailTemplate::where('type', 'subscription_expiration')
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
                $content = str_replace('{package_name}', $packageName ?? 'الباقة المميزة', $content);
                $content = str_replace('{expiry_date}', $expiryDate ?? 'غير محدد', $content);
            } else {
                // Default email content (fallback)
                $subject = 'تنبيه: انتهاء الاشتراك قريباً';
                $content = "مرحباً {$name},\n\nتنبيه: باقة الاشتراك الخاصة بك ({$packageName}) ستنتهي قريباً.\nتاريخ الانتهاء: {$expiryDate}\n\nيرجى تجديد اشتراكك للاستمرار في الاستفادة من خدماتنا.";
            }

            // Send email
            return $this->sendEmail($email, $subject, $content, $name);

        } catch (\Exception $e) {
            Log::error('EmailService: Failed to send subscription expiration email', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send subscription expired notification email
     */
    public function sendSubscriptionExpiredEmail($email, $name, $packageName = null, $expiryDate = null, $userLanguage = 'ar', $templateName = null)
    {
        // Check master toggle first
        if (!$this->settings || !($this->settings->email_notifications_enabled ?? true)) {
            Log::info('Email notifications are disabled by master toggle', [
                'email' => $email,
                'type' => 'subscription_expired'
            ]);
            return false;
        }

        try {
            // Get template - first try with specific template name, then with user language, then fallback
            $template = null;

            if ($templateName) {
                $template = EmailTemplate::where('name', $templateName)
                    ->where('type', 'subscription_expired')
                    ->where('status', true)
                    ->first();
            }

            // If no specific template found, try to get template by user language
            if (!$template) {
                $template = EmailTemplate::where('type', 'subscription_expired')
                    ->where('language', $userLanguage)
                    ->where('status', true)
                    ->orderBy('created_at', 'desc')
                    ->first();
            }

            // If still no template found, try Arabic as fallback
            if (!$template && $userLanguage !== 'ar') {
                $template = EmailTemplate::where('type', 'subscription_expired')
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
                $content = str_replace('{package_name}', $packageName ?? 'الباقة المميزة', $content);
                $content = str_replace('{expiry_date}', $expiryDate ?? 'غير محدد', $content);
            } else {
                // Default email content (fallback)
                $subject = 'انتهاء الاشتراك';
                $content = "مرحباً {$name},\n\nانتهى اشتراكك وتم نقلك إلى الباقة المجانية.\nيمكنك الترقية في أي وقت من لوحة التحكم.";
            }

            // Send email
            return $this->sendEmail($email, $subject, $content, $name);

        } catch (\Exception $e) {
            Log::error('EmailService: Failed to send subscription expired email', [
                'email' => $email,
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

    /**
     * Get available email templates for welcome messages
     */
    public function getWelcomeTemplates()
    {
        return EmailTemplate::active()
            ->ofType('welcome')
            ->get();
    }

    /**
     * Get available email templates for subscription expiration
     */
    public function getSubscriptionExpirationTemplates()
    {
        return EmailTemplate::active()
            ->ofType('subscription_expiration')
            ->get();
    }

    /**
     * Get available email templates for subscription expired
     */
    public function getSubscriptionExpiredTemplates()
    {
        return EmailTemplate::active()
            ->ofType('subscription_expired')
            ->get();
    }
}
