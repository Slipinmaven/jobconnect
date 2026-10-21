<?php
ini_set('log_errors', 'On');
ini_set('error_log', __DIR__ . '/../storage/logs/worker.log');

use App\Repositories\NotificationOutboxRepository;
use Dotenv\Dotenv;

require __DIR__ . '/../bootstrap.php';
// ============================================================================
// STEP 1: LOAD ENVIRONMENT VARIABLES
// ============================================================================
error_log("Worker is starting at " . gmdate('Y-m-d H:i:s'));


// ============================================================================
// STEP 2: ESTABLISH DATABASE CONNECTION
// ============================================================================

try {
    $pdo = $container->get(PDO::class);
    if (!$pdo) {
        throw new \Exception('Database connection failed');
    }
    error_log("Email outbox worker started at " . gmdate('Y-m-d H:i:s'));
} catch (\Exception $e) {
    fwrite(STDERR, "Database Connection failed: " . $e->getMessage() . "\n");
    exit(1);
}

// ============================================================================
// STEP 3: INITIALIZE REPOSITORIES AND SERVICES
// ============================================================================

$outboxRepo = new NotificationOutboxRepository($pdo);

$userRepo = $container->get(\App\Repositories\UserRepository::class);
$emailService = $container->get(\App\Services\EmailService::class);

// ============================================================================
// STEP 4: SETUP WORKER IDENTIFICATION
// ============================================================================

$workerId = (gethostname() ?: 'worker') . ':' . getmypid();

// ============================================================================
// STEP 5: CLEANUP STUCK JOBS
// ============================================================================

$outboxRepo->unlockStuck(10);

// ============================================================================
// STEP 6: CLAIM AVAILABLE JOBS
// ============================================================================
$jobs = $outboxRepo->claim('email', $workerId, 50);

if (!$jobs) {
    // No jobs to process - exit gracefully
    error_log("No email jobs found, worker exiting");
    exit(0);
}

error_log("Found " . count($jobs) . " email jobs to process");

// ============================================================================
// STEP 7: PROCESS EACH JOB
// ============================================================================

$processed = 0;
$totalJobs = count($jobs);

