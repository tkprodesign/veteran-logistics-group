<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Set company timezone
date_default_timezone_set('America/New_York');

require_once __DIR__ . '/../common-sections/globals.php';

$signupEmailConfig = [];
$signupEmailConfigPath = __DIR__ . '/../common-sections/email-secrets.php';
if (file_exists($signupEmailConfigPath)) {
    $loadedSignupEmailConfig = include $signupEmailConfigPath;
    if (is_array($loadedSignupEmailConfig)) {
        $signupEmailConfig = $loadedSignupEmailConfig;
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

function signup_resolve_secret(string $name): string {
    global $signupEmailConfig;
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
    if (isset($signupEmailConfig[$name]) && trim((string)$signupEmailConfig[$name]) !== '') {
        return trim((string)$signupEmailConfig[$name]);
    }
    return '';
}

function signup_resolve_mail_setting(string $envName, string $default = ''): string {
    global $signupEmailConfig;
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
    if (isset($signupEmailConfig[$envName]) && trim((string)$signupEmailConfig[$envName]) !== '') {
        return trim((string)$signupEmailConfig[$envName]);
    }
    return $default;
}

function signup_send_verification_email(string $toEmail, string $recipientName, int $verificationCode): bool {
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
        error_log('signup: PHPMailer is not available');
        return false;
    }

    $smtpPassword = signup_resolve_secret('NOREPLY_EMAIL_PASSWORD');
    if ($smtpPassword === '') {
        $smtpPassword = signup_resolve_secret('RESEND_API_KEY');
    }
    if ($smtpPassword === '') {
        $smtpPassword = signup_resolve_secret('SMTP_PASSWORD');
    }
    if ($smtpPassword === '') {
        error_log('signup: missing SMTP password secret for noreply sender');
        return false;
    }

    $smtpHost = signup_resolve_mail_setting('SMTP_HOST', 'smtp.resend.com');
    $smtpPort = (int)signup_resolve_mail_setting('SMTP_PORT', '465');
    $smtpSecure = signup_resolve_mail_setting('SMTP_SECURE', \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS);
    $smtpUsername = signup_resolve_mail_setting('SMTP_USERNAME', 'resend');
    $fromEmail = signup_resolve_mail_setting('NOREPLY_FROM_EMAIL', 'noreply@veteranlogisticsgroup.us');

    $safeName = htmlspecialchars($recipientName !== '' ? $recipientName : 'Customer', ENT_QUOTES, 'UTF-8');
    $safeCode = htmlspecialchars((string)$verificationCode, ENT_QUOTES, 'UTF-8');
    $subject = 'Your Veteran Logistics Group verification code';

    $htmlBody = '<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Email Verification Code</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f3f4f6;padding:24px 0;">
  <tr>
    <td align="center">
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="640" style="max-width:640px;background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
        <tr><td style="background:#0f172a;padding:16px 28px;"><h2 style="margin:0;color:#ffffff;font-size:20px;font-weight:700;">Veteran Logistics Group</h2></td></tr>
        <tr><td style="padding:28px 40px 6px 40px;"><h1 style="margin:0;font-size:26px;line-height:1.3;color:#0f172a;">Verify your email</h1></td></tr>
        <tr><td style="padding:0 40px 14px 40px;"><p style="margin:0;font-size:15px;line-height:1.7;color:#374151;">Hello ' . $safeName . ', use this one-time verification code to continue your signup.</p></td></tr>
        <tr><td style="padding:0 40px 18px 40px;">
          <div style="display:inline-block;padding:16px 24px;border-radius:8px;background:#f8fafc;border:1px solid #e5e7eb;font-size:32px;letter-spacing:6px;font-weight:700;color:#0f172a;">' . $safeCode . '</div>
        </td></tr>
        <tr><td style="padding:0 40px 18px 40px;"><p style="margin:0;font-size:12px;line-height:1.6;color:#6b7280;">For your security, do not share this code. If you did not request this signup, contact support@veteranlogisticsgroup.us.</p></td></tr>
        <tr><td style="background:#f8fafc;border-top:1px solid #e5e7eb;padding:16px 24px;"><p style="margin:0;font-size:11px;line-height:1.5;color:#6b7280;">© 2026 Veteran Logistics Group. This is an automated verification email.</p></td></tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>';

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUsername;
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
        $mail->AltBody = 'Your verification code is ' . $verificationCode . '.';
        return $mail->send();
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        error_log('signup: PHPMailer failed for ' . $toEmail . ' err=' . $e->getMessage());
    }

    return false;
}

