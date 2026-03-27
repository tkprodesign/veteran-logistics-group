<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Chicago');
require_once __DIR__ . '/../common-sections/globals.php';


$cpEmailConfig = [];
$cpEmailConfigPath = __DIR__ . '/../common-sections/email-secrets.php';
if (file_exists($cpEmailConfigPath)) {
    $loadedCpEmailConfig = include $cpEmailConfigPath;
    if (is_array($loadedCpEmailConfig)) {
        $cpEmailConfig = $loadedCpEmailConfig;
    }
}

if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
    $phpMailerCandidates = [
        __DIR__ . '/../common-sections/PHPMailer/src',
        __DIR__ . '/PHPMailer/src',
        __DIR__ . '/../PHPMailer/src',
        __DIR__ . '/../vendor/phpmailer/phpmailer/src',
    ];
    foreach ($phpMailerCandidates as $mailerSrcDir) {
        if (file_exists($mailerSrcDir . '/PHPMailer.php') && file_exists($mailerSrcDir . '/SMTP.php') && file_exists($mailerSrcDir . '/Exception.php')) {
            require_once $mailerSrcDir . '/PHPMailer.php';
            require_once $mailerSrcDir . '/SMTP.php';
            require_once $mailerSrcDir . '/Exception.php';
            break;
        }
    }
}

$allowedAdminEmails = [
    'tkprodesign96@gmail.com',
    'admin@veteranlogisticsgroup.com'
];

$cookieEmailRaw = '';
if (isset($_COOKIE['user_Email']) && $_COOKIE['user_Email'] !== '') {
    $cookieEmailRaw = (string)$_COOKIE['user_Email'];
} elseif (isset($_COOKIE['user_email']) && $_COOKIE['user_email'] !== '') {
    $cookieEmailRaw = (string)$_COOKIE['user_email'];
}

if ($cookieEmailRaw === '') {
    header('Location: /dashboard/');
    exit();
}

$cookieEmail = strtolower(trim($cookieEmailRaw));
if (!in_array($cookieEmail, $allowedAdminEmails, true)) {
    header('Location: /dashboard/');
    exit();
}

if (!isset($_SESSION['email']) || strtolower((string)$_SESSION['email']) !== $cookieEmail) {
    $_SESSION['email'] = $cookieEmail;
}

function cp_get_tracking_number_from_post(): string {
    $tracking = '';
    if (isset($_POST['tracking_number'])) {
        $tracking = trim((string)$_POST['tracking_number']);
    }
    if ($tracking === '' && isset($_POST['tracking_id'])) {
        $tracking = trim((string)$_POST['tracking_id']);
    }
    return $tracking;
}

function cp_map_shipment_type(string $raw): string {
    $raw = strtolower(trim($raw));
    if (in_array($raw, ['standard', 'express', 'overnight'], true)) {
        return $raw;
    }
    if ($raw === 'air') return 'express';
    if (in_array($raw, ['ship', 'road', 'rail'], true)) return 'standard';
    return 'standard';
}

$cp_quote_update_notice = '';
$cp_quote_update_notice_type = '';
$cp_quote_delete_notice = '';
$cp_quote_delete_notice_type = '';
$cp_user_delete_notice = '';
$cp_user_delete_notice_type = '';
$cp_shipment_delete_notice = '';
$cp_shipment_delete_notice_type = '';
$cp_location_event_notice = '';
$cp_location_event_notice_type = '';
$cp_user_pay_block_notice = '';
$cp_user_pay_block_notice_type = '';
$cp_support_email_notice = '';
$cp_support_email_notice_type = '';
$cp_exception_payment_notice = '';
$cp_exception_payment_notice_type = '';

function cp_ensure_shipment_location_event_payment_columns(mysqli $dbconn): void {
    $columnSql = [
        "ALTER TABLE shipment_location_events ADD COLUMN payment_amount DECIMAL(10,2) NULL DEFAULT NULL",
        "ALTER TABLE shipment_location_events ADD COLUMN payment_reason VARCHAR(255) NULL DEFAULT NULL"
    ];

    foreach ($columnSql as $sql) {
        try {
            $dbconn->query($sql);
        } catch (Throwable $e) {
            // Ignore duplicate-column and missing-table failures here; insert/query logic handles actual table usage.
        }
    }
}

if (isset($_SESSION['cp_quote_notice']) && is_array($_SESSION['cp_quote_notice'])) {
    $cp_quote_update_notice = (string)($_SESSION['cp_quote_notice']['message'] ?? '');
    $cp_quote_update_notice_type = (string)($_SESSION['cp_quote_notice']['type'] ?? '');
    unset($_SESSION['cp_quote_notice']);
}
if (isset($_SESSION['cp_quote_delete_notice']) && is_array($_SESSION['cp_quote_delete_notice'])) {
    $cp_quote_delete_notice = (string)($_SESSION['cp_quote_delete_notice']['message'] ?? '');
    $cp_quote_delete_notice_type = (string)($_SESSION['cp_quote_delete_notice']['type'] ?? '');
    unset($_SESSION['cp_quote_delete_notice']);
}
if (isset($_SESSION['cp_user_delete_notice']) && is_array($_SESSION['cp_user_delete_notice'])) {
    $cp_user_delete_notice = (string)($_SESSION['cp_user_delete_notice']['message'] ?? '');
    $cp_user_delete_notice_type = (string)($_SESSION['cp_user_delete_notice']['type'] ?? '');
    unset($_SESSION['cp_user_delete_notice']);
}
if (isset($_SESSION['cp_shipment_delete_notice']) && is_array($_SESSION['cp_shipment_delete_notice'])) {
    $cp_shipment_delete_notice = (string)($_SESSION['cp_shipment_delete_notice']['message'] ?? '');
    $cp_shipment_delete_notice_type = (string)($_SESSION['cp_shipment_delete_notice']['type'] ?? '');
    unset($_SESSION['cp_shipment_delete_notice']);
}
if (isset($_SESSION['cp_location_notice']) && is_array($_SESSION['cp_location_notice'])) {
    $cp_location_event_notice = (string)($_SESSION['cp_location_notice']['message'] ?? '');
    $cp_location_event_notice_type = (string)($_SESSION['cp_location_notice']['type'] ?? '');
    unset($_SESSION['cp_location_notice']);
}
if (isset($_SESSION['cp_user_block_notice']) && is_array($_SESSION['cp_user_block_notice'])) {
    $cp_user_pay_block_notice = (string)($_SESSION['cp_user_block_notice']['message'] ?? '');
    $cp_user_pay_block_notice_type = (string)($_SESSION['cp_user_block_notice']['type'] ?? '');
    unset($_SESSION['cp_user_block_notice']);
}
if (isset($_SESSION['cp_support_email_notice']) && is_array($_SESSION['cp_support_email_notice'])) {
    $cp_support_email_notice = (string)($_SESSION['cp_support_email_notice']['message'] ?? '');
    $cp_support_email_notice_type = (string)($_SESSION['cp_support_email_notice']['type'] ?? '');
    unset($_SESSION['cp_support_email_notice']);
}
if (isset($_SESSION['cp_exception_payment_notice']) && is_array($_SESSION['cp_exception_payment_notice'])) {
    $cp_exception_payment_notice = (string)($_SESSION['cp_exception_payment_notice']['message'] ?? '');
    $cp_exception_payment_notice_type = (string)($_SESSION['cp_exception_payment_notice']['type'] ?? '');
    unset($_SESSION['cp_exception_payment_notice']);
}


