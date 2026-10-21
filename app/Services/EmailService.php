<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);

        try {
            $this->mailer->isSMTP();
            $this->mailer->Host = $_ENV['SMTP_HOST'] ?? 'smtp.mailtrap.io';
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $_ENV['SMTP_USERNAME'];
            $this->mailer->Password = $_ENV['SMTP_PASSWORD'];
            $this->mailer->SMTPSecure = $_ENV['SMTP_ENCRYPTION'] ?? PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = $_ENV['SMTP_PORT'] ?? 2525;

            //Default Sender
            $this->mailer->setFrom(
                $_ENV['SMTP_FROM_EMAIL'],
                $_ENV['SMTP_FROM_NAME']
            );
        } catch (Exception $e) {
            error_log(sprintf(
                'EmailService SMTP configuration failed: %s (host=%s, port=%s, encryption=%s)',
                $e->getMessage(),
                $this->mailer->Host ?: '(unset)',
                (string)$this->mailer->Port,
                $this->mailer->SMTPSecure ?: 'none'
            ));
            throw new \Exception('Email Configuration failed: ' . $e->getMessage());
        }
    }

public function sendEmailVerification($toEmail, $verificationLink, $expiresAt)
{
    try {
        $this->mailer->clearAddresses();
        $this->mailer->addAddress($toEmail);
        $this->mailer->isHTML(true);
        $this->mailer->Subject = 'Verify your email address';
        
        $this->mailer->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
                    line-height: 1.4;
                    color: #2d3748;
                    margin: 0;
                    padding: 0;
                }
                .container {
                    max-width: 400px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                }
                .logo {
                    font-size: 28px;
                    margin-bottom: 8px;
                }
                h2 {
                    font-size: 22px;
                    font-weight: 600;
                    margin: 0 0 8px;
                    color: #1a202c;
                }
                .code-box {
                    background: #f7fafc;
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                    padding: 20px;
                    text-align: center;
                    margin: 20px 0;
                }
                .code {
                    font-family: 'Courier New', monospace;
                    font-size: 32px;
                    font-weight: 700;
                    letter-spacing: 4px;
                    color: #3182ce;
                }
                .note {
                    font-size: 13px;
                    color: #718096;
                    text-align: center;
                    margin: 16px 0;
                    padding: 12px;
                    background: #ebf8ff;
                    border-radius: 6px;
                }
                .footer {
                    font-size: 12px;
                    color: #a0aec0;
                    text-align: center;
                    margin-top: 24px;
                    padding-top: 16px;
                    border-top: 1px solid #e2e8f0;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>✉️</div>
                    <h2>Hi {$toEmail},</h2>
                    <p style='margin:0; color:#4a5568;'>Verify your email to get started</p>
                </div>
                
                <div class='code-box'>
                    <div style='font-size:14px; color:#718096; margin-bottom:8px;'>Click the link below to verify your email</div>
                    <a href='{$verificationLink}' style='display:inline-block; background:#3182ce; color:white; padding:12px 24px; text-decoration:none; border-radius:6px; font-weight:600;'>Verify Email</a>
                    <div style='font-size:12px; color:#a0aec0; margin-top:12px;'>Valid until {$expiresAt}</div>
                </div>
                
                <div class='note'>
                    <strong>🔒 Security tip:</strong> This link will expire. If you didn't request this, you can safely ignore this email.
                </div>
                
                <p style='font-size:14px; color:#718096; text-align:center; margin:16px 0 0;'>
                    Didn't request this? You can safely ignore this email.
                </p>
                
                <div class='footer'>
                    © 2026 JobConnect. All rights reserved.
                </div>
            </div>
        </body>
        </html>
        ";

        $this->mailer->send();
        return true;
    } catch (Exception $e) {
        error_log(sprintf(
            'EmailService verification email failed: %s; SMTP error: %s (host=%s, port=%s, encryption=%s)',
            $e->getMessage(),
            $this->mailer->ErrorInfo ?: '(none)',
            $this->mailer->Host ?: '(unset)',
            (string)$this->mailer->Port,
            $this->mailer->SMTPSecure ?: 'none'
        ));
        return false;
    }
}
public function sendPasswordReset($toEmail, $resetLink, $expiresAt)
{
    try {
        $this->mailer->clearAddresses();
        $this->mailer->addAddress($toEmail);
        $this->mailer->isHTML(true);
        $this->mailer->Subject = 'Reset your password';
        
        // HTML version
        $this->mailer->Body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
                    line-height: 1.4;
                    color: #2d3748;
                    margin: 0;
                    padding: 0;
                }
                .container {
                    max-width: 400px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                }
                .logo {
                    font-size: 28px;
                    margin-bottom: 8px;
                }
                h2 {
                    font-size: 22px;
                    font-weight: 600;
                    margin: 0 0 8px;
                    color: #1a202c;
                }
                .code-box {
                    background: #f7fafc;
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                    padding: 20px;
                    text-align: center;
                    margin: 20px 0;
                }
                .code {
                    font-family: 'Courier New', monospace;
                    font-size: 32px;
                    font-weight: 700;
                    letter-spacing: 4px;
                    color: #DD6B20;
                }
                .note {
                    font-size: 13px;
                    color: #718096;
                    text-align: center;
                    margin: 16px 0;
                    padding: 12px;
                    background: #FEEBC8;
                    border-radius: 6px;
                }
                .footer {
                    font-size: 12px;
                    color: #a0aec0;
                    text-align: center;
                    margin-top: 24px;
                    padding-top: 16px;
                    border-top: 1px solid #e2e8f0;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>🔐</div>
                    <h2>Hi {$toEmail},</h2>
                    <p style='margin:0; color:#4a5568;'>Reset your password</p>
                </div>
                
                <div class='code-box'>
                    <div style='font-size:14px; color:#718096; margin-bottom:8px;'>Click the link below to reset your password</div>
                    <a href='{$resetLink}' style='display:inline-block; background:#DD6B20; color:white; padding:12px 24px; text-decoration:none; border-radius:6px; font-weight:600;'>Reset Password</a>
                    <div style='font-size:12px; color:#a0aec0; margin-top:12px;'>Valid until {$expiresAt}</div>
                </div>
                
                <div class='note'>
                    <strong>⚠️ Security tip:</strong> If you didn't request a password reset, please ignore this email or contact support.
                </div>
                
                <p style='font-size:14px; color:#718096; text-align:center; margin:16px 0 0;'>
                    This link will expire in 5 minutes for security reasons.
                </p>
                
                <div class='footer'>
                    © 2024 Your App Name. All rights reserved.
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Plain text fallback
        $this->mailer->AltBody = "Reset your password\n\n" .
            "Hi {$toEmail},\n\n" .
            "Click the link below to reset your password:\n" .
            "{$resetLink}\n\n" .
            "This link is valid until {$expiresAt}\n\n" .
            "If you didn't request a password reset, please ignore this email.\n\n" .
            "© 2026 JobConnect. All rights reserved.";

        $this->mailer->send();
        return true;
    } catch (Exception $e) {
        error_log('Password reset email failed: ' . $e->getMessage());
        return false;
    }
}
    public function sendInterviewNotification(
        string $toEmail,
        string $candidateName,
        string $jobTitle,
        string $company_name,
        string $action,
        string $scheduledAt,
        int $durationMinutes,
        string $interviewType,
        ?string $meetingLink = null,
        ?string $location = null,
        ?string $notes = null,
        ?string $cancellationReason = null,
        string $actionUrl
    ): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($toEmail);
            $this->mailer->isHTML(true);

            $subject = match ($action) {
                'scheduled'   => 'Interview Scheduled',
                'rescheduled' => 'Interview Rescheduled',
                'cancelled'   => 'Interview Cancelled',
                'completed'   => 'Interview Completed',
                'reminder'    => 'Interview Reminder',
                default       => 'Interview Update',
            };
            $this->mailer->Subject = $subject;

            $data = [
                'candidate_name'      => htmlspecialchars($candidateName),
                'job_title'           => htmlspecialchars($jobTitle),
                'company_name'        => htmlspecialchars($company_name),
                'scheduled_datetime'  => htmlspecialchars($scheduledAt),
                'meeting_link'        => $meetingLink,
                'location'            => htmlspecialchars($location ?? ''),
                'notes'               => htmlspecialchars($notes ?? ''),
                'cancellation_reason' => htmlspecialchars($cancellationReason ?? ''),
                'action_url'          => $actionUrl,
                'recipient_email'     => htmlspecialchars($toEmail),
            ];

            $html = match ($action) {
                'scheduled'   => $this->getScheduledHtml($data),
                'rescheduled' => $this->getRescheduledHtml($data),
                'cancelled'   => $this->getCancelledHtml($data),
                'completed'   => $this->getCompletedHtml($data),
                default       => $this->getDefaultHtml($data),
            };
            $this->mailer->Body = $html;

            $plainText = $this->buildPlainTextInterviewNotification(
                $candidateName,
                $jobTitle,
                $company_name,
                $action,
                $scheduledAt,
                $durationMinutes,
                $interviewType,
                $meetingLink,
                $location,
                $notes,
                $cancellationReason,
                $actionUrl,
                $toEmail
            );
            $this->mailer->AltBody = $plainText;

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log('Interview notification email failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Helper to build the email HTML with consistent layout.
     */
    private function buildEmailHtml(
        string $greeting,
        string $message,
        array $details,
        string $extraContent,
        string $actionUrl,
        string $recipientEmail,
        ?string $title = null
    ): string {
        $year = date('Y');
        $detailsHtml = '';
        foreach ($details as $label => $value) {
            if (!empty($value)) {
                $detailsHtml .= "<div class=\"detail-line\">
                                    <span class=\"detail-label\">{$label}</span>
                                    <span class=\"detail-value\">{$value}</span>
                                </div>";
            }
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.5;
        }
        .email-container {
            max-width: 500px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .content {
            padding: 32px 28px;
        }
        .greeting {
            font-size: 20px;
            font-weight: 500;
            margin-bottom: 16px;
        }
        .message {
            font-size: 16px;
            color: #3f3f46;
            margin-bottom: 24px;
        }
        .details {
            background: #fafafa;
            border-radius: 16px;
            padding: 20px;
            margin: 24px 0;
        }
        .detail-line {
            margin-bottom: 12px;
        }
        .detail-line:last-child {
            margin-bottom: 0;
        }
        .detail-label {
            font-weight: 500;
            color: #71717a;
            display: inline-block;
            min-width: 100px;
        }
        .detail-value {
            color: #1e293b;
        }
        .button {
            display: inline-block;
            background: #2563eb;
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 40px;
            font-weight: 500;
            margin-top: 8px;
        }
        .footer {
            font-size: 12px;
            color: #a1a1aa;
            text-align: center;
            border-top: 1px solid #e4e4e7;
            padding: 20px 28px;
            background: #fafafa;
        }
        @media (max-width: 600px) {
            .email-container { margin: 0; border-radius: 0; }
            .content { padding: 24px 20px; }
            .button { display: block; text-align: center; }
            .detail-label { display: block; margin-bottom: 4px; }
            .detail-value { display: block; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background:#f1f5f9;">
    <div class="email-container">
        <div class="content">
            <div class="greeting">{$greeting}</div>
            <div class="message">{$message}</div>
            <div class="details">
                {$detailsHtml}
            </div>
            {$extraContent}
            <div style="text-align:center;">
                <a href="{$actionUrl}" class="button">View in JobConnect</a>
            </div>
        </div>
        <div class="footer">
            © {$year} JobConnect · Sent to {$recipientEmail} · <a href="#" style="color:#a1a1aa;">Unsubscribe</a>
        </div>
    </div>
</body>
</html>
HTML;
    }

    // Action‑specific HTML templates (they call buildEmailHtml)
    private function getScheduledHtml(array $data): string
    {
        $meetingInfo = '';
        if (!empty($data['meeting_link'])) {
            $meetingInfo = "<a href='{$data['meeting_link']}' style='color:#2563eb;'>Join Meeting</a>";
        } elseif (!empty($data['location'])) {
            $meetingInfo = $data['location'];
        }

        $details = [
            "📅 When:" => $data['scheduled_datetime'],
            "📍 Where:" => $meetingInfo,
        ];

        $extra = '';
        if (!empty($data['notes'])) {
            $extra = "<div style='margin-top:12px; padding:12px; background:#f8fafc; border-radius:12px;'>
                        <strong>📝 From {$data['company_name']}:</strong><br>
                        {$data['notes']}
                      </div>";
        }

        return $this->buildEmailHtml(
            "🎉 Hi {$data['candidate_name']},",
            "Great news! Your interview for <strong>{$data['job_title']}</strong> at <strong>{$data['company_name']}</strong> has been scheduled.",
            $details,
            $extra,
            $data['action_url'],
            $data['recipient_email'],
            'Interview Scheduled'
        );
    }

    private function getRescheduledHtml(array $data): string
    {
        $meetingInfo = '';
        if (!empty($data['meeting_link'])) {
            $meetingInfo = "<a href='{$data['meeting_link']}' style='color:#2563eb;'>Join Meeting</a>";
        } elseif (!empty($data['location'])) {
            $meetingInfo = $data['location'];
        }

        $details = [
            "📅 New date & time:" => $data['scheduled_datetime'],
            "📍 Where:" => $meetingInfo,
        ];

        $extra = '';
        if (!empty($data['notes'])) {
            $extra = "<div style='margin-top:12px; padding:12px; background:#f8fafc; border-radius:12px;'>
                        <strong>📝 From {$data['company_name']}:</strong><br>
                        {$data['notes']}
                      </div>";
        }

        return $this->buildEmailHtml(
            "Hi {$data['candidate_name']},",
            "Your interview for <strong>{$data['job_title']}</strong> at <strong>{$data['company_name']}</strong> has been <strong>rescheduled</strong>.",
            $details,
            $extra,
            $data['action_url'],
            $data['recipient_email'],
            'Interview Rescheduled'
        );
    }

    private function getCancelledHtml(array $data): string
    {
        $extra = '';
        if (!empty($data['cancellation_reason'])) {
            $extra = "<div style='margin-top:12px; padding:12px; background:#fee2e2; border-radius:12px;'>
                        <strong>ℹ️ Reason given:</strong> {$data['cancellation_reason']}
                      </div>";
        }

        return $this->buildEmailHtml(
            "Hi {$data['candidate_name']},",
            "We're sorry to inform you that your interview for <strong>{$data['job_title']}</strong> at <strong>{$data['company_name']}</strong> has been <strong>cancelled</strong>.",
            [],
            $extra,
            $data['action_url'],
            $data['recipient_email'],
            'Interview Cancelled'
        );
    }

    private function getCompletedHtml(array $data): string
    {
        $extra = "<div style='margin-top:12px; padding:12px; background:#fef9e6; border-radius:12px; text-align:center;'>
                    <strong>We hope you land the job! 🍀</strong>
                  </div>";

        return $this->buildEmailHtml(
            "Hi {$data['candidate_name']},",
            "Thank you for attending the interview for <strong>{$data['job_title']}</strong> at <strong>{$data['company_name']}</strong>. It's now marked as completed.",
            [],
            $extra,
            $data['action_url'],
            $data['recipient_email'],
            'Interview Completed'
        );
    }

    private function getDefaultHtml(array $data): string
    {
        return $this->buildEmailHtml(
            "Hi {$data['candidate_name']},",
            "There's an update regarding your interview for <strong>{$data['job_title']}</strong> at <strong>{$data['company_name']}</strong>.",
            [],
            '',
            $data['action_url'],
            $data['recipient_email'],
            'Interview Update'
        );
    }

    // ---------- Plain Text Builder ----------
    private function buildPlainTextInterviewNotification(
        string $candidateName,
        string $jobTitle,
        string $company_name,
        string $action,
        string $scheduledAt,
        int $durationMinutes,
        string $interviewTypeDisplay,
        ?string $meetingLink = null,
        ?string $location = null,
        ?string $notes = null,
        ?string $cancellationReason = null,
        string $actionUrl,
        string $recipientEmail
    ): string {
        $text = "Hello {$candidateName},\n\n";

        $text .= match ($action) {
            'scheduled'   => "Great news! Your interview for {$jobTitle} at {$company_name} has been scheduled for {$scheduledAt}.\n\n",
            'rescheduled' => "Your interview for {$jobTitle} at {$company_name} has been rescheduled to {$scheduledAt}.\n\n",
            'cancelled'   => "We're sorry to inform you that your interview for {$jobTitle} at {$company_name} has been cancelled.\n\n",
            'completed'   => "Thank you for attending the interview for {$jobTitle} at {$company_name}. It's now marked as completed. We hope you land the job!\n\n",
            default       => "There's an update regarding your interview for {$jobTitle} at {$company_name}.\n\n",
        };

        if ($action === 'scheduled' || $action === 'rescheduled') {
            if ($meetingLink) {
                $text .= "You can join the meeting here: {$meetingLink}\n\n";
            } elseif ($location) {
                $text .= "Location: {$location}\n\n";
            }
            if ($notes) {
                $text .= "From {$company_name}: {$notes}\n\n";
            }
        }

        if ($action === 'cancelled' && $cancellationReason) {
            $text .= "Reason given: {$cancellationReason}\n\n";
        }

        $text .= "View full details: {$actionUrl}\n\n";
        $text .= "© " . date('Y') . " JobConnect · Sent to {$recipientEmail}\n";

        return $text;
    }

    /**
 * Send application status notification email.
 *
 * @param string $toEmail
 * @param string $candidateName
 * @param string $jobTitle
 * @param string $companyName
 * @param string $status   // shortlisted, rejected, accepted, reviewed
 * @param string $message  // optional custom message (if empty, a default is used)
 * @param string $actionUrl
 * @return bool
 */
public function sendApplicationStatusNotification(
    string $toEmail,
    string $candidateName,
    string $jobTitle,
    string $companyName,
    string $status,
    string $message = '',
    string $actionUrl
): bool {
    try {
        $this->mailer->clearAddresses();
        $this->mailer->addAddress($toEmail);
        $this->mailer->isHTML(true);

        // Subject based on status
        $subject = match ($status) {
            'shortlisted' => 'Congratulations! You’ve been shortlisted',
            'rejected'    => 'Update on your application',
            'accepted'    => 'You’ve been accepted!',
            'reviewed'    => 'Your application has been reviewed',
            default       => 'Application Status Update',
        };
        $this->mailer->Subject = $subject;

        // Prepare data array for the template
        $data = [
            'candidate_name' => htmlspecialchars($candidateName),
            'job_title'      => htmlspecialchars($jobTitle),
            'company_name'   => htmlspecialchars($companyName),
            'status'         => $status,
            'message'        => htmlspecialchars($message),
            'action_url'     => $actionUrl,
            'recipient_email'=> htmlspecialchars($toEmail),
            'year'           => date('Y'),
        ];

        $html = $this->getApplicationStatusHtml($data);
        $this->mailer->Body = $html;

        // Plain text fallback
        $plainText = $this->buildPlainTextApplicationStatus($data);
        $this->mailer->AltBody = $plainText;

        $this->mailer->send();
        return true;
    } catch (Exception $e) {
        error_log('Application status email failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Build HTML for application status email.
 *
 * @param array $data
 * @return string
 */
private function getApplicationStatusHtml(array $data): string
{
    // Determine default message if not provided
    if (empty($data['message'])) {
        $data['message'] = match ($data['status']) {
            'shortlisted' => "Great news! You've been shortlisted for the {$data['job_title']} position at {$data['company_name']}. The employer will contact you soon with next steps.",
            'accepted'    => "Congratulations! You've been accepted for the {$data['job_title']} position at {$data['company_name']}. We're excited to have you on board!",
            'rejected'    => "We appreciate your interest in the {$data['job_title']} position at {$data['company_name']}. While we won't be moving forward this time, we encourage you to keep applying – the right opportunity is waiting!",
            'reviewed'    => "Your application for the {$data['job_title']} position at {$data['company_name']} has been reviewed. The employer will update you soon.",
            default       => "There's an update on your application for the {$data['job_title']} position at {$data['company_name']}. Please check your dashboard for details.",
        };
    }

    // Status icon and title
    $statusConfig = match ($data['status']) {
        'shortlisted' => [
            'icon'  => '🎉',
            'title' => 'Shortlisted!',
            'sub'   => 'The employer will be in touch soon',
            'bg'    => 'shortlisted',
        ],
        'accepted' => [
            'icon'  => '🏆',
            'title' => 'Accepted!',
            'sub'   => "We're excited to have you on board!",
            'bg'    => 'accepted',
        ],
        'rejected' => [
            'icon'  => '💙',
            'title' => 'Not this time',
            'sub'   => 'Keep applying – the right opportunity is waiting!',
            'bg'    => 'rejected',
        ],
        'reviewed' => [
            'icon'  => '📋',
            'title' => 'Application reviewed',
            'sub'   => 'Check your dashboard for updates',
            'bg'    => 'reviewed',
        ],
        default => [
            'icon'  => '📌',
            'title' => 'Application Update',
            'sub'   => 'Please check your dashboard',
            'bg'    => 'reviewed',
        ],
    };

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Status</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f4f5;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.5;
            color: #18181b;
        }
        .email-container {
            max-width: 500px;
            width: 100%;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .content {
            padding: 32px 28px;
        }
        .greeting {
            font-size: 20px;
            font-weight: 500;
            margin-bottom: 16px;
        }
        .message {
            font-size: 16px;
            color: #3f3f46;
            margin-bottom: 24px;
        }
        .status-box {
            background: #fafafa;
            border-radius: 16px;
            padding: 20px;
            margin: 24px 0;
            text-align: center;
        }
        .status-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }
        .status-title {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .status-subtitle {
            font-size: 14px;
            color: #71717a;
        }
        .details {
            background: #ffffff;
            border-radius: 12px;
            padding: 16px;
            margin-top: 16px;
            border-left: 4px solid #2563eb;
            text-align: left;
        }
        .detail-row {
            padding: 8px 0;
            border-bottom: 1px solid #e4e4e7;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 500;
            color: #71717a;
            display: inline-block;
            min-width: 80px;
        }
        .detail-value {
            color: #1e293b;
        }
        .button {
            display: inline-block;
            background: #2563eb;
            color: white;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 40px;
            font-weight: 500;
            margin-top: 8px;
        }
        .footer {
            font-size: 12px;
            color: #a1a1aa;
            text-align: center;
            border-top: 1px solid #e4e4e7;
            padding: 20px 28px;
            background: #fafafa;
        }
        @media (max-width: 600px) {
            .email-container { margin: 0; border-radius: 0; }
            .content { padding: 24px 20px; }
            .button { display: block; text-align: center; }
        }
        /* Status-specific colors */
        .status-box.shortlisted { background: #f0fdf4; border-left: 4px solid #22c55e; }
        .status-box.accepted { background: #f0fdf4; border-left: 4px solid #22c55e; }
        .status-box.rejected { background: #fef9e6; border-left: 4px solid #eab308; }
        .status-box.reviewed { background: #eff6ff; border-left: 4px solid #3b82f6; }
    </style>
</head>
<body style="margin:0; padding:0; background:#f4f4f5;">
    <div class="email-container">
        <div class="content">
            <div class="greeting">Hi {$data['candidate_name']},</div>
            <div class="message">{$data['message']}</div>

            <div class="status-box {$statusConfig['bg']}">
                <div class="status-icon">{$statusConfig['icon']}</div>
                <div class="status-title">{$statusConfig['title']}</div>
                <div class="status-subtitle">{$statusConfig['sub']}</div>
            </div>

            <div class="details">
                <div class="detail-row">
                    <span class="detail-label">Position</span>
                    <span class="detail-value">{$data['job_title']}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Company</span>
                    <span class="detail-value">{$data['company_name']}</span>
                </div>
            </div>

            <div style="text-align:center; margin-top:24px;">
                <a href="{$data['action_url']}" class="button">View application details</a>
            </div>
        </div>
        <div class="footer">
            © {$data['year']} JobConnect · Sent to {$data['recipient_email']} · <a href="#" style="color:#a1a1aa;">Unsubscribe</a>
        </div>
    </div>
</body>
</html>
HTML;

    return $html;
}

/**
 * Build plain text version for application status.
 *
 * @param array $data
 * @return string
 */
private function buildPlainTextApplicationStatus(array $data): string
{
    $text = "Hi {$data['candidate_name']},\n\n";
    $text .= "{$data['message']}\n\n";

    $text .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $text .= "Position: {$data['job_title']}\n";
    $text .= "Company:  {$data['company_name']}\n";
    $text .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    $text .= "To view full details, please visit:\n";
    $text .= "{$data['action_url']}\n\n";

    $text .= "© {$data['year']} JobConnect · Sent to {$data['recipient_email']}\n";

    return $text;
}
}