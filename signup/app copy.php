<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Set company timezone
date_default_timezone_set('America/New_York');

require_once __DIR__ . '/../common-sections/globals.php';

$errors = [];

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

            // TODO: Send verification email here

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