function cp_password_secret_for_mailbox(string $fromEmail): string {
    $mailbox = strtolower(trim(explode('@', $fromEmail)[0] ?? ''));
    $map = [
        'billing' => 'BILLING_EMAIL_PASSWORD',
        'shipments' => 'SHIPMENTS_EMAIL_PASSWORD',
        'admin' => 'ADMIN_EMAIL_PASSWORD',
        'support' => 'SUPPORT_EMAIL_PASSWORD',
        'tracking' => 'TRACKING_EMAIL_PASSWORD',
        'noreply' => 'NOREPLY_EMAIL_PASSWORD',
    ];
    return $map[$mailbox] ?? '';
}

function cp_resolve_secret(string $name): string {
    global $cpEmailConfig;
    if ($name === '') {
        return '';
    }
    $value = getenv($name);
    if ($value !== false && trim((string)$value) !== '') {
        return trim((string)$value);
    }
    if (isset($_ENV[$name]) && trim((string)$_ENV[$name]) !== '') {
        return trim((string)$_ENV[$name]);
    }
    if (isset($_SERVER[$name]) && trim((string)$_SERVER[$name]) !== '') {
        return trim((string)$_SERVER[$name]);
    }
    if (isset($cpEmailConfig[$name]) && trim((string)$cpEmailConfig[$name]) !== '') {
        return trim((string)$cpEmailConfig[$name]);
    }
    return '';
}

function cp_resolve_mail_setting(string $envName, string $default = ''): string {
    global $cpEmailConfig;
    $value = getenv($envName);
    if ($value !== false && trim((string)$value) !== '') {
        return trim((string)$value);
    }
    if (isset($_ENV[$envName]) && trim((string)$_ENV[$envName]) !== '') {
        return trim((string)$_ENV[$envName]);
    }
    if (isset($_SERVER[$envName]) && trim((string)$_SERVER[$envName]) !== '') {
        return trim((string)$_SERVER[$envName]);
    }
    if (isset($cpEmailConfig[$envName]) && trim((string)$cpEmailConfig[$envName]) !== '') {
        return trim((string)$cpEmailConfig[$envName]);
    }
    return $default;
}

function cp_send_smtp_html_email(string $toEmail, string $fromEmail, string $subject, string $htmlBody): bool {
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
        error_log('control-panel: PHPMailer is not available');
        return false;
    }

    $passwordSecret = cp_password_secret_for_mailbox($fromEmail);
    $smtpPassword = cp_resolve_secret($passwordSecret);
    if ($smtpPassword === '') {
        error_log('control-panel: missing smtp password secret for ' . $fromEmail . ' expected_secret=' . $passwordSecret);
        return false;
    }

    $smtpHost = cp_resolve_mail_setting('SMTP_HOST', 'mail.spacemail.com');
    $smtpPort = (int)cp_resolve_mail_setting('SMTP_PORT', '465');
    $smtpSecure = cp_resolve_mail_setting('SMTP_SECURE', \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS);

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $fromEmail;
        $mail->Password = $smtpPassword;
        $mail->SMTPSecure = $smtpSecure;
        $mail->Port = $smtpPort;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom($fromEmail, 'Veteran Logistics Group');
        $mail->addAddress($toEmail);
        $mail->addReplyTo('support@veteranlogisticsgroup.us', 'Veteran Logistics Group Support');
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = trim(preg_replace('/\s+/', ' ', strip_tags($htmlBody)));
        return $mail->send();
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        error_log('control-panel: PHPMailer failed for to=' . $toEmail . ' from=' . $fromEmail . ' subject=' . $subject . ' err=' . $e->getMessage());
    }

    return false;
}

function cp_build_location_event_email_html(array $payload, string $recipientType): string {
    $trackingNumber = htmlspecialchars((string)($payload['tracking_number'] ?? '-'), ENT_QUOTES, 'UTF-8');
    $statusText = htmlspecialchars((string)($payload['status_text'] ?? '-'), ENT_QUOTES, 'UTF-8');
    $locationName = htmlspecialchars((string)($payload['location_name'] ?? '-'), ENT_QUOTES, 'UTF-8');
    $city = htmlspecialchars((string)($payload['city'] ?? ''), ENT_QUOTES, 'UTF-8');
    $stateRegion = htmlspecialchars((string)($payload['state_region'] ?? ''), ENT_QUOTES, 'UTF-8');
    $countryCode = htmlspecialchars((string)($payload['country_code'] ?? ''), ENT_QUOTES, 'UTF-8');
    $eventTimeEpoch = (int)($payload['event_time_epoch'] ?? 0);
    $eventTimeText = $eventTimeEpoch > 0 ? date('F j, Y h:i A T', $eventTimeEpoch) : '-';
    $eventTimeText = htmlspecialchars($eventTimeText, ENT_QUOTES, 'UTF-8');
    $recipientName = trim((string)($payload[$recipientType . '_name'] ?? 'Customer'));
    $safeRecipientName = htmlspecialchars($recipientName !== '' ? $recipientName : 'Customer', ENT_QUOTES, 'UTF-8');
    $roleText = $recipientType === 'sender' ? 'sender' : 'receiver';
    $safeRoleText = htmlspecialchars($roleText, ENT_QUOTES, 'UTF-8');
    $locationPieces = array_filter([$locationName, $city, $stateRegion, $countryCode], static fn($v) => trim((string)$v) !== '');
    $locationText = implode(', ', array_map(static fn($v) => htmlspecialchars_decode($v, ENT_QUOTES), $locationPieces));
    $safeLocationText = htmlspecialchars($locationText !== '' ? $locationText : '-', ENT_QUOTES, 'UTF-8');
    $trackUrl = 'https://veteranlogisticsgroup.us/track/?id=' . rawurlencode((string)($payload['tracking_number'] ?? ''));

    return '<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Shipment Location Update</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f3f4f6;padding:24px 0;">
<tr><td align="center">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="640" style="max-width:640px;background-color:#ffffff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
<tr><td style="background-color:#0f172a;padding:16px 28px;"><img src="https://veteranlogisticsgroup.us/assets/images/branding/logo-horizontal-dark.png" alt="Veteran Logistics Group" width="220" style="display:block;border:0;max-width:220px;height:auto;"></td></tr>
<tr><td style="padding:24px 40px 8px 40px;"><h1 style="margin:0;font-size:24px;line-height:1.3;color:#0f172a;">Shipment location event added</h1></td></tr>
<tr><td style="padding:0 40px 14px 40px;"><p style="margin:0;font-size:15px;line-height:1.7;color:#374151;">Hello ' . $safeRecipientName . ', this is an automatic update for the ' . $safeRoleText . ' on your shipment.</p></td></tr>
<tr><td style="padding:0 40px 18px 40px;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #e5e7eb;border-radius:8px;">
<tr><td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#6b7280;">Tracking Number</td><td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;font-size:14px;color:#111827;">' . $trackingNumber . '</td></tr>
<tr><td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#6b7280;">Status</td><td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;font-size:14px;color:#111827;">' . $statusText . '</td></tr>
<tr><td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#6b7280;">Location</td><td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;font-size:14px;color:#111827;">' . $safeLocationText . '</td></tr>
<tr><td style="padding:12px 14px;font-size:13px;color:#6b7280;">Event Time</td><td style="padding:12px 14px;font-size:14px;color:#111827;">' . $eventTimeText . '</td></tr>
</table>
</td></tr>
<tr><td style="padding:0 40px 24px 40px;"><a href="' . htmlspecialchars($trackUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;background-color:#1d4ed8;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:6px;font-size:14px;font-weight:bold;">Track Shipment</a></td></tr>
<tr><td style="background-color:#f8fafc;border-top:1px solid #e5e7eb;padding:16px 24px;"><p style="margin:0;font-size:11px;line-height:1.5;color:#6b7280;">© 2026 Veteran Logistics Group. Please do not reply to this email.</p></td></tr>
</table>
</td></tr>
</table>
</body>
</html>';
}

