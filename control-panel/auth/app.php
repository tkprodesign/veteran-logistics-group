<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
date_default_timezone_set('America/New_York');

require_once __DIR__ . '/../../common-sections/globals.php';

$allowedAdminEmails = [
    'tkprodesign96@gmail.com',
    'admin@veteranlogisticsgroup.us'
];

$error = '';

$cookieEmailRaw = '';
if (isset($_COOKIE['user_Email']) && $_COOKIE['user_Email'] !== '') {
    $cookieEmailRaw = (string)$_COOKIE['user_Email'];
} elseif (isset($_COOKIE['user_email']) && $_COOKIE['user_email'] !== '') {
    $cookieEmailRaw = (string)$_COOKIE['user_email'];
}
$cookieEmail = strtolower(trim($cookieEmailRaw));
if ($cookieEmail !== '' && in_array($cookieEmail, $allowedAdminEmails, true)) {
    $_SESSION['email'] = $cookieEmail;
    header('Location: /control-panel/page/');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginInput = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($loginInput === '' || $password === '') {
        $error = 'Please enter your username/email and password.';
    } else {
        $stmt = $conn->prepare(
            "SELECT id, email, username, password
             FROM users
             WHERE username = ? OR email = ?
             LIMIT 1"
        );
        $stmt->bind_param("ss", $loginInput, $loginInput);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if (!$user || !password_verify($password, (string)$user['password'])) {
            $error = 'Invalid login credentials.';
        } else {
            $adminEmail = strtolower(trim((string)$user['email']));
            if (!in_array($adminEmail, $allowedAdminEmails, true)) {
                $error = 'This account does not have control panel access.';
            } else {
                session_regenerate_id(true);
                $_SESSION['email'] = $adminEmail;
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['username'] = (string)$user['username'];

                $expiry = time() + (86400 * 30);
                $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
                setcookie('user_Email', $adminEmail, $expiry, '/', '', $secure, true);
                setcookie('user_email', $adminEmail, $expiry, '/', '', $secure, true);

                header('Location: /control-panel/page/');
                exit();
            }
        }
    }
}
?>
