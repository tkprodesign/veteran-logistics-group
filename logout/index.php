<?php
session_start();

$currentRequestPath = (string)($_SERVER['REQUEST_URI'] ?? '/logout/');
$referrer = (string)($_SERVER['HTTP_REFERER'] ?? '');
$next = strtolower(trim((string)($_GET['next'] ?? '')));

$redirectTarget = '/';

if ($next === 'login') {
    $redirectTarget = '/login/';
} elseif ($next === 'home') {
    $redirectTarget = '/';
} else {
    $referrerPath = (string)(parse_url($referrer, PHP_URL_PATH) ?? '');
    if ($referrerPath !== '' && strpos($referrerPath, '/dashboard') === 0) {
        $redirectTarget = '/login/';
    }
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
}

$secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
setcookie('user_email', '', time() - 3600, '/', '', $secure, true);
setcookie('user_Email', '', time() - 3600, '/', '', $secure, true);

session_destroy();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Location: ' . $redirectTarget);
exit();
?>