function cp_send_location_event_notifications(mysqli $dbconn, int $shipmentId, string $trackingNumber, array $eventPayload): array {
    $shipmentRow = null;

    if ($shipmentId > 0) {
        $stmtShipment = $dbconn->prepare(
            "SELECT tracking_number, sender_name, sender_email, receiver_name, receiver_email
             FROM shipments
             WHERE id = ?
             LIMIT 1"
        );
        if ($stmtShipment) {
            $stmtShipment->bind_param('i', $shipmentId);
            $stmtShipment->execute();
            $res = $stmtShipment->get_result();
            $shipmentRow = $res ? $res->fetch_assoc() : null;
            $stmtShipment->close();
        }
    }

    if (!$shipmentRow && $trackingNumber !== '') {
        $stmtShipment = $dbconn->prepare(
            "SELECT tracking_number, sender_name, sender_email, receiver_name, receiver_email
             FROM shipments
             WHERE tracking_number = ?
             ORDER BY id DESC
             LIMIT 1"
        );
        if ($stmtShipment) {
            $stmtShipment->bind_param('s', $trackingNumber);
            $stmtShipment->execute();
            $res = $stmtShipment->get_result();
            $shipmentRow = $res ? $res->fetch_assoc() : null;
            $stmtShipment->close();
        }
    }

    if (!$shipmentRow) {
        return ['attempted' => 0, 'sent' => 0, 'failed' => 0, 'error' => 'Shipment record not found for notification emails.'];
    }

    $payload = array_merge($shipmentRow, $eventPayload);
    $subject = 'Shipment Tracking Update: ' . (string)($payload['tracking_number'] ?? $trackingNumber);

    $recipients = [
        'sender' => trim((string)($shipmentRow['sender_email'] ?? '')),
        'receiver' => trim((string)($shipmentRow['receiver_email'] ?? '')),
    ];

    $attempted = 0;
    $sent = 0;
    $failed = 0;

    foreach ($recipients as $role => $email) {
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            continue;
        }
        $attempted++;
        $html = cp_build_location_event_email_html($payload, $role);
        if (cp_send_smtp_html_email($email, 'tracking@veteranlogisticsgroup.us', $subject, $html)) {
            $sent++;
        } else {
            $failed++;
        }
    }

    return ['attempted' => $attempted, 'sent' => $sent, 'failed' => $failed];
}

function cp_send_resend_html_email(string $toEmail, string $subject, string $html): array {
    $apiKey = getenv('RESEND_API_KEY');
    if (!$apiKey || trim($apiKey) === '') {
        $apiKey = 're_AzyocZ26_Lx4bpNbTyHtUFxpikY4mBjjE';
    }
    $apiKey = trim((string)$apiKey);
    if ($apiKey === '') {
        return ['ok' => false, 'error' => 'Missing Resend API key.'];
    }

    $payload = [
        'from' => 'support@veteranlogisticsgroup.com',
        'to' => [$toEmail],
        'subject' => $subject,
        'html' => $html
    ];

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$apiKey}",
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr !== '') {
        return ['ok' => false, 'error' => 'Resend request failed: ' . $curlErr];
    }
    if ($httpCode !== 200 && $httpCode !== 201) {
        return ['ok' => false, 'error' => 'Resend rejected request (' . $httpCode . ').', 'response' => $response];
    }

    return ['ok' => true, 'response' => $response];
}