$errors = [];
$alreadySignedIn = !empty($_SESSION['user_id']) || !empty($_COOKIE['user_email']) || !empty($_COOKIE['user_Email']);

if ($alreadySignedIn) {
    header("Location: /dashboard/");
    exit();
}

// HANDLE FORM SUBMISSION
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name         = trim($_POST["name"] ?? "");
    $email        = trim($_POST["email"] ?? "");
    $country_code = $_POST["country_code"] ?? null;
    $phone_number = !empty($_POST["phone_number"]) ? trim($_POST["phone_number"]) : null;
    $username     = trim($_POST["username"] ?? "");
    $password     = $_POST["password"] ?? "";
    $terms        = isset($_POST["accept_terms"]);

    /* -------------------------
       VALIDATION
    -------------------------- */

    if ($name === "") {
        $errors[] = "Name is required.";
    }

    if ($email === "") {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if ($username === "") {
        $errors[] = "Username is required.";
    }

    if ($password === "") {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }

    if (!$terms) {
        $errors[] = "You must accept the terms and conditions.";
    }

    if ($phone_number !== null && !preg_match("/^[0-9]+$/", $phone_number)) {
        $errors[] = "Phone number must contain digits only.";
    }

    /* -------------------------
       DUPLICATE CHECK
    -------------------------- */

    if (empty($errors)) {

        // Check email
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "Email already exists.";
        }

        $stmt->close();

        // Check username
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "Username already exists.";
        }

        $stmt->close();
    }

    /* -------------------------
       INSERT USER + VERIFICATION
    -------------------------- */

    if (empty($errors)) {

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $created_at = time(); // epoch timestamp

        $stmt = $conn->prepare(
            "INSERT INTO users 
            (name, email, country_code, phone_number, username, password, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "ssssssi",
            $name,
            $email,
            $country_code,
            $phone_number,
            $username,
            $hashed_password,
            $created_at
        );

        if ($stmt->execute()) {

            // ✅ Generate 6-digit numeric code
            $verification_code = random_int(100000, 999999);

            // ✅ Epoch timestamp
            $date_created = time();

            // Insert into verification_code table
            $v_stmt = $conn->prepare(
                "INSERT INTO verification_code 
                (email, code, date_created) 
                VALUES (?, ?, ?)"
            );

            $v_stmt->bind_param(
                "sii",
                $email,
                $verification_code,
                $date_created
            );

            $v_stmt->execute();
            $v_stmt->close();

            $verificationSent = signup_send_verification_email($email, $name, $verification_code);
            if (!$verificationSent) {
                $cleanupVerification = $conn->prepare("DELETE FROM verification_code WHERE email = ?");
                if ($cleanupVerification) {
                    $cleanupVerification->bind_param("s", $email);
                    $cleanupVerification->execute();
                    $cleanupVerification->close();
                }

                $cleanupUser = $conn->prepare("DELETE FROM users WHERE email = ? LIMIT 1");
                if ($cleanupUser) {
                    $cleanupUser->bind_param("s", $email);
                    $cleanupUser->execute();
                    $cleanupUser->close();
                }

                $errors[] = "We could not send your verification email right now. Please try signing up again in a moment.";
                $stmt->close();
                goto signup_app_done;
            }

            // Redirect to verification page
            header("Location: /emailVerificationAndLogin/?email=" . urlencode($email));
            exit();

        } else {
            $errors[] = "Registration failed. Please try again.";
        }

        $stmt->close();
    }
}

signup_app_done:
$conn->close();
?>