foreach ($jobs as $job) {
    $processed++;

    // Extract job data
    $jobId     = (int)$job['id'];
    $userId    = (int)$job['user_id'];
    $attempts  = (int)$job['attempts'];

    // ====================================================================
    // STEP 7a: DECODE PAYLOAD - THIS APPLIES TO ALL JOBS
    // ====================================================================
    $payload = $job['payload'];

    // Decode JSON string to array - NEEDED FOR EVERY JOB
    if (is_string($payload)) {
        $payload = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Failed to decode payload for job $jobId: " . json_last_error_msg());
            $outboxRepo->markFailed($jobId, $attempts, "Invalid JSON payload: " . json_last_error_msg());
            continue;
        }
    }

    // If payload is not an array after decoding, mark as failed
    if (!is_array($payload)) {
        $outboxRepo->markFailed($jobId, $attempts, "Payload is not an array after decoding");
        continue;
    }

    error_log("Processing job $jobId for user $userId (attempt $attempts)");
    error_log("Decoded payload: " . json_encode($payload));

    try {
        // ====================================================================
        // STEP 7b: VALIDATE REQUIRED DATA
        // ====================================================================
        // Check for email_template directly in the decoded payload
        if (empty($payload['email_template'])) {
            throw new RuntimeException("Missing email_template in payload. Available keys: " . implode(', ', array_keys($payload)));
        }

        // ====================================================================
        // STEP 7c: LOAD USER INFORMATION
        // ====================================================================
        $user = $userRepo->findById($userId);
        if (!$user || empty($user['email'])) {
            throw new RuntimeException("User email not found for user_id=$userId");
        }

        $email = $user['email'];
        $role  = $user['role'] ?? 'jobseeker';
        $name  = $user['name'] ?? $user['first_name'] ?? $email;

        // ====================================================================
        // STEP 7d: PREPARE EMAIL VARIABLES
        // ====================================================================
        $template = $payload['email_template'];
        $vars     = $payload['email_vars'] ?? [];

        error_log("Processing template: $template for email: $email");
        error_log("Email vars: " . json_encode($vars));

        // ====================================================================
        // STEP 7e: ROUTE TO APPROPRIATE EMAIL METHOD
        // ====================================================================
        $sent = false;

        switch ($template) {
            case 'email_verification':
                // Handle verification emails
                $link = $vars['link'] ?? null;
                $expiresAt = $vars['expires_at'] ?? null;

                if (!$link) {
                    throw new RuntimeException("Missing verification link in email_vars");
                }

                error_log("Sending email verification to $email with link: $link");
                $sent = $emailService->sendEmailVerification($email, $link, $expiresAt);
                break;
            case 'password_reset':
                $link = $vars['link'] ?? null;
                $expiresAt = $vars['expires_at'] ?? null;

                if (!$link) {
                    throw new RuntimeException("Missing password reset link in email_vars");
                }

                error_log("Sending password reset to $email with link: $link");
                $sent = $emailService->sendPasswordReset($email, $link, $expiresAt);
                break;

            case 'verification_submitted':
                $displayName = $vars['display_name'] ?? $name;
                $userRole = $vars['user_role'] ?? $role;
                error_log("Sending verification submitted to $email for $displayName");
                $sent = $emailService->sendVerificationSubmitted($email, $displayName, $userRole);
                break;

            case 'verification_approved':
                $displayName = $vars['display_name'] ?? $name;
                $userRole = $vars['user_role'] ?? $role;
                error_log("Sending verification approved to $email for $displayName");
                $sent = $emailService->sendVerificationApproved($email, $displayName, $userRole);
                break;

            case 'verification_rejected':
                $displayName = $vars['display_name'] ?? $name;
                $userRole = $vars['user_role'] ?? $role;
                $reason = $vars['reason'] ?? 'No reason provided';
                error_log("Sending verification rejected to $email for $displayName. Reason: $reason");
                $sent = $emailService->sendVerificationRejected($email, $displayName, (string)$reason, $userRole);
                break;

            case 'verification_flagged':
                $displayName = $vars['display_name'] ?? $name;
                $userRole = $vars['user_role'] ?? $role;
                $reason = $vars['reason'] ?? 'No reason provided';
                error_log("Sending verification flagged to $email for $displayName. Reason: $reason");
                $sent = $emailService->sendVerificationFlagged($email, $displayName, (string)$reason, $userRole);
                break;

            // Handle any other templates from legacy format
            case 'verification_notification':
            case 'account_notification':
                // For legacy emails, extract from payload
                $subject = $payload['subject'] ?? $vars['subject'] ?? 'Notification';
                $message = $payload['message'] ?? $vars['message'] ?? 'No message provided';

                error_log("Legacy template '$template' detected for $email. Subject: $subject");
                // You'll need to implement a generic email method if you want to handle these
                throw new RuntimeException("Legacy template '$template' needs to be implemented in EmailService");
                break;

            default:
                // Try to handle as generic email if subject and message are provided
                if (isset($vars['subject']) && isset($vars['message'])) {
                    error_log("Generic email template '$template' for $email");
                    // $sent = $emailService->sendGenericEmail($email, $vars['subject'], $vars['message']);
                    throw new RuntimeException("Generic email sending not implemented for template '$template'");
                } else {
                    throw new RuntimeException("Unknown email_template '$template'");
                }
        }

        // Check if email was sent successfully
        if (!$sent) {
            throw new RuntimeException("EmailService returned false for template '$template'");
        }

        // ====================================================================
        // STEP 7f: MARK AS DELIVERED
        // ====================================================================
        $outboxRepo->markDelivered($jobId);
        error_log("✅ Successfully delivered job $jobId to $email");
    } catch (\Throwable $e) {
        // ====================================================================
        // STEP 7g: HANDLE FAILURE
        // ====================================================================
        $errorMessage = $e->getMessage();
        $outboxRepo->markFailed($jobId, $attempts, $errorMessage);
        error_log("❌ Failed to process job $jobId: $errorMessage");
    }

    // Log progress every 10 jobs
    if ($processed % 10 === 0) {
        error_log("Progress: $processed/$totalJobs jobs processed");
    }
}

error_log("✅ Email outbox worker completed at " . gmdate('Y-m-d H:i:s') . " - Processed $processed jobs");
exit(0);