function cp_build_support_email_html(string $messageBody, string $adminEmail): string {
    $safeAdmin = htmlspecialchars($adminEmail, ENT_QUOTES, 'UTF-8');
    $safeMessage = nl2br(htmlspecialchars($messageBody, ENT_QUOTES, 'UTF-8'));

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Veteran Logistics Group Support Message</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f4;font-family:Helvetica,Arial,sans-serif;">
<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f4f4f4;padding:40px 0;">
<tr>
<td align="center">
<table border="0" cellpadding="0" cellspacing="0" width="600" style="background-color:#ffffff;border-radius:4px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
<tr>
<td align="center" style="padding:40px 40px 20px 40px;">
<img src="https://veteranlogisticsgroup.com/assets/images/branding/logo-stacked-light.png" alt="Veteran Logistics Group" width="60" style="display:block;border:0;">
</td>
</tr>
<tr>
<td align="center" style="padding:0 60px 40px 60px;color:#333333;">
<p style="font-size:16px;margin:0 0 15px 0;">Hello,</p>
<h1 style="font-size:28px;line-height:1.3;margin:0 0 20px 0;font-weight:500;">Support Update</h1>
<p style="font-size:14px;color:#666666;margin:0 0 30px 0;">You have a new message from Veteran Logistics Group support:</p>
<div style="font-size:16px;line-height:1.6;color:#222;padding:18px;background-color:#ffffff;border:1px solid #eeeeee;display:block;border-radius:4px;text-align:left;">
{$safeMessage}
</div>
<p style="font-size:13px;color:#888888;margin:24px 0 0 0;">Sent by: {$safeAdmin}</p>
</td>
</tr>
<tr>
<td align="center" style="background-color:#f9f9f9;padding:30px 40px;border-top:1px solid #eeeeee;">
<p style="font-size:11px;color:#999999;line-height:1.5;margin:0 0 15px 0;">
&copy;2026 Veteran Logistics Group. All rights reserved.
</p>
<p style="font-size:11px;color:#999999;margin:0 0 15px 0;">Please do not reply to this email.</p>
</td>
</tr>
</table>
</td>
</tr>
</table>
</body>
</html>
HTML;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_support_email']) && !empty($_POST['send_support_email'])) {
    $receiverEmail = trim((string)($_POST['support_receiver_email'] ?? ''));
    $subject = trim((string)($_POST['support_subject'] ?? ''));
    $messageBody = trim((string)($_POST['support_message'] ?? ''));

    if ($receiverEmail === '' || !filter_var($receiverEmail, FILTER_VALIDATE_EMAIL)) {
        $cp_support_email_notice = 'Receiver email must be valid.';
        $cp_support_email_notice_type = 'error';
    } elseif ($subject === '') {
        $cp_support_email_notice = 'Subject is required.';
        $cp_support_email_notice_type = 'error';
    } elseif ($messageBody === '') {
        $cp_support_email_notice = 'Message is required.';
        $cp_support_email_notice_type = 'error';
    } else {
        $html = cp_build_support_email_html($messageBody, $cookieEmail);
        $sendResult = cp_send_resend_html_email($receiverEmail, $subject, $html);
        if (!empty($sendResult['ok'])) {
            $cp_support_email_notice = 'Support email sent successfully.';
            $cp_support_email_notice_type = 'success';
        } else {
            $cp_support_email_notice = (string)($sendResult['error'] ?? 'Support email send failed.');
            $cp_support_email_notice_type = 'error';
        }
    }

    $_SESSION['cp_support_email_notice'] = [
        'message' => $cp_support_email_notice,
        'type' => $cp_support_email_notice_type
    ];
    header('Location: /control-panel/page/#cp-support-email');
    exit();
}

// Confirm exception issue payment by id
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_exception_payment']) && !empty($_POST['confirm_exception_payment'])) {
    $exceptionPaymentId = isset($_POST['exception_payment_id']) ? (int)$_POST['exception_payment_id'] : 0;
    $confirmedAt = time();

    if ($exceptionPaymentId <= 0) {
        $cp_exception_payment_notice = 'Payment ID must be a valid number.';
        $cp_exception_payment_notice_type = 'error';
    } else {
        $stmt = $dbconn->prepare(
            "UPDATE exception_issue_payments
             SET status = 'confirmed',
                 updated_at_epoch = ?,
                 confirmed_at_epoch = ?,
                 confirmed_by = ?
             WHERE id = ? AND status = 'pending_confirmation'
             LIMIT 1"
        );

        if (!$stmt) {
            $cp_exception_payment_notice = 'Unable to prepare exception payment confirmation.';
            $cp_exception_payment_notice_type = 'error';
        } else {
            $stmt->bind_param("iisi", $confirmedAt, $confirmedAt, $cookieEmail, $exceptionPaymentId);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();

            if ($affected > 0) {
                $cp_exception_payment_notice = "Exception payment #{$exceptionPaymentId} confirmed successfully.";
                $cp_exception_payment_notice_type = 'success';
            } else {
                $stmtCheck = $dbconn->prepare("SELECT id, status FROM exception_issue_payments WHERE id = ? LIMIT 1");
                if ($stmtCheck) {
                    $stmtCheck->bind_param("i", $exceptionPaymentId);
                    $stmtCheck->execute();
                    $resCheck = $stmtCheck->get_result();
                    $rowCheck = $resCheck ? $resCheck->fetch_assoc() : null;
                    $stmtCheck->close();

                    if (!$rowCheck) {
                        $cp_exception_payment_notice = "Exception payment #{$exceptionPaymentId} was not found.";
                    } elseif (strtolower((string)($rowCheck['status'] ?? '')) === 'confirmed') {
                        $cp_exception_payment_notice = "Exception payment #{$exceptionPaymentId} is already confirmed.";
                    } else {
                        $cp_exception_payment_notice = "Exception payment #{$exceptionPaymentId} could not be confirmed from its current status.";
                    }
                } else {
                    $cp_exception_payment_notice = "Exception payment #{$exceptionPaymentId} could not be confirmed.";
                }
                $cp_exception_payment_notice_type = 'error';
            }
        }
    }

    $_SESSION['cp_exception_payment_notice'] = [
        'message' => $cp_exception_payment_notice,
        'type' => $cp_exception_payment_notice_type
    ];
    header('Location: /control-panel/page/#cp-exception-payments');
    exit();
}

// Delete users row by id
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_site_user']) && !empty($_POST['delete_site_user'])) {
    $userId = isset($_POST['delete_user_id']) ? (int)$_POST['delete_user_id'] : 0;

    if ($userId <= 0) {
        $cp_user_delete_notice = 'User ID must be a valid number.';
        $cp_user_delete_notice_type = 'error';
    } else {
        $stmt = $dbconn->prepare("DELETE FROM users WHERE id = ? LIMIT 1");
        if (!$stmt) {
            $cp_user_delete_notice = 'Unable to prepare user delete.';
            $cp_user_delete_notice_type = 'error';
        } else {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();

            if ($affected > 0) {
                $cp_user_delete_notice = "User #{$userId} deleted successfully.";
                $cp_user_delete_notice_type = 'success';
            } else {
                $cp_user_delete_notice = "User #{$userId} was not found.";
                $cp_user_delete_notice_type = 'error';
            }
        }
    }

    $_SESSION['cp_user_delete_notice'] = [
        'message' => $cp_user_delete_notice,
        'type' => $cp_user_delete_notice_type
    ];
    header('Location: /control-panel/page/#cp-delete-site-user');
    exit();
}

// Delete shipment row by id
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_shipment_record']) && !empty($_POST['delete_shipment_record'])) {
    $shipmentId = isset($_POST['delete_shipment_id']) ? (int)$_POST['delete_shipment_id'] : 0;

    if ($shipmentId <= 0) {
        $cp_shipment_delete_notice = 'Shipment ID must be a valid number.';
        $cp_shipment_delete_notice_type = 'error';
    } else {
        $stmt = $dbconn->prepare("DELETE FROM shipments WHERE id = ? LIMIT 1");
        if (!$stmt) {
            $cp_shipment_delete_notice = 'Unable to prepare shipment delete.';
            $cp_shipment_delete_notice_type = 'error';
        } else {
            $stmt->bind_param("i", $shipmentId);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();

            if ($affected > 0) {
                $cp_shipment_delete_notice = "Shipment #{$shipmentId} deleted successfully.";
                $cp_shipment_delete_notice_type = 'success';
            } else {
                $cp_shipment_delete_notice = "Shipment #{$shipmentId} was not found.";
                $cp_shipment_delete_notice_type = 'error';
            }
        }
    }

    $_SESSION['cp_shipment_delete_notice'] = [
        'message' => $cp_shipment_delete_notice,
        'type' => $cp_shipment_delete_notice_type
    ];
    header('Location: /control-panel/page/#cp-delete-shipment');
    exit();
}

