<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Set company timezone
date_default_timezone_set('America/New_York');

require_once __DIR__ . '/../common-sections/globals.php';

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

            /* -------------------------
               SEND VERIFICATION EMAIL VIA RESEND
            -------------------------- */

            $apiKey = 're_AzyocZ26_Lx4bpNbTyHtUFxpikY4mBjjE'; // <-- Replace with your Resend API key

            $verificationHTML = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Veteran Logistics Group Verification Email</title>
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
<p style="font-size:16px;margin:0 0 15px 0;">Hi {$name},</p>
<h1 style="font-size:28px;line-height:1.3;margin:0 0 20px 0;font-weight:500;">Alert.</h1>
<p style="font-size:14px;color:#666666;margin:0 0 30px 0;">Your verification code is:</p>
<div style="font-size:36px;font-weight:bold;letter-spacing:4px;color:#000000;padding:20px;background-color:#ffffff;border:1px solid #eeeeee;display:inline-block;border-radius:4px;">
{$verification_code}
</div>
<p style="font-size:13px;color:#888888;margin:40px 0 0 0;">Do not share this code with anyone.</p>
</td>
</tr>
<tr>
<td align="center" style="background-color:#f9f9f9;padding:30px 40px;border-top:1px solid #eeeeee;">
<p style="font-size:11px;color:#999999;line-height:1.5;margin:0 0 15px 0;">
©2026 Veteran Logistics Group. All rights reserved.
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

            // Prepare payload
            $data = [
                "from" => "noreply@veteranlogisticsgroup.com", // Must be verified in Resend
                "to"   => [$email],
                "subject" => "Veteran Logistics Group Verification Code",
                "html" => $verificationHTML
            ];

            // Send via Resend API
            $ch = curl_init("https://api.resend.com/emails");
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer $apiKey",
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

            $response = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpcode !== 200 && $httpcode !== 201) {
                error_log("Failed to send verification email: " . $response);
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

$conn->close();
?>


