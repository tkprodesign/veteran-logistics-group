<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Set company timezone
date_default_timezone_set('America/New_York');

require_once __DIR__ . '/../common-sections/globals.php';

// Load email secrets
$signupEmailConfig = [];
$signupEmailConfigPath = __DIR__ . '/../common-sections/email-secrets.php';
if (file_exists($signupEmailConfigPath)) {
    $loadedSignupEmailConfig = include $signupEmailConfigPath;
    if (is_array($loadedSignupEmailConfig)) {
        $signupEmailConfig = $loadedSignupEmailConfig;
    }
}

/* -------------------------
   TURNSTILE VERIFY
-------------------------- */
function signup_verify_turnstile(string $token, string $remoteIp): bool {
    $secretKey = '0x4AAAAAACwnvIudy3lvL60Re4JVpWPk5Ks';

    if ($token === '') return false;

    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'secret' => $secretKey,
        'response' => $token,
        'remoteip' => $remoteIp,
    ]));

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode < 200 || $httpCode >= 300) {
        return false;
    }

    $decoded = json_decode($response, true);
    return is_array($decoded) && !empty($decoded['success']);
}

/* -------------------------
   SECRET RESOLVER
-------------------------- */
function signup_resolve_secret(string $name): string {
    global $signupEmailConfig;

    if ($name === '') return '';

    if (isset($signupEmailConfig[$name]) && trim($signupEmailConfig[$name]) !== '') {
        return trim($signupEmailConfig[$name]);
    }

    return '';
}

/* -------------------------
   SEND EMAIL (RESEND API)
-------------------------- */
function signup_send_verification_email(string $toEmail, string $recipientName, int $verificationCode): bool {

    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $apiKey = signup_resolve_secret('RESEND_API_KEY');
    if ($apiKey === '') {
        error_log('signup: missing RESEND_API_KEY');
        return false;
    }

    $fromEmail = signup_resolve_secret('NOREPLY_FROM_EMAIL');
    if ($fromEmail === '') {
        $fromEmail = 'noreply@veteranlogisticsgroup.us';
    }

    $safeName = htmlspecialchars($recipientName ?: 'Customer', ENT_QUOTES, 'UTF-8');
    $safeCode = htmlspecialchars((string)$verificationCode, ENT_QUOTES, 'UTF-8');

    $currentYear = date('Y');
    $html = '
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Signup Verification Code</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;">Your Veteran Logistics Group verification code is inside.</div>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f3f4f6;padding:24px 0;">
<tr>
<td align="center">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="640" style="max-width:640px;background-color:#ffffff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
<tr>
<td style="background-color:#0f172a;padding:16px 28px;">
<a href="https://veteranlogisticsgroup.us/" target="_blank" rel="noopener" style="text-decoration:none;display:inline-block;">
<img src="https://veteranlogisticsgroup.us/assets/images/branding/logo-horizontal-dark.png" alt="Veteran Logistics Group" width="220" style="display:block;border:0;max-width:220px;height:auto;">
</a>
</td>
</tr>
<tr><td style="padding:28px 40px 6px 40px;"><h1 style="margin:0;font-size:26px;line-height:1.3;color:#0f172a;">Verify your account</h1></td></tr>
<tr><td style="padding:0 40px 12px 40px;"><p style="margin:0;font-size:15px;line-height:1.7;color:#374151;">Hi ' . $safeName . ', thanks for signing up. Use the verification code below to activate your account.</p></td></tr>
<tr><td style="padding:6px 40px 20px 40px;"><div style="display:inline-block;border:1px solid #d1d5db;border-radius:8px;padding:14px 18px;background-color:#f9fafb;font-size:32px;letter-spacing:6px;font-weight:bold;color:#111827;">' . $safeCode . '</div><p style="margin:12px 0 0 0;font-size:13px;color:#6b7280;">This code expires in 15 minutes.</p></td></tr>
<tr><td style="padding:0 40px 18px 40px;"><p style="margin:0;font-size:12px;line-height:1.6;color:#6b7280;">If you did not expect this message, please contact support at support@veteranlogisticsgroup.us.</p></td></tr>
<tr><td style="background-color:#f8fafc;border-top:1px solid #e5e7eb;padding:16px 24px;"><p style="margin:0;font-size:11px;line-height:1.5;color:#6b7280;">© ' . $currentYear . ' Veteran Logistics Group. Please do not reply to this email.</p></td></tr>
</table>
</td>
</tr>
</table>
</body>
</html>';

    $data = [
        "from" => "Veteran Logistics Group <{$fromEmail}>",
        "to" => [$toEmail],
        "subject" => "Your verification code",
        "html" => $html
    ];

    $ch = curl_init("https://api.resend.com/emails");

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$apiKey}",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($response === false || $httpCode < 200 || $httpCode >= 300) {
        error_log("Resend failed: HTTP {$httpCode} | {$response}");
        return false;
    }

    return true;
}

/* -------------------------
   MAIN LOGIC
-------------------------- */

$errors = [];

$alreadySignedIn = !empty($_SESSION['user_id']) || !empty($_COOKIE['user_email']);
if ($alreadySignedIn) {
    header("Location: /dashboard/");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name         = trim($_POST["name"] ?? "");
    $email        = trim($_POST["email"] ?? "");
    $country_code = $_POST["country_code"] ?? null;
    $phone_number = !empty($_POST["phone_number"]) ? trim($_POST["phone_number"]) : null;
    $username     = trim($_POST["username"] ?? "");
    $password     = $_POST["password"] ?? "";
    $terms        = isset($_POST["accept_terms"]);
    $turnstileToken = trim($_POST["cf-turnstile-response"] ?? "");
    $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';

    // VALIDATION
    if ($name === "") $errors[] = "Name is required.";
    if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if ($username === "") $errors[] = "Username is required.";
    if ($password === "" || strlen($password) < 8) $errors[] = "Password must be at least 8 characters.";
    if (!$terms) $errors[] = "You must accept the terms.";
    if ($phone_number !== null && !preg_match("/^[0-9]+$/", $phone_number)) $errors[] = "Phone must be digits only.";

    if (!signup_verify_turnstile($turnstileToken, $remoteIp)) {
        $errors[] = "Turnstile verification failed.";
    }

    // DUPLICATE CHECK
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) $errors[] = "Email exists.";
        $stmt->close();

        $stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) $errors[] = "Username exists.";
        $stmt->close();
    }

    // INSERT
    if (empty($errors)) {

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $created = time();

        $stmt = $conn->prepare("INSERT INTO users (name,email,country_code,phone_number,username,password,created_at) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssi", $name,$email,$country_code,$phone_number,$username,$hashed,$created);

        if ($stmt->execute()) {

            $code = random_int(100000, 999999);
            $now = time();

            $v = $conn->prepare("INSERT INTO verification_code (email,code,date_created) VALUES (?,?,?)");
            $v->bind_param("sii",$email,$code,$now);
            $v->execute();
            $v->close();

            if (!signup_send_verification_email($email,$name,$code)) {
                $conn->query("DELETE FROM users WHERE email='{$email}'");
                $conn->query("DELETE FROM verification_code WHERE email='{$email}'");
                $errors[] = "Email failed to send.";
            } else {
                header("Location: /emailVerificationAndLogin/?email=" . urlencode($email));
                exit();
            }

        } else {
            $errors[] = "Registration failed.";
        }

        $stmt->close();
    }
}

$conn->close();
?>