// Update shipment_service_quotes (price + duration) by id
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_service_quote']) && !empty($_POST['update_service_quote'])) {
    $quoteId = isset($_POST['quote_id']) ? (int)$_POST['quote_id'] : 0;
    $priceRaw = trim((string)($_POST['quote_price'] ?? ''));
    $durationRaw = trim((string)($_POST['quote_duration'] ?? ''));

    if ($quoteId <= 0) {
        $cp_quote_update_notice = 'Quote ID must be a valid number.';
        $cp_quote_update_notice_type = 'error';
    } elseif ($priceRaw === '' || !is_numeric($priceRaw) || (float)$priceRaw < 0) {
        $cp_quote_update_notice = 'Price must be a valid number (0 or greater).';
        $cp_quote_update_notice_type = 'error';
    } elseif ($durationRaw === '' || !ctype_digit($durationRaw) || (int)$durationRaw <= 0) {
        $cp_quote_update_notice = 'Duration must be a whole number greater than 0.';
        $cp_quote_update_notice_type = 'error';
    } else {
        $price = (float)$priceRaw;
        $duration = (int)$durationRaw;
        $updatedAt = time();

        $sql = "UPDATE shipment_service_quotes
                SET price = ?, duration = ?, updated_at_epoch = ?
                WHERE id = ?
                LIMIT 1";
        $stmt = $dbconn->prepare($sql);

        if (!$stmt) {
            $cp_quote_update_notice = 'Unable to prepare quote update.';
            $cp_quote_update_notice_type = 'error';
        } else {
            $stmt->bind_param("diii", $price, $duration, $updatedAt, $quoteId);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();

            if ($affected > 0) {
                $cp_quote_update_notice = "Quote #{$quoteId} updated successfully.";
                $cp_quote_update_notice_type = 'success';
            } else {
                $cp_quote_update_notice = "No record updated. Check if Quote ID #{$quoteId} exists or values are unchanged.";
                $cp_quote_update_notice_type = 'error';
            }
        }
    }

    $_SESSION['cp_quote_notice'] = [
        'message' => $cp_quote_update_notice,
        'type' => $cp_quote_update_notice_type
    ];
    header('Location: /control-panel/page/#cp-edit-service-quote');
    exit();
}

// Delete shipment_service_quotes row by id
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_service_quote']) && !empty($_POST['delete_service_quote'])) {
    $quoteId = isset($_POST['delete_quote_id']) ? (int)$_POST['delete_quote_id'] : 0;

    if ($quoteId <= 0) {
        $cp_quote_delete_notice = 'Quote ID must be a valid number.';
        $cp_quote_delete_notice_type = 'error';
    } else {
        $stmt = $dbconn->prepare("DELETE FROM shipment_service_quotes WHERE id = ? LIMIT 1");
        if (!$stmt) {
            $cp_quote_delete_notice = 'Unable to prepare quote delete.';
            $cp_quote_delete_notice_type = 'error';
        } else {
            $stmt->bind_param("i", $quoteId);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();

            if ($affected > 0) {
                $cp_quote_delete_notice = "Quote #{$quoteId} deleted successfully.";
                $cp_quote_delete_notice_type = 'success';
            } else {
                $cp_quote_delete_notice = "Quote #{$quoteId} was not found.";
                $cp_quote_delete_notice_type = 'error';
            }
        }
    }

    $_SESSION['cp_quote_delete_notice'] = [
        'message' => $cp_quote_delete_notice,
        'type' => $cp_quote_delete_notice_type
    ];
    header('Location: /control-panel/page/#cp-delete-service-quote');
    exit();
}

