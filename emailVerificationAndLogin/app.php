<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Set company timezone
date_default_timezone_set('America/New_York');

/* -------------------------
   CHECK EMAIL FROM GET
-------------------------- */

if (!isset($_GET['email']) || !filter_var($_GET['email'], FILTER_VALIDATE_EMAIL)) {
    header("Location: /");
    exit();
}

$email = trim($_GET['email']);
$requested_redirect = trim((string)($_GET['redirect'] ?? ''));
$safe_redirect = '';
if ($requested_redirect !== '' && $requested_redirect[0] === '/' && strpos($requested_redirect, '//') !== 0 && preg_match('/[\x00-\x1F\x7F]/', $requested_redirect) !== 1) {
    $safe_redirect = $requested_redirect;
}

require_once __DIR__ . '/../common-sections/globals.php';

$errors = [];

/* -------------------------
   HANDLE VERIFICATION
-------------------------- */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $entered_code = trim($_POST["verification_code"] ?? "");

    if ($entered_code === "") {
        $errors[] = "Verification code is required.";
    }

    if (empty($errors)) {

        // 1. Get the most recent verification code
        $stmt = $conn->prepare(
            "SELECT email, code, date_created 
             FROM verification_code 
             WHERE email = ? 
             ORDER BY date_created DESC 
             LIMIT 1"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $verification = $result->fetch_assoc();
        $stmt->close();

        if (!$verification) {
            $errors[] = "No verification record found.";
        } else {

            $current_time = time();
            $expiry_limit = 15 * 60; // 15 minutes

            // 2. Expiry Check (Epoch comparison)
            if (($current_time - $verification['date_created']) > $expiry_limit) {

                // Generate new verification code
                $new_code = random_int(100000, 999999);
                $new_timestamp = time();

                $ins = $conn->prepare(
                    "INSERT INTO verification_code 
                     (email, code, date_created) 
                     VALUES (?, ?, ?)"
                );
                $ins->bind_param("sii", $email, $new_code, $new_timestamp);
                $ins->execute();
                $ins->close();

                // TODO: Send $new_code to user's email

                $errors[] = "Code expired. A new code has been sent to your email.";

            } elseif (hash_equals((string)$verification['code'], $entered_code)) {

                // 3. Update user as verified
                $update = $conn->prepare(
                    "UPDATE users SET is_verified = 1 WHERE email = ?"
                );
                $update->bind_param("s", $email);
                $update->execute();
                $update->close();

                // 4. Fetch full user details (except password & is_verified)
                $uStmt = $conn->prepare(
                    "SELECT id, name, email, country_code, phone_number, username, created_at 
                     FROM users 
                     WHERE email = ?"
                );
                $uStmt->bind_param("s", $email);
                $uStmt->execute();
                $uResult = $uStmt->get_result();
                $user = $uResult->fetch_assoc();
                $uStmt->close();

                if (!$user) {
                    $errors[] = "User not found.";
                } else {

                    // 5. Start session and store all user variables
                    session_start();
                    $_SESSION['email']        = $user['email'];
                    $_SESSION['name']         = $user['name'];
                    $_SESSION['user_id']      = $user['id'];
                    $_SESSION['username']     = $user['username'];
                    $_SESSION['country_code'] = $user['country_code'];
                    $_SESSION['phone_number'] = $user['phone_number'];
                    $_SESSION['created_at']   = $user['created_at'];

                    // 6. Set 30-day cookie
                    setcookie(
                        "user_email",
                        $user['email'],
                        time() + (86400 * 30),
                        "/",
                        "",
                        true,
                        true
                    );

                    // 7. Cleanup old verification codes
                    $del = $conn->prepare("DELETE FROM verification_code WHERE email = ?");
                    $del->bind_param("s", $email);
                    $del->execute();
                    $del->close();

                    // 8. Log full login into user_logins
                    $stmtLogin = $conn->prepare(
                        "INSERT INTO user_logins (user_id, login_at, ip_address, user_agent) VALUES (?, ?, ?, ?)"
                    );
                    $loginTime = time();
                    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                    $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
                    $stmtLogin->bind_param("iiss", $user['id'], $loginTime, $ip, $agent);
                    $stmtLogin->execute();
                    $stmtLogin->close();

                    // 9. Redirect based on role email
                    $verifiedEmail = strtolower(trim((string)$user['email']));
                    if ($safe_redirect !== '') {
                        header("Location: " . $safe_redirect);
                    } elseif ($verifiedEmail === 'admin@veteranlogisticsgroup.us') {
                        header("Location: /control-panel/");
                    } else {
                        header("Location: /dashboard");
                    }
                    exit();
                }

            } else {
                $errors[] = "Invalid verification code.";
            }
        }
    }
}
$conn->close();
?>

