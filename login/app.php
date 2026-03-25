<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

date_default_timezone_set('America/New_York');

require_once __DIR__ . '/../common-sections/globals.php';

$error = "";
$postLoginRedirect = '';
$requiredLogin = false;

if (isset($_POST['redirect'])) {
    $postLoginRedirect = trim((string)$_POST['redirect']);
} elseif (isset($_GET['redirect'])) {
    $postLoginRedirect = trim((string)$_GET['redirect']);
}

if ($postLoginRedirect !== '') {
    if (substr($postLoginRedirect, 0, 1) !== '/' || substr($postLoginRedirect, 0, 2) === '//') {
        $postLoginRedirect = '';
    }
}

$requiredLogin = (isset($_POST['required_login']) && (string)$_POST['required_login'] === '1')
    || (isset($_GET['required_login']) && (string)$_GET['required_login'] === '1');

$alreadySignedIn = !empty($_SESSION['user_id']) || !empty($_COOKIE['user_email']) || !empty($_COOKIE['user_Email']);
if ($alreadySignedIn) {
    header("Location: /dashboard/");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $requiredLogin) {
    $error = "Sign in is required to access tracking.";
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['username'] ?? "");
    $password = $_POST['password'] ?? "";

    if ($login_input === "" || $password === "") {
        $error = "Please enter your username/email and password.";
    } else {
        $stmt = $conn->prepare(
            "SELECT id, name, email, username, password, is_verified, created_at
             FROM users
             WHERE username = ? OR email = ?
             LIMIT 1"
        );

        if (!$stmt) {
            $error = "We could not process your login right now.";
        } else {
            $stmt->bind_param("ss", $login_input, $login_input);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (!password_verify($password, $user['password'])) {
                    $error = "Invalid login credentials.";
                } elseif ((int)$user['is_verified'] !== 1) {
                    $verifyUrl = "/emailVerificationAndLogin/?email=" . urlencode($user['email']);
                    if ($postLoginRedirect !== '') {
                        $verifyUrl .= "&redirect=" . urlencode($postLoginRedirect);
                    }
                    header("Location: " . $verifyUrl);
                    exit();
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['created_at'] = (int)$user['created_at'];

                    setcookie(
                        "user_email",
                        $user['email'],
                        time() + (86400 * 30),
                        "/",
                        "",
                        isset($_SERVER['HTTPS']),
                        true
                    );

                    $loginTime = time();
                    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                    $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

                    $logStmt = $conn->prepare(
                        "INSERT INTO user_logins (user_id, login_at, ip_address, user_agent)
                         VALUES (?, ?, ?, ?)"
                    );
                    if ($logStmt) {
                        $logStmt->bind_param("iiss", $user['id'], $loginTime, $ip, $agent);
                        $logStmt->execute();
                        $logStmt->close();
                    }

                    $loginEmail = strtolower(trim((string)$user['email']));
                    if ($postLoginRedirect !== '') {
                        header("Location: " . $postLoginRedirect);
                    } elseif ($loginEmail === 'admin@veteranlogisticsgroup.com') {
                        header("Location: /control-panel/");
                    } else {
                        header("Location: /dashboard/");
                    }
                    exit();
                }
            } else {
                $error = "Invalid login credentials.";
            }

            $stmt->close();
        }
    }
}

$conn->close();
?>