// Insert shipment_location_events row
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_location_event']) && !empty($_POST['add_location_event'])) {
    cp_ensure_shipment_location_event_payment_columns($dbconn);

    $shipmentId = isset($_POST['event_shipment_id']) ? (int)$_POST['event_shipment_id'] : 0;
    $trackingNumber = trim((string)($_POST['event_tracking_number'] ?? ''));
    $locationLabel = strtolower(trim((string)($_POST['event_location_label'] ?? 'checkpoint')));
    $eventSeverity = strtolower(trim((string)($_POST['event_severity'] ?? 'neutral')));
    $isCurrent = 1;
    $isOrigin = 0;
    $isDestination = 0;
    $locationName = trim((string)($_POST['event_location_name'] ?? ''));
    $city = trim((string)($_POST['event_city'] ?? ''));
    $stateRegion = trim((string)($_POST['event_state_region'] ?? ''));
    $countryCode = strtoupper(trim((string)($_POST['event_country_code'] ?? 'US')));
    $postalCode = trim((string)($_POST['event_postal_code'] ?? ''));
    $statusText = trim((string)($_POST['event_status_text'] ?? ''));
    $issueNote = '';
    $paymentAmountRaw = trim((string)($_POST['event_payment_amount'] ?? ''));
    $paymentReason = trim((string)($_POST['event_payment_reason'] ?? ''));
    $nowEpoch = time();
    $eventTimeEpoch = $nowEpoch;

    $validLocationLabels = ['origin', 'checkpoint', 'exception', 'destination'];
    $validSeverities = ['neutral', 'negative'];
    if ($shipmentId <= 0) {
        $cp_location_event_notice = 'Shipment ID must be a valid number.';
        $cp_location_event_notice_type = 'error';
    } elseif ($trackingNumber === '') {
        $cp_location_event_notice = 'Tracking Number is required.';
        $cp_location_event_notice_type = 'error';
    } elseif ($locationName === '') {
        $cp_location_event_notice = 'Location Name is required.';
        $cp_location_event_notice_type = 'error';
    } elseif ($statusText === '') {
        $cp_location_event_notice = 'Status Text is required.';
        $cp_location_event_notice_type = 'error';
    } elseif (!in_array($locationLabel, $validLocationLabels, true)) {
        $cp_location_event_notice = 'Location label is invalid.';
        $cp_location_event_notice_type = 'error';
    } elseif (!in_array($eventSeverity, $validSeverities, true)) {
        $cp_location_event_notice = 'Event severity is invalid.';
        $cp_location_event_notice_type = 'error';
    } elseif (!preg_match('/^[A-Z]{2}$/', $countryCode)) {
        $cp_location_event_notice = 'Country code must be a valid 2-letter code (e.g., US).';
        $cp_location_event_notice_type = 'error';
    } elseif ($paymentAmountRaw !== '' && (!is_numeric($paymentAmountRaw) || (float)$paymentAmountRaw < 0)) {
        $cp_location_event_notice = 'Payment amount must be a valid number (0 or greater).';
        $cp_location_event_notice_type = 'error';
    } elseif ($paymentAmountRaw !== '' && $paymentReason === '') {
        $cp_location_event_notice = 'Add what the payment is for when a payment amount is provided.';
        $cp_location_event_notice_type = 'error';
    } else {
        $paymentAmount = ($paymentAmountRaw !== '') ? (float)$paymentAmountRaw : null;

        if ($locationLabel === 'origin') {
            $isOrigin = 1;
            $isDestination = 0;
        } elseif ($locationLabel === 'destination') {
            $isOrigin = 0;
            $isDestination = 1;
        } else {
            $isOrigin = 0;
            $isDestination = 0;
        }

        // Keep a single "current" event for this shipment/tracking.
        $stmtClearCurrent = $dbconn->prepare(
            "UPDATE shipment_location_events
             SET is_current = NULL, updated_at_epoch = ?
             WHERE shipment_id = ? OR tracking_number = ?"
        );
        if ($stmtClearCurrent) {
            $stmtClearCurrent->bind_param("iis", $nowEpoch, $shipmentId, $trackingNumber);
            try {
                $stmtClearCurrent->execute();
            } catch (Throwable $e) {
                // Keep moving; the insert path below will surface a proper admin notice if needed.
            }
            $stmtClearCurrent->close();
        }

        // Unique indexes allow only one origin and one destination flag per shipment.
        if ($isOrigin === 1) {
            $stmtClearOrigin = $dbconn->prepare(
                "UPDATE shipment_location_events
                 SET is_origin = NULL, updated_at_epoch = ?
                 WHERE (shipment_id = ? OR tracking_number = ?) AND is_origin = 1"
            );
            if ($stmtClearOrigin) {
                $stmtClearOrigin->bind_param("iis", $nowEpoch, $shipmentId, $trackingNumber);
                try {
                    $stmtClearOrigin->execute();
                } catch (Throwable $e) {
                    // If this cleanup fails, the insert below will still produce a user-facing notice.
                }
                $stmtClearOrigin->close();
            }
        }

        if ($isDestination === 1) {
            $stmtClearDestination = $dbconn->prepare(
                "UPDATE shipment_location_events
                 SET is_destination = NULL, updated_at_epoch = ?
                 WHERE (shipment_id = ? OR tracking_number = ?) AND is_destination = 1"
            );
            if ($stmtClearDestination) {
                $stmtClearDestination->bind_param("iis", $nowEpoch, $shipmentId, $trackingNumber);
                try {
                    $stmtClearDestination->execute();
                } catch (Throwable $e) {
                    // If this cleanup fails, the insert below will still produce a user-facing notice.
                }
                $stmtClearDestination->close();
            }
        }

        $sql = "INSERT INTO shipment_location_events
                (shipment_id, tracking_number, location_label, event_severity, is_current, is_origin, is_destination, location_name, city, state_region, country_code, postal_code, status_text, issue_note, payment_amount, payment_reason, event_time_epoch, created_at_epoch, updated_at_epoch)
                VALUES (?, ?, ?, ?, ?, IF(? = 1, 1, NULL), IF(? = 1, 1, NULL), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $dbconn->prepare($sql);

        if (!$stmt) {
            $cp_location_event_notice = 'Unable to prepare location event insert.';
            $cp_location_event_notice_type = 'error';
        } else {
            $stmt->bind_param(
                "isssiiisssssssdsiii",
                $shipmentId,
                $trackingNumber,
                $locationLabel,
                $eventSeverity,
                $isCurrent,
                $isOrigin,
                $isDestination,
                $locationName,
                $city,
                $stateRegion,
                $countryCode,
                $postalCode,
                $statusText,
                $issueNote,
                $paymentAmount,
                $paymentReason,
                $eventTimeEpoch,
                $nowEpoch,
                $nowEpoch
            );

            try {
                if ($stmt->execute()) {
                    $insertedId = (int)$stmt->insert_id;
                    $notificationResult = cp_send_location_event_notifications(
                        $dbconn,
                        $shipmentId,
                        $trackingNumber,
                        [
                            'tracking_number' => $trackingNumber,
                            'status_text' => $statusText,
                            'location_name' => $locationName,
                            'city' => $city,
                            'state_region' => $stateRegion,
                            'country_code' => $countryCode,
                            'event_time_epoch' => $eventTimeEpoch,
                        ]
                    );

                    $cp_location_event_notice = "Location event #{$insertedId} added successfully.";
                    if ((int)($notificationResult['attempted'] ?? 0) > 0) {
                        $cp_location_event_notice .= ' Email notifications sent: ' . (int)$notificationResult['sent'] . '/' . (int)$notificationResult['attempted'] . '.';
                    } elseif (!empty($notificationResult['error'])) {
                        $cp_location_event_notice .= ' ' . (string)$notificationResult['error'];
                    } else {
                        $cp_location_event_notice .= ' No valid sender/receiver email found for notification.';
                    }

                    $cp_location_event_notice_type = ((int)($notificationResult['failed'] ?? 0) > 0) ? 'error' : 'success';
                } else {
                    $cp_location_event_notice = 'Could not insert location event. Check shipment/tracking values and try again.';
                    $cp_location_event_notice_type = 'error';
                }
            } catch (Throwable $e) {
                $cp_location_event_notice = 'Could not insert location event. Existing origin/destination flags were adjusted if needed, but the new event still failed validation.';
                $cp_location_event_notice_type = 'error';
            }
            $stmt->close();
        }
    }

    $_SESSION['cp_location_notice'] = [
        'message' => $cp_location_event_notice,
        'type' => $cp_location_event_notice_type
    ];
    header('Location: /control-panel/page/#cp-add-location-event');
    exit();
}

