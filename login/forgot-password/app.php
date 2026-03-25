<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Set UPS HQ timezone
date_default_timezone_set('America/New_York');

require_once __DIR__ . '/../../common-sections/globals.php';

$error = "";
$success = "";

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['email'] ?? "");

    if ($login_input === "") {
        $error = "Please enter your email or username.";
    } else {
        // Check in database if username/email exists
        $stmt = $conn->prepare(
            "SELECT id, email, username 
             FROM users 
             WHERE email = ? OR username = ? 
             LIMIT 1"
        );
        $stmt->bind_param("ss", $login_input, $login_input);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = "No account found with that email or username.";
        } else {
            $user = $result->fetch_assoc();
            // Here you would normally send the email
            $success = "An email has been sent to <strong>" . htmlspecialchars($user['email']) . "</strong> with instructions to reset your password.";
        }
        $stmt->close();
    }
}
?>

