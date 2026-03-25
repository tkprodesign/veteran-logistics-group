<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Set UPS HQ timezone
date_default_timezone_set('America/New_York');

/* -------------------------
   DATABASE CONNECTION
-------------------------- */
$host = "sql300.byethost18.com";
$user = "b18_41230477";
$pass = "Wateva06@";
$db   = "b18_41230477_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session
session_start();

// Check if 'user_email' cookie exists
if (isset($_COOKIE['user_email']) && !empty($_COOKIE['user_email'])) {
    if (!isset($_SESSION['email'])) {
        $_SESSION['email'] = $_COOKIE['user_email'];
    }
} else {
    header("Location: /login.php");
    exit();
}

/* -------------------------
   FETCH USER DATA
-------------------------- */
$stmt = $conn->prepare(
    "SELECT id, name, email, country_code, phone_number, username, created_at 
     FROM users 
     WHERE email = ?"
);
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user) {
    $user_id         = $user['id'];
    $user_name       = $user['name'];
    $user_email      = $user['email'];
    $user_country    = $user['country_code'];
    $user_phone      = $user['phone_number'];
    $user_username   = $user['username'];
    $user_created_at = $user['created_at'];
} else {
    header("Location: /login.php");
    exit();
}

/* -------------------------
   FETCH LAST LOGIN
-------------------------- */
$stmtLogin = $conn->prepare(
    "SELECT login_at 
     FROM user_logins 
     WHERE user_id = ? 
     ORDER BY login_at DESC 
     LIMIT 1"
);
$stmtLogin->bind_param("i", $user_id);
$stmtLogin->execute();
$loginResult = $stmtLogin->get_result();
$lastLogin = $loginResult->fetch_assoc();
$stmtLogin->close();

$last_login_display = $lastLogin ? date('M j, Y', $lastLogin['login_at']) : "Never";
$joined_display = date('F Y', $user_created_at);
?>