// Set users.pay_block = 1 and update users.pay_block_tittle/users.pay_block_message by user id
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_user_pay_block']) && !empty($_POST['update_user_pay_block'])) {
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $payBlockTittle = trim((string)($_POST['pay_block_tittle'] ?? ''));
    $payBlockMessage = trim((string)($_POST['pay_block_message'] ?? ''));
    $payBlock = 1;

    if ($userId <= 0) {
        $cp_user_pay_block_notice = 'User ID must be a valid number.';
        $cp_user_pay_block_notice_type = 'error';
    } else {
        $userExists = false;
        $stmtUserCheck = $dbconn->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
        if ($stmtUserCheck) {
            $stmtUserCheck->bind_param("i", $userId);
            $stmtUserCheck->execute();
            $resUserCheck = $stmtUserCheck->get_result();
            $userExists = ($resUserCheck && $resUserCheck->num_rows > 0);
            $stmtUserCheck->close();
        }
        if (!$userExists) {
            $cp_user_pay_block_notice = "User #{$userId} was not found.";
            $cp_user_pay_block_notice_type = 'error';
        } elseif ($payBlockTittle === '' && $payBlockMessage === '') {
            $sql = "UPDATE users SET pay_block = ?, pay_block_tittle = NULL, pay_block_message = NULL WHERE id = ? LIMIT 1";
            $stmt = $dbconn->prepare($sql);
            if (!$stmt) {
                $cp_user_pay_block_notice = 'Unable to prepare user payment-block update.';
                $cp_user_pay_block_notice_type = 'error';
            } else {
                $stmt->bind_param("ii", $payBlock, $userId);
                $stmt->execute();
                $affected = $stmt->affected_rows;
                $stmt->close();

                if ($affected > 0) {
                    $cp_user_pay_block_notice = "User #{$userId} payment block updated.";
                } else {
                    $cp_user_pay_block_notice = "User #{$userId} already has these payment block values.";
                }
                $cp_user_pay_block_notice_type = 'success';
            }
        } elseif ($payBlockTittle === '') {
            $sql = "UPDATE users SET pay_block = ?, pay_block_tittle = NULL, pay_block_message = ? WHERE id = ? LIMIT 1";
            $stmt = $dbconn->prepare($sql);
            if (!$stmt) {
                $cp_user_pay_block_notice = 'Unable to prepare user payment-block update.';
                $cp_user_pay_block_notice_type = 'error';
            } else {
                $stmt->bind_param("isi", $payBlock, $payBlockMessage, $userId);
                $stmt->execute();
                $affected = $stmt->affected_rows;
                $stmt->close();

                if ($affected > 0) {
                    $cp_user_pay_block_notice = "User #{$userId} payment block updated.";
                } else {
                    $cp_user_pay_block_notice = "User #{$userId} already has these payment block values.";
                }
                $cp_user_pay_block_notice_type = 'success';
            }
        } elseif ($payBlockMessage === '') {
            $sql = "UPDATE users SET pay_block = ?, pay_block_tittle = ?, pay_block_message = NULL WHERE id = ? LIMIT 1";
            $stmt = $dbconn->prepare($sql);
            if (!$stmt) {
                $cp_user_pay_block_notice = 'Unable to prepare user payment-block update.';
                $cp_user_pay_block_notice_type = 'error';
            } else {
                $stmt->bind_param("isi", $payBlock, $payBlockTittle, $userId);
                $stmt->execute();
                $affected = $stmt->affected_rows;
                $stmt->close();

                if ($affected > 0) {
                    $cp_user_pay_block_notice = "User #{$userId} payment block updated.";
                } else {
                    $cp_user_pay_block_notice = "User #{$userId} already has these payment block values.";
                }
                $cp_user_pay_block_notice_type = 'success';
            }
        } else {
            $sql = "UPDATE users SET pay_block = ?, pay_block_tittle = ?, pay_block_message = ? WHERE id = ? LIMIT 1";
            $stmt = $dbconn->prepare($sql);
            if (!$stmt) {
                $cp_user_pay_block_notice = 'Unable to prepare user payment-block update.';
                $cp_user_pay_block_notice_type = 'error';
            } else {
                $stmt->bind_param("issi", $payBlock, $payBlockTittle, $payBlockMessage, $userId);
                $stmt->execute();
                $affected = $stmt->affected_rows;
                $stmt->close();

                if ($affected > 0) {
                    $cp_user_pay_block_notice = "User #{$userId} payment block updated.";
                } else {
                    $cp_user_pay_block_notice = "User #{$userId} already has these payment block values.";
                }
                $cp_user_pay_block_notice_type = 'success';
            }
        }
    }

    $_SESSION['cp_user_block_notice'] = [
        'message' => $cp_user_pay_block_notice,
        'type' => $cp_user_pay_block_notice_type
    ];
    header('Location: /control-panel/page/#cp-user-payment-block');
    exit();
}





// Subription Function
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['subscribe_button']) && !empty($_POST['subscribe_button'])) {
    header("Refresh:0");
    exit();
    $subscribe_email = $_POST['subscribe_email'];
    $stmt = $dbconn->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?)");
    $stmt->bind_param("s", $subscribe_email);
    $stmt->execute();
    $stmt->close();
    
    // Subscriber notification to admin email
$to = 'admin@veteranlogisticsgroup.com';
$from = 'alert@veteranlogisticsgroup.com';
    $fromName = 'Alert'; 
    
    $subject = 'New Subcriber'; 
    
    $htmlContent = ' 
        <html> 
        <head> 
            <title>New Subsriber | Levend Shipping</title> 
        </head> 
        <body style="border: 2px dashed #230c54; padding-left: 5px; padding-right: 5px;"> 
            <h1>You have a new subsriber to your newsletter!</h1>
            <h3 style="color: #1D1D37;">'.$subscribe_email.'</h3>
        </body> 
        </html>'; 
    
        // Set content-type header for sending HTML email 
        $headers = "MIME-Version: 1.0" . "\r\n"; 
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n"; 
        
        // Additional headers 
        $headers .= 'From: '.$fromName.'<'.$from.'>' . "\r\n"; 
        
        
        // Send email 
        if(mail($to, $subject, $htmlContent, $headers)){ 
            $mail_sent = 'Email has sent successfully.';
            $value = 1;
        }
        else{ 
            $mail_sent = 'Email sending failed.'; 
        }



    // Thanking Subscriber email
    $to2 = $subscribe_email; 
$from2 = 'alert@veteranlogisticsgroup.com';
    $fromName2 = 'Alert'; 
    
    $subject2 = 'Thank You For Subsribing'; 
    
    $htmlContent2 = ' 
        <html> 
        <head> 
            <title>Thank You For Subsribing | Levend Shipping Inc.</title> 
        </head> 
        <body style="border: 2px dashed #230c54; padding-left: 5px; padding-right: 5px;"> 
            <h1>Thank You For Subsribing to our Newsletter service!</h1>
        </body> 
        </html>'; 
    
        // Set content-type header for sending HTML email 
        $headers2 = "MIME-Version: 1.0" . "\r\n"; 
        $headers2 .= "Content-type:text/html;charset=UTF-8" . "\r\n"; 
        
        // Additional headers 
        $headers2 .= 'From: '.$fromName2.'<'.$from2.'>' . "\r\n"; 
        
        // Send email 
        if(mail($to2, $subject2, $htmlContent2, $headers2)){ 
            $mail_sent = 'Email has sent successfully.';
            $value = 1;
        }
        else{ 
            $mail_sent = 'Email sending failed.'; 
        }

    header("location:?subscription_success=yes");        
}





// Change package location email
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_location']) && !empty($_POST['change_location'])) {
    $tracking_number = cp_get_tracking_number_from_post();
    $new_location = $_POST['new_location'];
    if ($tracking_number !== '') {
        $updatedAt = time();
        $stmt = $dbconn->prepare("UPDATE shipments SET current_location = ?, date_updated = ? WHERE tracking_number = ?");
        $stmt->bind_param("sis", $new_location, $updatedAt, $tracking_number);
        $stmt->execute();
        $stmt->close();
    }
}





