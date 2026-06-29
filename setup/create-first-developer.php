<?php

require_once("../app.php");

$name = "Flight";
$username = "flight";
$email = "dev@veteranlogisticsgroup.us";
$password = "treamtime26";   // Change this
$role = "super_developer";

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$createdAt = time();

$stmt = $dbconn->prepare("
    INSERT INTO developers
    (
        username,
        email,
        password_hash,
        full_name,
        role,
        created_at
    )
    VALUES
    (?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "sssssi",
    $username,
    $email,
    $passwordHash,
    $name,
    $role,
    $createdAt
);

if ($stmt->execute()) {
    echo "✅ Developer account created successfully.";
} else {
    echo "❌ Error: " . $stmt->error;
}

$stmt->close();
$dbconn->close();
