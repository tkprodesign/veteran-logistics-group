<?php

require_once("../app.php"); // adjust the path if necessary

$sql = "
CREATE TABLE IF NOT EXISTS developers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) DEFAULT NULL,
    role ENUM(
        'developer',
        'lead_developer',
        'super_developer'
    ) DEFAULT 'developer',
    is_active TINYINT(1) DEFAULT 1,
    last_login BIGINT DEFAULT NULL,
    created_at BIGINT NOT NULL,
    updated_at BIGINT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($dbconn->query($sql) === TRUE) {
    echo "✅ Developers table created successfully.";
} else {
    echo "❌ Error creating table: " . $dbconn->error;
}

$dbconn->close();
?>