// Cancel order
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_order']) && !empty($_POST['cancel_order'])) {
    $tracking_number = cp_get_tracking_number_from_post();
    if ($tracking_number !== '') {
        $updatedAt = time();
        $stmt = $dbconn->prepare("UPDATE shipments SET status = 'cancelled', date_updated = ? WHERE tracking_number = ?");
        $stmt->bind_param("is", $updatedAt, $tracking_number);
        $stmt->execute();
        $stmt->close();
    }
}





// Create Item Function
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create-item-button']) && !empty($_POST['create-item-button'])) {
    // Form data
    $create_item_id = $_POST['create-item-id'];
    $create_item_name = $_POST['create-item-name'];
    $create_item_description = $_POST['create-item-description'];
    $create_image_item_number = $_POST['create_image_item_number'];
   
    // Query
    $stmt = $dbconn->prepare("INSERT INTO items (order_id, item_name, item_description, item_number) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $create_item_id, $create_item_name, $create_item_description, $create_image_item_number);
    $stmt->execute();
    $stmt->close();   
}





// Create order function
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_order']) && !empty($_POST['create_order'])) {
    $name = trim((string)($_POST['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $item_name = trim((string)($_POST['item_name'] ?? ''));
    $item_description = trim((string)($_POST['item_description'] ?? ''));
    $total_price = trim((string)($_POST['total_price'] ?? ''));
    $price_breakdown = trim((string)($_POST['price_breakdown'] ?? ''));
    $origin = trim((string)($_POST['origin'] ?? ''));
    $destination = trim((string)($_POST['destination'] ?? ''));
    $duration = (int)($_POST['duration'] ?? 3);
    $duration = $duration > 0 ? $duration : 3;
    $shipmentType = cp_map_shipment_type((string)($_POST['delivery_type'] ?? 'standard'));

    $now = time();
    $estimatedDelivery = $now + ($duration * 86400);
    $status = 'pending';
    $currentLocation = $origin !== '' ? $origin : 'Origin Facility';
    $completion = 0;
    $length = 1.0;
    $width = 1.0;
    $height = 1.0;
    $weight = 1.0;
    $senderPhone = null;
    $receiverName = 'Receiver';
    $receiverEmail = $email !== '' ? $email : 'receiver@example.com';
    $receiverPhone = null;
    $deliveredAt = null;
    $notes = trim("Item: {$item_name}; Description: {$item_description}; Price: {$total_price}; Breakdown: {$price_breakdown}");

    $userId = null;
    if ($email !== '') {
        $userStmt = $dbconn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        if ($userStmt) {
            $userStmt->bind_param("s", $email);
            $userStmt->execute();
            $userRes = $userStmt->get_result();
            $userRow = $userRes ? $userRes->fetch_assoc() : null;
            if ($userRow && isset($userRow['id'])) {
                $userId = (int)$userRow['id'];
            }
            $userStmt->close();
        }
    }

    $trackingNumber = '1Z' . strtoupper(substr(bin2hex(random_bytes(8)), 0, 16));

    $sql = "INSERT INTO shipments
        (tracking_number, sender_name, sender_email, sender_phone, user_id, receiver_name, receiver_email, receiver_phone, origin_address, destination_address, length, width, height, weight, shipment_type, status, current_location, completion_percentage, estimated_delivery_time, date_created, date_updated, delivered_at, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $dbconn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param(
            "ssssisssssddddsssiiiiis",
            $trackingNumber,
            $name,
            $email,
            $senderPhone,
            $userId,
            $receiverName,
            $receiverEmail,
            $receiverPhone,
            $origin,
            $destination,
            $length,
            $width,
            $height,
            $weight,
            $shipmentType,
            $status,
            $currentLocation,
            $completion,
            $estimatedDelivery,
            $now,
            $now,
            $deliveredAt,
            $notes
        );
        $stmt->execute();
        $stmt->close();
    }
}





// Send Custom Email Function
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_mail']) && !empty($_POST['send_mail'])) {
    $email = $_POST["email"];
    $subject = $_POST["subject"];
    $content = $_POST["content"];


    // Email sending disabled in this project.
}





// Upload Item image function
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_picture']) && !empty($_POST['upload_picture'])) {
   

        $tracking_id = $_POST['tracking_id'];
        $item_number = $_POST['item_number'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        
      
        // File upload handling
        $file_name = $_FILES['image']['name'];
        $file_temp = $_FILES['image']['tmp_name'];
        $file_destination = '../assets/images/items/' . $file_name; // Set your destination path
        move_uploaded_file($file_temp, $file_destination);
        
        // Database insert
        $stmt = $dbconn->prepare("INSERT INTO items (tracking_id, item_number, name, description, image_link) VALUES ( ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisss", $tracking_id, $item_number, $name, $description,  $file_destination);
        $stmt->execute();
    
}





// Create free Quote function
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['free-quote-button']) && !empty($_POST['free-quote-button'])) {// Request Free Quote
    // Collect form data and sanitize
    $free_quote_name = htmlspecialchars(trim($_POST['free-quote-name']));
    $free_quote_email = filter_var($_POST['free-quote-email'], FILTER_SANITIZE_EMAIL);
    $free_quote_number = htmlspecialchars(trim($_POST['free-quote-number']));
    $free_quote_freight_method = htmlspecialchars(trim($_POST['free-quote-freight-method']));
    $free_quote_request = htmlspecialchars(trim($_POST['free-quote-request']));
    $free_quote_request_time = time();

    // Database Connection (Assuming $dbconn is MySQLi)
    $stmt = $dbconn->prepare("INSERT INTO free_quotes_requests (name, email, number, method, request, time) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $free_quote_name, $free_quote_email, $free_quote_number, $free_quote_freight_method, $free_quote_request, $free_quote_request_time);
    $stmt->execute();
    $stmt->close();

    // Email sending disabled in this project.
    header("location:?request-sent=yes");        
}




// Delete shipment function
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_shipment']) && !empty($_POST['delete_shipment'])) {
    $tracking_number = cp_get_tracking_number_from_post();
    if ($tracking_number !== '') {
        $stmt = $dbconn->prepare("DELETE FROM shipments WHERE tracking_number = ?");
        $stmt->bind_param("s", $tracking_number);
        $stmt->execute();
        $stmt->close();
    }
}



// Delete Quote function
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_quote']) && !empty($_POST['delete_quote'])) {
    $id = $_POST['tracking_id'];
    mysqli_query($dbconn, "DELETE FROM quotes WHERE id = $id");
       
}





// Delete item function
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_item']) && !empty($_POST['delete_item'])) {

    $id = $_POST['tracking_id'];
    $item_number = $_POST['item_number'];

    mysqli_query($dbconn, "DELETE FROM items WHERE tracking_id = '$id' AND item_number = $item_number");
}
?>


