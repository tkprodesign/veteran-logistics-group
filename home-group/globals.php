<?php
declare(strict_types=1);

// Central database configuration
$DB_HOST = "sql300.byethost18.com";
$DB_USER = "b18_41230477";
$DB_PASS = "Wateva06@";
$DB_NAME = "b18_41230477_db";

// mysqli object style (used by newer app files)
$conn = $conn ?? new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// mysqli procedural style alias (used by legacy app files)
$dbconn = $dbconn ?? $conn;

