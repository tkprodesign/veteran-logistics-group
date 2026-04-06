<?php
session_start();

$allowedAdminEmails = [
    'tkprodesign96@gmail.com',
    'admin@veteranlogisticsgroup.us'
];

$cookieEmailRaw = '';
if (isset($_COOKIE['user_Email']) && $_COOKIE['user_Email'] !== '') {
    $cookieEmailRaw = (string)$_COOKIE['user_Email'];
} elseif (isset($_COOKIE['user_email']) && $_COOKIE['user_email'] !== '') {
    $cookieEmailRaw = (string)$_COOKIE['user_email'];
}

$cookieEmail = strtolower(trim($cookieEmailRaw));

if ($cookieEmail !== '' && in_array($cookieEmail, $allowedAdminEmails, true)) {
    header('Location: /control-panel/page/');
    exit();
}

header('Location: /control-panel/auth/');
exit();
?>
