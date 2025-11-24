<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../includes/phpmailer/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../includes/phpmailer/PHPMailer-master/src/SMTP.php';
require_once __DIR__ . '/../includes/phpmailer/PHPMailer-master/src/Exception.php';

function sendMail($to, $subject, $body, $from = null, $fromName = null, $attachments = []) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = defined('SMTP_USERNAME') ? SMTP_USERNAME : (getenv('SMTP_USERNAME') ?: '');
        $mail->Password   = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : (getenv('SMTP_PASSWORD') ?: '');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Use TLS encryption
        // Keep existing option for local development; enable strict in production
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        $mail->Port       = defined('SMTP_PORT') ? SMTP_PORT : 587;

        // Resolve from defaults
        $from = $from ?? (defined('FROM_EMAIL') ? FROM_EMAIL : 'no-reply@example.com');
        $fromName = $fromName ?? (defined('FROM_NAME') ? FROM_NAME : 'SafeSpeak');

        $mail->setFrom($from, $fromName);
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        // Add attachments if provided
        if (!empty($attachments) && is_array($attachments)) {
            foreach ($attachments as $attachment) {
                if (isset($attachment['path']) && file_exists($attachment['path'])) {
                    $mail->addAttachment($attachment['path'], $attachment['name'] ?? basename($attachment['path']));
                }
            }
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mail Error: ' . $mail->ErrorInfo);
        return false;
    }
}