<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

require_once __DIR__ . '/common-sections/globals.php';

if (!isset($conn) || !($conn instanceof mysqli) || !empty($conn->connect_error)) {
    http_response_code(500);
    echo "Database connection is not available.\n";
    exit(1);
}

$dbName = $conn->query('SELECT DATABASE() AS db_name');
$dbRow = $dbName ? $dbName->fetch_assoc() : null;
$databaseName = (string)($dbRow['db_name'] ?? '');

if ($databaseName === '') {
    http_response_code(500);
    echo "Could not determine active database.\n";
    exit(1);
}

$messages = [];
$errors = [];

function setup_log(array &$messages, string $message): void {
    $messages[] = $message;
}

function setup_error(array &$errors, string $message): void {
    $errors[] = $message;
}

function table_exists(mysqli $conn, string $databaseName, string $tableName): bool {
    $stmt = $conn->prepare(
        "SELECT 1
         FROM information_schema.tables
         WHERE table_schema = ?
           AND table_name = ?
         LIMIT 1"
    );
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('ss', $databaseName, $tableName);
    $stmt->execute();
    $res = $stmt->get_result();
    $exists = (bool)($res && $res->fetch_row());
    $stmt->close();
    return $exists;
}

function column_exists(mysqli $conn, string $databaseName, string $tableName, string $columnName): bool {
    $stmt = $conn->prepare(
        "SELECT 1
         FROM information_schema.columns
         WHERE table_schema = ?
           AND table_name = ?
           AND column_name = ?
         LIMIT 1"
    );
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('sss', $databaseName, $tableName, $columnName);
    $stmt->execute();
    $res = $stmt->get_result();
    $exists = (bool)($res && $res->fetch_row());
    $stmt->close();
    return $exists;
}

function index_exists(mysqli $conn, string $databaseName, string $tableName, string $indexName): bool {
    $stmt = $conn->prepare(
        "SELECT 1
         FROM information_schema.statistics
         WHERE table_schema = ?
           AND table_name = ?
           AND index_name = ?
         LIMIT 1"
    );
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('sss', $databaseName, $tableName, $indexName);
    $stmt->execute();
    $res = $stmt->get_result();
    $exists = (bool)($res && $res->fetch_row());
    $stmt->close();
    return $exists;
}

function ensure_table(mysqli $conn, array &$messages, array &$errors, string $tableName, string $sql): void {
    if ($conn->query($sql) === true) {
        setup_log($messages, "Ensured table `{$tableName}` exists.");
        return;
    }
    setup_error($errors, "Failed ensuring table `{$tableName}`: " . $conn->error);
}

function ensure_column(mysqli $conn, string $databaseName, array &$messages, array &$errors, string $tableName, string $columnName, string $definition): void {
    if (column_exists($conn, $databaseName, $tableName, $columnName)) {
        setup_log($messages, "Column `{$tableName}`.`{$columnName}` already exists.");
        return;
    }

    $sql = "ALTER TABLE `{$tableName}` ADD COLUMN `{$columnName}` {$definition}";
    if ($conn->query($sql) === true) {
        setup_log($messages, "Added column `{$tableName}`.`{$columnName}`.");
        return;
    }
    setup_error($errors, "Failed adding column `{$tableName}`.`{$columnName}`: " . $conn->error);
}

function ensure_index(mysqli $conn, string $databaseName, array &$messages, array &$errors, string $tableName, string $indexName, string $sql): void {
    if (index_exists($conn, $databaseName, $tableName, $indexName)) {
        setup_log($messages, "Index `{$indexName}` on `{$tableName}` already exists.");
        return;
    }

    if ($conn->query($sql) === true) {
        setup_log($messages, "Added index `{$indexName}` on `{$tableName}`.");
        return;
    }
    setup_error($errors, "Failed adding index `{$indexName}` on `{$tableName}`: " . $conn->error);
}

$tableSql = [
    'users' => "
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(190) NOT NULL,
            `email` VARCHAR(190) NOT NULL,
            `country_code` VARCHAR(16) DEFAULT NULL,
            `phone_number` VARCHAR(60) DEFAULT NULL,
            `username` VARCHAR(120) NOT NULL,
            `password` VARCHAR(255) NOT NULL,
            `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` INT UNSIGNED NOT NULL,
            `pay_block` VARCHAR(20) DEFAULT NULL,
            `pay_block_tittle` VARCHAR(255) DEFAULT NULL,
            `pay_block_message` TEXT DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_users_email` (`email`),
            UNIQUE KEY `uniq_users_username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ",
    'verification_code' => "
        CREATE TABLE IF NOT EXISTS `verification_code` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `email` VARCHAR(190) NOT NULL,
            `code` INT NOT NULL,
            `date_created` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_verification_email_created` (`email`, `date_created`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ",
    'user_logins' => "
        CREATE TABLE IF NOT EXISTS `user_logins` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT UNSIGNED NOT NULL,
            `login_at` INT UNSIGNED NOT NULL,
            `ip_address` VARCHAR(64) DEFAULT NULL,
            `user_agent` TEXT DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_user_logins_user_time` (`user_id`, `login_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ",
    'quotes' => "
        CREATE TABLE IF NOT EXISTS `quotes` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(190) NOT NULL,
            `address` VARCHAR(255) DEFAULT NULL,
            `phone_number` VARCHAR(60) DEFAULT NULL,
            `item_name` VARCHAR(190) DEFAULT NULL,
            `origin` VARCHAR(190) DEFAULT NULL,
            `destination` VARCHAR(190) DEFAULT NULL,
            `receivers_name` VARCHAR(190) DEFAULT NULL,
            `receivers_number` VARCHAR(60) DEFAULT NULL,
            `receivers_email` VARCHAR(190) DEFAULT NULL,
            `receivers_address` VARCHAR(255) DEFAULT NULL,
            `postal_code` VARCHAR(40) DEFAULT NULL,
            `method` VARCHAR(80) DEFAULT NULL,
            `free_quote_request` TEXT DEFAULT NULL,
            `time` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_quotes_time` (`time`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ",
    'free_quotes_requests' => "
        CREATE TABLE IF NOT EXISTS `free_quotes_requests` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(190) NOT NULL,
            `email` VARCHAR(190) DEFAULT NULL,
            `number` VARCHAR(60) DEFAULT NULL,
            `method` VARCHAR(80) DEFAULT NULL,
            `request` TEXT DEFAULT NULL,
            `time` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_free_quotes_time` (`time`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ",
    'shipments' => "
        CREATE TABLE IF NOT EXISTS `shipments` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `tracking_number` VARCHAR(80) NOT NULL,
            `sender_name` VARCHAR(190) NOT NULL,
            `sender_email` VARCHAR(190) DEFAULT NULL,
            `sender_phone` VARCHAR(60) DEFAULT NULL,
            `user_id` INT UNSIGNED DEFAULT NULL,
            `receiver_name` VARCHAR(190) NOT NULL,
            `receiver_email` VARCHAR(190) DEFAULT NULL,
            `receiver_phone` VARCHAR(60) DEFAULT NULL,
            `origin_address` VARCHAR(255) NOT NULL,
            `destination_address` VARCHAR(255) NOT NULL,
            `length` DECIMAL(10,2) DEFAULT NULL,
            `width` DECIMAL(10,2) DEFAULT NULL,
            `height` DECIMAL(10,2) DEFAULT NULL,
            `weight` DECIMAL(10,2) DEFAULT NULL,
            `shipment_type` VARCHAR(60) NOT NULL DEFAULT 'standard',
            `status` VARCHAR(60) NOT NULL DEFAULT 'pending',
            `current_location` VARCHAR(255) DEFAULT NULL,
            `completion_percentage` INT NOT NULL DEFAULT 0,
            `estimated_delivery_time` BIGINT DEFAULT NULL,
            `date_created` BIGINT NOT NULL,
            `date_updated` BIGINT NOT NULL,
            `delivered_at` BIGINT DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_shipments_tracking_number` (`tracking_number`),
            KEY `idx_shipments_user_status` (`user_id`, `status`),
            KEY `idx_shipments_status_created` (`status`, `date_created`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ",
    'shipment_location_events' => "
        CREATE TABLE IF NOT EXISTS `shipment_location_events` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `shipment_id` BIGINT UNSIGNED NOT NULL,
            `tracking_number` VARCHAR(80) NOT NULL,
            `location_label` VARCHAR(40) NOT NULL DEFAULT 'checkpoint',
            `event_severity` VARCHAR(40) NOT NULL DEFAULT 'neutral',
            `is_current` TINYINT(1) DEFAULT NULL,
            `is_origin` TINYINT(1) DEFAULT NULL,
            `is_destination` TINYINT(1) DEFAULT NULL,
            `location_name` VARCHAR(190) NOT NULL,
            `city` VARCHAR(120) DEFAULT NULL,
            `state_region` VARCHAR(120) DEFAULT NULL,
            `country_code` VARCHAR(8) DEFAULT NULL,
            `postal_code` VARCHAR(40) DEFAULT NULL,
            `status_text` VARCHAR(255) NOT NULL,
            `issue_note` TEXT DEFAULT NULL,
            `payment_amount` DECIMAL(10,2) DEFAULT NULL,
            `payment_reason` VARCHAR(255) DEFAULT NULL,
            `negative_event_paid` TINYINT(1) NOT NULL DEFAULT 0,
            `negative_event_paid_at_epoch` BIGINT DEFAULT NULL,
            `event_time_epoch` BIGINT NOT NULL,
            `created_at_epoch` BIGINT NOT NULL,
            `updated_at_epoch` BIGINT NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_sle_tracking_time` (`tracking_number`, `event_time_epoch`),
            KEY `idx_sle_shipment_time` (`shipment_id`, `event_time_epoch`),
            KEY `idx_sle_current` (`tracking_number`, `is_current`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ",
    'shipment_service_quotes' => "
        CREATE TABLE IF NOT EXISTS `shipment_service_quotes` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT NOT NULL,
            `service_level` ENUM('priority','express','economy') NOT NULL,
            `payload_hash` CHAR(64) NOT NULL,
            `payload_json` MEDIUMTEXT NOT NULL,
            `price` DECIMAL(10,2) DEFAULT NULL,
            `duration` INT UNSIGNED DEFAULT NULL,
            `description_text` TEXT DEFAULT NULL,
            `comment_text` TEXT DEFAULT NULL,
            `processing_status` ENUM('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
            `email_sent_epoch` INT UNSIGNED DEFAULT NULL,
            `created_at_epoch` INT UNSIGNED NOT NULL,
            `updated_at_epoch` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_quote_payload_service` (`user_id`, `service_level`, `payload_hash`),
            KEY `idx_quote_user_created` (`user_id`, `created_at_epoch`),
            KEY `idx_quote_status` (`processing_status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ",
    'shipment_payment_proofs' => "
        CREATE TABLE IF NOT EXISTS `shipment_payment_proofs` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT UNSIGNED NOT NULL,
            `name` VARCHAR(190) NOT NULL,
            `email` VARCHAR(190) DEFAULT NULL,
            `file_name` VARCHAR(255) NOT NULL,
            `uploaded_at_epoch` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_payment_proofs_user` (`user_id`),
            KEY `idx_payment_proofs_uploaded` (`uploaded_at_epoch`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ",
    'exception_issue_payments' => "
        CREATE TABLE IF NOT EXISTS `exception_issue_payments` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `event_id` INT UNSIGNED NOT NULL,
            `tracking_number` VARCHAR(80) NOT NULL,
            `user_id` INT UNSIGNED NOT NULL,
            `name` VARCHAR(190) NOT NULL,
            `email` VARCHAR(190) NOT NULL,
            `event_title` VARCHAR(255) NOT NULL,
            `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `payment_for` VARCHAR(255) NOT NULL,
            `payment_method` VARCHAR(20) NOT NULL DEFAULT 'card',
            `crypto_asset` VARCHAR(30) DEFAULT NULL,
            `crypto_wallet_address` VARCHAR(255) DEFAULT NULL,
            `proof_file_name` VARCHAR(255) DEFAULT NULL,
            `status` VARCHAR(40) NOT NULL DEFAULT 'pending',
            `invoice_number` VARCHAR(255) DEFAULT NULL,
            `created_at_epoch` INT NOT NULL,
            `updated_at_epoch` INT NOT NULL,
            `confirmed_at_epoch` INT DEFAULT NULL,
            `confirmed_by` VARCHAR(190) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_exception_payments_event` (`event_id`),
            KEY `idx_exception_payments_tracking` (`tracking_number`),
            KEY `idx_exception_payments_status` (`status`),
            KEY `idx_exception_payments_user` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ",
    'payment_methods' => "
        CREATE TABLE IF NOT EXISTS `payment_methods` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT UNSIGNED NOT NULL,
            `method_type` VARCHAR(20) NOT NULL,
            `card_brand` VARCHAR(60) DEFAULT NULL,
            `card_last4` VARCHAR(4) DEFAULT NULL,
            `exp_month` INT DEFAULT NULL,
            `exp_year` INT DEFAULT NULL,
            `processor_token` VARCHAR(255) DEFAULT NULL,
            `token_source_note` VARCHAR(255) DEFAULT NULL,
            `wallet_network` VARCHAR(60) DEFAULT NULL,
            `wallet_address` VARCHAR(255) DEFAULT NULL,
            `ownership_proof` TEXT DEFAULT NULL,
            `display_label` VARCHAR(255) DEFAULT NULL,
            `is_default` TINYINT(1) NOT NULL DEFAULT 0,
            `record_status` VARCHAR(20) NOT NULL DEFAULT 'active',
            `created_at_epoch` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_payment_methods_user_status` (`user_id`, `record_status`),
            UNIQUE KEY `uniq_payment_methods_processor_token` (`processor_token`),
            UNIQUE KEY `uniq_payment_methods_wallet_address` (`wallet_address`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ",
    'payment_method_events' => "
        CREATE TABLE IF NOT EXISTS `payment_method_events` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `payment_method_id` BIGINT UNSIGNED DEFAULT NULL,
            `user_id` INT UNSIGNED NOT NULL,
            `event_type` VARCHAR(60) NOT NULL,
            `event_message` VARCHAR(255) DEFAULT NULL,
            `ip_address` VARCHAR(64) DEFAULT NULL,
            `user_agent` TEXT DEFAULT NULL,
            `created_at_epoch` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_payment_method_events_user` (`user_id`, `created_at_epoch`),
            KEY `idx_payment_method_events_method` (`payment_method_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ",
    'user_saved_addresses' => "
        CREATE TABLE IF NOT EXISTS `user_saved_addresses` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT UNSIGNED NOT NULL,
            `address_role` VARCHAR(20) NOT NULL,
            `full_name` VARCHAR(190) NOT NULL,
            `contact_name` VARCHAR(190) DEFAULT NULL,
            `email` VARCHAR(190) DEFAULT NULL,
            `phone` VARCHAR(60) DEFAULT NULL,
            `address_line1` VARCHAR(255) NOT NULL,
            `address_line2` VARCHAR(255) DEFAULT NULL,
            `city` VARCHAR(120) DEFAULT NULL,
            `state_region` VARCHAR(120) DEFAULT NULL,
            `postal_code` VARCHAR(40) DEFAULT NULL,
            `country` VARCHAR(120) DEFAULT NULL,
            `country_code` VARCHAR(8) DEFAULT NULL,
            `is_residential` TINYINT(1) NOT NULL DEFAULT 0,
            `date_created` INT NOT NULL,
            `date_updated` INT NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_saved_address` (`user_id`, `address_role`, `address_line1`, `city`, `state_region`, `postal_code`, `country_code`),
            KEY `idx_saved_addresses_user` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ",
    'newsletter_subscribers' => "
        CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `email` VARCHAR(190) NOT NULL,
            `created_at_epoch` INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_newsletter_email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ",
    'items' => "
        CREATE TABLE IF NOT EXISTS `items` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `order_id` BIGINT DEFAULT NULL,
            `tracking_id` VARCHAR(80) DEFAULT NULL,
            `item_number` INT DEFAULT NULL,
            `item_name` VARCHAR(190) DEFAULT NULL,
            `item_description` TEXT DEFAULT NULL,
            `name` VARCHAR(190) DEFAULT NULL,
            `description` TEXT DEFAULT NULL,
            `image_link` VARCHAR(255) DEFAULT NULL,
            `created_at_epoch` INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `idx_items_tracking_item` (`tracking_id`, `item_number`),
            KEY `idx_items_order_id` (`order_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ",
    'promocode' => "
        CREATE TABLE IF NOT EXISTS `promocode` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `code` VARCHAR(80) NOT NULL,
            `discount_type` VARCHAR(20) NOT NULL,
            `discount_value` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `starts_at_epoch` INT UNSIGNED DEFAULT NULL,
            `expires_at_epoch` INT UNSIGNED DEFAULT NULL,
            `usage_limit` INT UNSIGNED DEFAULT NULL,
            `used_count` INT UNSIGNED NOT NULL DEFAULT 0,
            `created_at_epoch` INT UNSIGNED NOT NULL DEFAULT 0,
            `updated_at_epoch` INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_promocode_code` (`code`),
            KEY `idx_promocode_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ",
];

foreach ($tableSql as $tableName => $sql) {
    ensure_table($conn, $messages, $errors, $tableName, $sql);
}

if (table_exists($conn, $databaseName, 'shipment_location_events')) {
    ensure_column($conn, $databaseName, $messages, $errors, 'shipment_location_events', 'payment_amount', "DECIMAL(10,2) NULL DEFAULT NULL");
    ensure_column($conn, $databaseName, $messages, $errors, 'shipment_location_events', 'payment_reason', "VARCHAR(255) NULL DEFAULT NULL");
    ensure_index($conn, $databaseName, $messages, $errors, 'shipment_location_events', 'idx_sle_origin', "ALTER TABLE `shipment_location_events` ADD INDEX `idx_sle_origin` (`shipment_id`, `is_origin`)");
    ensure_index($conn, $databaseName, $messages, $errors, 'shipment_location_events', 'idx_sle_destination', "ALTER TABLE `shipment_location_events` ADD INDEX `idx_sle_destination` (`shipment_id`, `is_destination`)");
}

if (table_exists($conn, $databaseName, 'users')) {
    ensure_column($conn, $databaseName, $messages, $errors, 'users', 'is_verified', "TINYINT(1) NOT NULL DEFAULT 0");
    ensure_column($conn, $databaseName, $messages, $errors, 'users', 'pay_block', "VARCHAR(20) NULL DEFAULT NULL");
    ensure_column($conn, $databaseName, $messages, $errors, 'users', 'pay_block_tittle', "VARCHAR(255) NULL DEFAULT NULL");
    ensure_column($conn, $databaseName, $messages, $errors, 'users', 'pay_block_message', "TEXT NULL DEFAULT NULL");
}

if (table_exists($conn, $databaseName, 'newsletter_subscribers')) {
    ensure_column($conn, $databaseName, $messages, $errors, 'newsletter_subscribers', 'created_at_epoch', "INT UNSIGNED NOT NULL DEFAULT 0");
}

$isCli = (PHP_SAPI === 'cli');
if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
}

echo "Database setup for {$databaseName}\n";
echo str_repeat('=', 48) . "\n";

foreach ($messages as $message) {
    echo "[OK] {$message}\n";
}

if ($errors) {
    echo "\nErrors\n";
    echo str_repeat('-', 48) . "\n";
    foreach ($errors as $error) {
        echo "[ERROR] {$error}\n";
    }
    exit(1);
}

echo "\nSetup completed successfully.\n";
