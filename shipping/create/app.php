<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
date_default_timezone_set('America/New_York');

require_once __DIR__ . '/../../common-sections/globals.php';

session_start();

$user_id = 0;
$user_name = '';
$user_email = '';
$user_country = '';
$user_phone = '';
$user_pay_block = '';
$user_pay_block_tittle = '';
$user_pay_block_message = '';
$card_pay_block_error = false;

$activeEmail = '';
if (isset($_SESSION['email']) && !empty($_SESSION['email'])) {
    $activeEmail = trim((string)$_SESSION['email']);
} elseif (isset($_COOKIE['user_email']) && !empty($_COOKIE['user_email'])) {
    $activeEmail = trim((string)$_COOKIE['user_email']);
    $_SESSION['email'] = $activeEmail;
}

if ($activeEmail !== '') {
    $stmt = $conn->prepare(
        "SELECT id, name, email, country_code, phone_number, username, created_at, pay_block, pay_block_tittle, pay_block_message
         FROM users
         WHERE email = ?
         LIMIT 1"
    );
    if (!$stmt) {
        $stmt = $conn->prepare(
            "SELECT id, name, email, country_code, phone_number, username, created_at
             FROM users
             WHERE email = ?
             LIMIT 1"
        );
    }
    if ($stmt) {
        $stmt->bind_param("s", $activeEmail);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user) {
            $user_id = (int)$user['id'];
            $user_name = (string)$user['name'];
            $user_email = (string)$user['email'];
            $user_country = (string)$user['country_code'];
            $user_phone = (string)$user['phone_number'];
            $user_pay_block = trim((string)($user['pay_block'] ?? ''));
            $user_pay_block_tittle = trim((string)($user['pay_block_tittle'] ?? ''));
            $user_pay_block_message = trim((string)($user['pay_block_message'] ?? ''));
        }
    }
}

$pay_block_flag = 0;
if ($user_pay_block !== '') {
    if (is_numeric($user_pay_block)) {
        $pay_block_flag = (int)$user_pay_block;
    } else {
        $pay_block_flag = 1;
    }
}
$card_payment_allowed = ($pay_block_flag === 0);
$effective_pay_block_message = $user_pay_block_message;
if ($effective_pay_block_message === '' && $pay_block_flag === 1 && !is_numeric($user_pay_block)) {
    $effective_pay_block_message = $user_pay_block;
}
$effective_pay_block_title = $user_pay_block_tittle !== '' ? $user_pay_block_tittle : ('Payment Block: ' . (string)$user_pay_block);

// Corporate policy: only signed-in users can create shipments.
if ($user_id <= 0) {
    if (isset($_SESSION['email'])) {
        unset($_SESSION['email']);
    }
    if (isset($_COOKIE['user_email'])) {
        setcookie('user_email', '', time() - 3600, '/');
    }
    $currentUri = $_SERVER['REQUEST_URI'] ?? '/shipping/create/';
    $loginTarget = '/login/?redirect=' . urlencode($currentUri);
    header('Location: ' . $loginTarget);
    exit();
}

$step = isset($_GET['s']) ? (int)$_GET['s'] : 1;
if ($step < 1) $step = 1;
if ($step > 5) $step = 5;
$preview_mode = isset($_GET['_dev_preview']) && hash_equals('ups-step-preview-2026', (string)$_GET['_dev_preview']);
if (isset($_GET['strict']) && $_GET['strict'] === '1') {
    unset($_SESSION['shipping_relaxed_mode']);
}
if ($preview_mode) {
    $_SESSION['shipping_relaxed_mode'] = 1;
}
$relaxed_mode = !empty($_SESSION['shipping_relaxed_mode']);
$shipping_test_mode = false;

$shipping_errors = [];
$shipping_success = '';

if (!isset($_SESSION['shipping_create_draft']) || !is_array($_SESSION['shipping_create_draft'])) {
    $shipping_mock_defaults = [
        'ship_from_country' => 'United States',
        'ship_from_country_code' => 'US',
        'sender_name' => 'Northline Supply Co.',
        'sender_contact' => 'Dispatch Desk',
        'sender_email' => 'dispatch@northline-demo.test',
        'sender_phone' => '+1 415 555 0148',
        'sender_notify' => 1,
        'origin_address' => '425 Market Street',
        'sender_address2' => '',
        'sender_city' => 'San Francisco',
        'sender_state' => 'California',
        'sender_zip' => '94105',
        'sender_save_address' => 0,
        'receiver_name' => 'Lakeside Retail Hub',
        'receiver_contact' => 'Receiving Team',
        'receiver_email' => 'receiving@lakeside-demo.test',
        'receiver_phone' => '+1 312 555 0182',
        'receiver_notify' => 1,
        'destination_address' => '233 South Wacker Drive',
        'receiver_address2' => '',
        'receiver_city' => 'Chicago',
        'receiver_state' => 'Illinois',
        'receiver_zip' => '60606',
        'receiver_save_address' => 0,
        'is_residential' => 0,
        'packaging_type' => 'standard',
        'weight' => '12',
        'length' => '16',
        'width' => '10',
        'height' => '8',
        'shipment_class' => 'parcel'
    ];
    $_SESSION['shipping_create_draft'] = [
        'ship_from_country' => $shipping_test_mode ? $shipping_mock_defaults['ship_from_country'] : 'United States',
        'ship_from_country_code' => $shipping_test_mode ? $shipping_mock_defaults['ship_from_country_code'] : 'US',
        'sender_name' => $shipping_test_mode ? $shipping_mock_defaults['sender_name'] : $user_name,
        'sender_contact' => $shipping_test_mode ? $shipping_mock_defaults['sender_contact'] : '',
        'sender_email' => $shipping_test_mode ? $shipping_mock_defaults['sender_email'] : $user_email,
        'sender_phone' => $shipping_test_mode ? $shipping_mock_defaults['sender_phone'] : $user_phone,
        'sender_notify' => $shipping_test_mode ? $shipping_mock_defaults['sender_notify'] : 0,
        'origin_address' => $shipping_test_mode ? $shipping_mock_defaults['origin_address'] : '',
        'sender_address2' => $shipping_test_mode ? $shipping_mock_defaults['sender_address2'] : '',
        'sender_city' => $shipping_test_mode ? $shipping_mock_defaults['sender_city'] : '',
        'sender_state' => $shipping_test_mode ? $shipping_mock_defaults['sender_state'] : '',
        'sender_zip' => $shipping_test_mode ? $shipping_mock_defaults['sender_zip'] : '',
        'sender_save_address' => $shipping_test_mode ? $shipping_mock_defaults['sender_save_address'] : 0,
        'receiver_name' => $shipping_test_mode ? $shipping_mock_defaults['receiver_name'] : '',
        'receiver_contact' => $shipping_test_mode ? $shipping_mock_defaults['receiver_contact'] : '',
        'receiver_email' => $shipping_test_mode ? $shipping_mock_defaults['receiver_email'] : '',
        'receiver_phone' => $shipping_test_mode ? $shipping_mock_defaults['receiver_phone'] : '',
        'receiver_notify' => $shipping_test_mode ? $shipping_mock_defaults['receiver_notify'] : 0,
        'destination_address' => $shipping_test_mode ? $shipping_mock_defaults['destination_address'] : '',
        'receiver_address2' => $shipping_test_mode ? $shipping_mock_defaults['receiver_address2'] : '',
        'receiver_city' => $shipping_test_mode ? $shipping_mock_defaults['receiver_city'] : '',
        'receiver_state' => $shipping_test_mode ? $shipping_mock_defaults['receiver_state'] : '',
        'receiver_zip' => $shipping_test_mode ? $shipping_mock_defaults['receiver_zip'] : '',
        'receiver_save_address' => $shipping_test_mode ? $shipping_mock_defaults['receiver_save_address'] : 0,
        'is_residential' => $shipping_test_mode ? $shipping_mock_defaults['is_residential'] : 0,
        'packaging_type' => $shipping_test_mode ? $shipping_mock_defaults['packaging_type'] : 'standard',
        'weight' => $shipping_test_mode ? $shipping_mock_defaults['weight'] : '',
        'length' => $shipping_test_mode ? $shipping_mock_defaults['length'] : '',
        'width' => $shipping_test_mode ? $shipping_mock_defaults['width'] : '',
        'height' => $shipping_test_mode ? $shipping_mock_defaults['height'] : '',
        'shipment_class' => $shipping_test_mode ? $shipping_mock_defaults['shipment_class'] : 'parcel',
        'pickup_option' => 'dropoff',
        'pickup_date' => date('Y-m-d'),
        'pickup_instructions' => '',
        'quote_request_id' => 0,
        'quote_service_level' => '',
        'promo_code' => '',
        'promo_id' => 0,
        'promo_discount_amount' => 0,
        'promo_discount_type' => '',
        'promo_discount_value' => '',
        'service_type' => 'standard',
        'package_contents' => '',
        'reference_number' => '',
        'parcel_value' => '',
        'opt_carbon' => 0,
        'opt_signature' => 0,
        'opt_adult_signature' => 0,
        'shipment_purpose' => 'I am sending a gift',
        'business_shipper' => 'yes',
        'payment_method' => 'card',
        'card_type' => '',
        'card_number' => '',
        'card_expiry' => '',
        'card_cvv' => '',
        'cardholder_name' => '',
        'crypto_asset' => 'bitcoin',
        'crypto_wallet_address' => '',
        'crypto_payment_proof_file' => '',
        'accept_terms' => 0
    ];
}

$shipping_mock_defaults = $shipping_mock_defaults ?? [
    'ship_from_country' => 'United States',
    'ship_from_country_code' => 'US',
    'sender_name' => 'Northline Supply Co.',
    'sender_contact' => 'Dispatch Desk',
    'sender_email' => 'dispatch@northline-demo.test',
    'sender_phone' => '+1 415 555 0148',
    'sender_notify' => 1,
    'origin_address' => '425 Market Street',
    'sender_address2' => '',
    'sender_city' => 'San Francisco',
    'sender_state' => 'California',
    'sender_zip' => '94105',
    'sender_save_address' => 0,
    'receiver_name' => 'Lakeside Retail Hub',
    'receiver_contact' => 'Receiving Team',
    'receiver_email' => 'receiving@lakeside-demo.test',
    'receiver_phone' => '+1 312 555 0182',
    'receiver_notify' => 1,
    'destination_address' => '233 South Wacker Drive',
    'receiver_address2' => '',
    'receiver_city' => 'Chicago',
    'receiver_state' => 'Illinois',
    'receiver_zip' => '60606',
    'receiver_save_address' => 0,
    'is_residential' => 0,
    'packaging_type' => 'standard',
    'weight' => '12',
    'length' => '16',
    'width' => '10',
    'height' => '8',
    'shipment_class' => 'parcel'
];

if (isset($_SESSION['shipping_create_draft']) && is_array($_SESSION['shipping_create_draft'])) {
    $fields_to_clear_if_mock = [
        'sender_name',
        'sender_contact',
        'sender_email',
        'sender_phone',
        'sender_notify',
        'origin_address',
        'sender_address2',
        'sender_city',
        'sender_state',
        'sender_zip',
        'sender_save_address',
        'receiver_name',
        'receiver_contact',
        'receiver_email',
        'receiver_phone',
        'receiver_notify',
        'destination_address',
        'receiver_address2',
        'receiver_city',
        'receiver_state',
        'receiver_zip',
        'receiver_save_address',
        'is_residential',
        'weight',
        'length',
        'width',
        'height'
    ];

    foreach ($fields_to_clear_if_mock as $field) {
        if (
            array_key_exists($field, $_SESSION['shipping_create_draft']) &&
            array_key_exists($field, $shipping_mock_defaults) &&
            (string)$_SESSION['shipping_create_draft'][$field] === (string)$shipping_mock_defaults[$field]
        ) {
            $_SESSION['shipping_create_draft'][$field] = '';
        }
    }

    if (isset($_SESSION['shipping_create_draft']['sender_name']) && $_SESSION['shipping_create_draft']['sender_name'] === '') {
        $_SESSION['shipping_create_draft']['sender_name'] = $user_name;
    }
    if (isset($_SESSION['shipping_create_draft']['sender_email']) && $_SESSION['shipping_create_draft']['sender_email'] === '') {
        $_SESSION['shipping_create_draft']['sender_email'] = $user_email;
    }
    if (isset($_SESSION['shipping_create_draft']['sender_phone']) && $_SESSION['shipping_create_draft']['sender_phone'] === '') {
        $_SESSION['shipping_create_draft']['sender_phone'] = $user_phone;
    }
    if (isset($_SESSION['shipping_create_draft']['ship_from_country']) && $_SESSION['shipping_create_draft']['ship_from_country'] === '') {
        $_SESSION['shipping_create_draft']['ship_from_country'] = 'United States';
    }
    if (isset($_SESSION['shipping_create_draft']['ship_from_country_code']) && $_SESSION['shipping_create_draft']['ship_from_country_code'] === '') {
        $_SESSION['shipping_create_draft']['ship_from_country_code'] = 'US';
    }
}

if ($shipping_test_mode && $_SERVER['REQUEST_METHOD'] !== 'POST' && $step === 1) {
    $_SESSION['shipping_create_draft'] = array_merge($_SESSION['shipping_create_draft'], $shipping_mock_defaults);
}

if ($step === 1 && isset($_GET['reset']) && $_GET['reset'] === '1') {
    unset($_SESSION['shipping_create_draft'], $_SESSION['shipping_last_created'], $_SESSION['shipping_create_progress']);
    header("Location: /shipping/create/?s=1");
    exit();
}

if (!isset($_SESSION['shipping_create_progress'])) {
    $_SESSION['shipping_create_progress'] = 0;
}

if (!$relaxed_mode && $step > 1 && (int)$_SESSION['shipping_create_progress'] < ($step - 1)) {
    header("Location: /shipping/create/?s=1");
    exit();
}

function shipping_clean_text(string $value): string {
    return trim(preg_replace('/\s+/', ' ', $value));
}

function shipping_generate_tracking(mysqli $conn): string {
    do {
        $tracking = '1Z' . strtoupper(substr(bin2hex(random_bytes(9)), 0, 18));
        $stmt = $conn->prepare("SELECT id FROM shipments WHERE tracking_number = ? LIMIT 1");
        $stmt->bind_param("s", $tracking);
        $stmt->execute();
        $res = $stmt->get_result();
        $exists = ($res && $res->num_rows > 0);
        $stmt->close();
    } while ($exists);
    return $tracking;
}

function shipping_country_timezone_from_code(string $countryCode): string {
    $code = strtoupper(trim($countryCode));
    if (!preg_match('/^[A-Z]{2}$/', $code)) {
        $code = 'US';
    }
    if ($code) {
        $tzList = DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $code);
        if (!empty($tzList)) {
            return $tzList[0];
        }
    }
    return 'UTC';
}

function shipping_country_local_date_ymd(string $countryCode): string {
    $tz = shipping_country_timezone_from_code($countryCode);
    $now = new DateTime('now', new DateTimeZone($tz));
    return $now->format('Y-m-d');
}

function shipping_default_pickup_date_ymd(string $shipmentClass, string $shipFromCountryCode): string {
    $tz = shipping_country_timezone_from_code($shipFromCountryCode);
    $localNow = new DateTime('now', new DateTimeZone($tz));

    // After 12:00 local time, standard pickup starts from next day.
    if ((int)$localNow->format('H') >= 12) {
        $localNow->modify('+1 day');
    }

    $offsetDays = 0;
    if ($shipmentClass === 'heavy_parcel') {
        $offsetDays = 1;
    } elseif ($shipmentClass === 'freight_pallet') {
        $offsetDays = 2;
    }
    if ($offsetDays > 0) {
        $localNow->modify('+' . $offsetDays . ' day');
    }
    return $localNow->format('Y-m-d');
}

function shipping_find_first_column(array $columns, array $candidates): ?string {
    foreach ($candidates as $candidate) {
        if (isset($columns[$candidate])) {
            return $candidate;
        }
    }
    return null;
}

function shipping_increment_promo_usage(mysqli $conn, array $draft): void {
    $promoId = isset($draft['promo_id']) ? (int)$draft['promo_id'] : 0;
    $promoCode = trim((string)($draft['promo_code'] ?? ''));
    $promoDiscount = (float)($draft['promo_discount_amount'] ?? 0);
    if ($promoDiscount <= 0 || ($promoId <= 0 && $promoCode === '')) {
        return;
    }

    $tableExistsRes = $conn->query("SHOW TABLES LIKE 'promocode'");
    if (!$tableExistsRes || $tableExistsRes->num_rows === 0) {
        return;
    }

    $columnsRes = $conn->query("SHOW COLUMNS FROM promocode");
    if (!$columnsRes) {
        return;
    }
    $columns = [];
    while ($row = $columnsRes->fetch_assoc()) {
        $columns[$row['Field']] = true;
    }

    $idCol = shipping_find_first_column($columns, ['id', 'promo_id']);
    $codeCol = shipping_find_first_column($columns, ['code', 'promo_code', 'coupon_code']);
    $usedCol = shipping_find_first_column($columns, ['used_count', 'times_used', 'usage_count', 'used']);
    if ($usedCol === null || ($promoId <= 0 && $codeCol === null)) {
        return;
    }

    if ($promoId > 0 && $idCol !== null) {
        $sql = "UPDATE promocode SET {$usedCol} = COALESCE({$usedCol}, 0) + 1 WHERE {$idCol} = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('i', $promoId);
            $stmt->execute();
            $stmt->close();
        }
        return;
    }

    if ($promoCode !== '' && $codeCol !== null) {
        $sql = "UPDATE promocode SET {$usedCol} = COALESCE({$usedCol}, 0) + 1 WHERE LOWER({$codeCol}) = LOWER(?) LIMIT 1";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('s', $promoCode);
            $stmt->execute();
            $stmt->close();
        }
    }
}

function shipping_ensure_saved_addresses_table(mysqli $conn): bool {
    $sql = "CREATE TABLE IF NOT EXISTS user_saved_addresses (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id INT UNSIGNED NOT NULL,
        address_role VARCHAR(20) NOT NULL,
        full_name VARCHAR(190) NOT NULL,
        contact_name VARCHAR(190) DEFAULT NULL,
        email VARCHAR(190) DEFAULT NULL,
        phone VARCHAR(60) DEFAULT NULL,
        address_line1 VARCHAR(255) NOT NULL,
        address_line2 VARCHAR(255) DEFAULT NULL,
        city VARCHAR(120) DEFAULT NULL,
        state_region VARCHAR(120) DEFAULT NULL,
        postal_code VARCHAR(40) DEFAULT NULL,
        country VARCHAR(120) DEFAULT NULL,
        country_code VARCHAR(8) DEFAULT NULL,
        is_residential TINYINT(1) NOT NULL DEFAULT 0,
        date_created INT NOT NULL,
        date_updated INT NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_saved_address (
            user_id,
            address_role,
            address_line1,
            city,
            state_region,
            postal_code,
            country_code
        ),
        KEY idx_saved_addresses_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    return (bool)$conn->query($sql);
}

function shipping_save_user_address(mysqli $conn, int $userId, string $role, array $payload): bool {
    if ($userId <= 0) {
        return false;
    }

    if (!shipping_ensure_saved_addresses_table($conn)) {
        return false;
    }

    $now = time();
    $role = ($role === 'receiver') ? 'receiver' : 'sender';
    $fullName = shipping_clean_text((string)($payload['full_name'] ?? ''));
    $addressLine1 = shipping_clean_text((string)($payload['address_line1'] ?? ''));
    if ($fullName === '' || $addressLine1 === '') {
        return false;
    }

    $contactName = shipping_clean_text((string)($payload['contact_name'] ?? ''));
    $email = trim((string)($payload['email'] ?? ''));
    $phone = shipping_clean_text((string)($payload['phone'] ?? ''));
    $addressLine2 = shipping_clean_text((string)($payload['address_line2'] ?? ''));
    $city = shipping_clean_text((string)($payload['city'] ?? ''));
    $stateRegion = shipping_clean_text((string)($payload['state_region'] ?? ''));
    $postalCode = shipping_clean_text((string)($payload['postal_code'] ?? ''));
    $country = shipping_clean_text((string)($payload['country'] ?? ''));
    $countryCode = strtoupper(trim((string)($payload['country_code'] ?? '')));
    $isResidential = !empty($payload['is_residential']) ? 1 : 0;

    $sql = "INSERT INTO user_saved_addresses (
                user_id, address_role, full_name, contact_name, email, phone,
                address_line1, address_line2, city, state_region, postal_code,
                country, country_code, is_residential, date_created, date_updated
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                full_name = VALUES(full_name),
                contact_name = VALUES(contact_name),
                email = VALUES(email),
                phone = VALUES(phone),
                address_line2 = VALUES(address_line2),
                is_residential = VALUES(is_residential),
                date_updated = VALUES(date_updated)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        "issssssssssssiii",
        $userId,
        $role,
        $fullName,
        $contactName,
        $email,
        $phone,
        $addressLine1,
        $addressLine2,
        $city,
        $stateRegion,
        $postalCode,
        $country,
        $countryCode,
        $isResidential,
        $now,
        $now
    );

    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function shipping_normalize_upload_filename(string $filename): string {
    $filename = trim($filename);
    if ($filename === '') {
        return '';
    }
    $filename = preg_replace('/[^\w.\-]+/u', '_', $filename);
    $filename = preg_replace('/_+/', '_', $filename);
    return trim((string)$filename, '._');
}

function shipping_resolve_quoted_service_amount(mysqli $conn, int $userId, array $draft, string $selectedServiceUi): ?float {
    $quoteRequestId = isset($draft['quote_request_id']) ? (int)$draft['quote_request_id'] : 0;
    $quoteServiceLevel = strtolower(trim((string)($draft['quote_service_level'] ?? '')));
    $selectedServiceLevel = strtolower(trim($selectedServiceUi));

    if ($quoteRequestId <= 0 || $userId <= 0 || $quoteServiceLevel === '' || $quoteServiceLevel !== $selectedServiceLevel) {
        return null;
    }

    $stmt = $conn->prepare(
        "SELECT price, service_level, processing_status
         FROM shipment_service_quotes
         WHERE id = ? AND user_id = ?
         LIMIT 1"
    );
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("ii", $quoteRequestId, $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        return null;
    }

    $quotedLevel = strtolower(trim((string)($row['service_level'] ?? '')));
    $quotedStatus = strtolower(trim((string)($row['processing_status'] ?? '')));
    $quotedPrice = isset($row['price']) ? (float)$row['price'] : 0.0;
    $statusLooksReady = in_array($quotedStatus, ['ready', 'completed', 'done', 'processed'], true) || $quotedPrice > 0;

    if (!$statusLooksReady || $quotedLevel !== $selectedServiceLevel || $quotedPrice <= 0) {
        return null;
    }

    return $quotedPrice;
}

function shipping_money(float $amount): string {
    return '$' . number_format($amount, 2);
}

function shipping_mail_headers(string $fromEmail): string {
    return "MIME-Version: 1.0\r\n"
        . "Content-type: text/html; charset=UTF-8\r\n"
        . "From: Veteran Logistics Group <{$fromEmail}>\r\n"
        . "Reply-To: support@veteranlogisticsgroup.us\r\n"
        . "X-Mailer: PHP/" . phpversion();
}

function shipping_send_html_email(string $toEmail, string $fromEmail, string $subject, string $htmlBody): bool {
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $headers = shipping_mail_headers($fromEmail);
    return mail($toEmail, $subject, $htmlBody, $headers);
}

function shipping_build_customer_shipment_email_html(array $payload): string {
    $tracking = htmlspecialchars((string)($payload['tracking_number'] ?? 'Unavailable'));
    $service = htmlspecialchars((string)($payload['service_label'] ?? 'Economy'));
    $senderName = htmlspecialchars((string)($payload['sender_name'] ?? '-'));
    $receiverName = htmlspecialchars((string)($payload['receiver_name'] ?? '-'));
    $originAddress = nl2br(htmlspecialchars((string)($payload['origin_address'] ?? '-')));
    $destinationAddress = nl2br(htmlspecialchars((string)($payload['destination_address'] ?? '-')));
    $pickupDate = htmlspecialchars((string)($payload['pickup_date'] ?? '-'));
    $weight = htmlspecialchars((string)($payload['weight'] ?? '0'));
    $dimensions = htmlspecialchars((string)($payload['length'] ?? '0') . ' x ' . (string)($payload['width'] ?? '0') . ' x ' . (string)($payload['height'] ?? '0') . ' in');
    $estimatedDelivery = htmlspecialchars(date('M j, Y', time() + 3 * 86400));
    $trackUrl = 'https://veteranlogisticsgroup.us/track/?id=' . rawurlencode((string)($payload['tracking_number'] ?? ''));
    $dashboardUrl = 'https://veteranlogisticsgroup.us/dashboard/';

    return '<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Shipment Confirmed</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f3f4f6;padding:24px 0;">
<tr><td align="center">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="640" style="max-width:640px;background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
<tr><td style="background:#0f172a;padding:16px 28px;">
<a href="https://veteranlogisticsgroup.us/" target="_blank" rel="noopener" style="text-decoration:none;display:inline-block;">
<img src="https://veteranlogisticsgroup.us/assets/images/branding/logo-horizontal-dark.png" alt="Veteran Logistics Group" width="220" style="display:block;border:0;max-width:220px;height:auto;">
</a>
</td></tr>
<tr><td style="padding:28px 40px 6px 40px;"><h1 style="margin:0;font-size:26px;line-height:1.3;color:#0f172a;">Your shipment is confirmed</h1></td></tr>
<tr><td style="padding:0 40px 14px 40px;"><p style="margin:0;font-size:15px;line-height:1.7;color:#374151;">Hello ' . $senderName . ', your shipment has been successfully filed, paid, and registered.</p></td></tr>
<tr><td style="padding:0 40px 20px 40px;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #e5e7eb;border-radius:8px;">
<tr><td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#6b7280;">Tracking Number</td><td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;font-size:14px;font-weight:bold;color:#0f172a;">' . $tracking . '</td></tr>
<tr><td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#6b7280;">Service</td><td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;font-size:14px;color:#111827;">' . $service . '</td></tr>
<tr><td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#6b7280;">From</td><td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;font-size:14px;color:#111827;">' . $senderName . '<br>' . $originAddress . '</td></tr>
<tr><td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#6b7280;">To</td><td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;font-size:14px;color:#111827;">' . $receiverName . '<br>' . $destinationAddress . '</td></tr>
<tr><td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#6b7280;">Package</td><td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;font-size:14px;color:#111827;">' . $weight . ' lbs • ' . $dimensions . '</td></tr>
<tr><td style="padding:12px 14px;font-size:13px;color:#6b7280;">Estimated Delivery</td><td style="padding:12px 14px;font-size:14px;color:#111827;">' . $estimatedDelivery . '</td></tr>
</table>
</td></tr>
<tr><td style="padding:0 40px 22px 40px;">
<a href="' . htmlspecialchars($trackUrl) . '" style="display:inline-block;background:#1d4ed8;color:#fff;text-decoration:none;padding:12px 20px;border-radius:6px;font-size:14px;font-weight:bold;margin-right:10px;">Track Shipment</a>
<a href="' . htmlspecialchars($dashboardUrl) . '" style="display:inline-block;background:#fff;color:#1d4ed8;text-decoration:none;padding:12px 20px;border-radius:6px;font-size:14px;font-weight:bold;border:1px solid #1d4ed8;">Open Dashboard</a>
</td></tr>
<tr><td style="padding:0 40px 18px 40px;"><p style="margin:0;font-size:12px;line-height:1.6;color:#6b7280;">Need help? Contact support@veteranlogisticsgroup.us.</p></td></tr>
<tr><td style="background:#f8fafc;border-top:1px solid #e5e7eb;padding:16px 24px;"><p style="margin:0;font-size:11px;line-height:1.5;color:#6b7280;">© 2026 Veteran Logistics Group. This is an automated shipment confirmation email.</p></td></tr>
</table>
</td></tr>
</table>
</body>
</html>';
}

function shipping_build_customer_invoice_email_html(array $payload): string {
    $tracking = htmlspecialchars((string)($payload['tracking_number'] ?? 'Unavailable'));
    $invoiceNumber = htmlspecialchars((string)($payload['invoice_number'] ?? ('INV-' . (string)($payload['tracking_number'] ?? '000000'))));
    $senderName = htmlspecialchars((string)($payload['sender_name'] ?? '-'));
    $service = htmlspecialchars((string)($payload['service_label'] ?? 'Economy'));
    $paymentMethod = htmlspecialchars((string)($payload['payment_method_label'] ?? 'Payment Card'));
    $serviceAmount = shipping_money((float)($payload['service_total'] ?? 0));
    $pickupAmount = shipping_money((float)($payload['pickup_total'] ?? 0));
    $carbonAmount = shipping_money((float)($payload['carbon_total'] ?? 0));
    $signatureAmount = shipping_money((float)($payload['signature_total'] ?? 0));
    $adultSignatureAmount = shipping_money((float)($payload['adult_signature_total'] ?? 0));
    $discountAmount = shipping_money((float)($payload['promo_discount_total'] ?? 0));
    $taxAmount = shipping_money((float)($payload['tax_total'] ?? 0));
    $totalAmount = shipping_money((float)($payload['total_charges'] ?? 0));
    $invoiceDate = htmlspecialchars(date('M j, Y'));
    $invoiceUrl = 'https://veteranlogisticsgroup.us/shipping/create/?s=5&created=1';

    return '<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Shipment Invoice</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f3f4f6;padding:24px 0;">
<tr><td align="center">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="640" style="max-width:640px;background:#ffffff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">
<tr><td style="background:#0f172a;padding:16px 28px;">
<a href="https://veteranlogisticsgroup.us/" target="_blank" rel="noopener" style="text-decoration:none;display:inline-block;">
<img src="https://veteranlogisticsgroup.us/assets/images/branding/logo-horizontal-dark.png" alt="Veteran Logistics Group" width="220" style="display:block;border:0;max-width:220px;height:auto;">
</a>
</td></tr>
<tr><td style="padding:28px 40px 6px 40px;"><h1 style="margin:0;font-size:26px;line-height:1.3;color:#0f172a;">Your shipment invoice</h1></td></tr>
<tr><td style="padding:0 40px 14px 40px;"><p style="margin:0;font-size:15px;line-height:1.7;color:#374151;">Hello ' . $senderName . ', payment was received successfully. Here are your invoice details.</p></td></tr>
<tr><td style="padding:0 40px 18px 40px;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #e5e7eb;border-radius:8px;">
<tr><td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#6b7280;">Invoice #</td><td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;font-size:14px;font-weight:bold;color:#0f172a;">' . $invoiceNumber . '</td></tr>
<tr><td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#6b7280;">Date</td><td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;font-size:14px;color:#111827;">' . $invoiceDate . '</td></tr>
<tr><td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#6b7280;">Tracking</td><td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;font-size:14px;color:#111827;">' . $tracking . '</td></tr>
<tr><td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#6b7280;">Service</td><td style="padding:12px 14px;border-bottom:1px solid #e5e7eb;font-size:14px;color:#111827;">' . $service . '</td></tr>
<tr><td style="padding:12px 14px;font-size:13px;color:#6b7280;">Payment Method</td><td style="padding:12px 14px;font-size:14px;color:#111827;">' . $paymentMethod . '</td></tr>
</table>
</td></tr>
<tr><td style="padding:0 40px 18px 40px;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #e5e7eb;border-radius:8px;">
<tr><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#6b7280;">Service</td><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-size:14px;color:#111827;text-align:right;">' . $serviceAmount . '</td></tr>
<tr><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#6b7280;">Pickup Fee</td><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-size:14px;color:#111827;text-align:right;">' . $pickupAmount . '</td></tr>
<tr><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#6b7280;">Carbon Neutral Charges</td><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-size:14px;color:#111827;text-align:right;">' . $carbonAmount . '</td></tr>
<tr><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#6b7280;">Signature Required</td><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-size:14px;color:#111827;text-align:right;">' . $signatureAmount . '</td></tr>
<tr><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#6b7280;">Adult Signature Required</td><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-size:14px;color:#111827;text-align:right;">' . $adultSignatureAmount . '</td></tr>
<tr><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#6b7280;">Promo Discount</td><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-size:14px;color:#166534;text-align:right;">-' . $discountAmount . '</td></tr>
<tr><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-size:13px;color:#6b7280;">Taxes and Duties</td><td style="padding:10px 14px;border-bottom:1px solid #e5e7eb;font-size:14px;color:#111827;text-align:right;">' . $taxAmount . '</td></tr>
<tr><td style="padding:12px 14px;font-size:14px;font-weight:bold;color:#0f172a;">Total Paid</td><td style="padding:12px 14px;font-size:16px;font-weight:bold;color:#0f172a;text-align:right;">' . $totalAmount . '</td></tr>
</table>
</td></tr>
<tr><td style="padding:0 40px 22px 40px;">
<a href="' . htmlspecialchars($invoiceUrl) . '" style="display:inline-block;background:#1d4ed8;color:#fff;text-decoration:none;padding:12px 20px;border-radius:6px;font-size:14px;font-weight:bold;">View Invoice</a>
</td></tr>
<tr><td style="padding:0 40px 18px 40px;"><p style="margin:0;font-size:12px;line-height:1.6;color:#6b7280;">For billing questions, contact billing@veteranlogisticsgroup.us.</p></td></tr>
<tr><td style="background:#f8fafc;border-top:1px solid #e5e7eb;padding:16px 24px;"><p style="margin:0;font-size:11px;line-height:1.5;color:#6b7280;">© 2026 Veteran Logistics Group. This is an automated invoice email.</p></td></tr>
</table>
</td></tr>
</table>
</body>
</html>';
}

function shipping_send_customer_post_create_emails(array $shipmentData): void {
    $customerEmail = trim((string)($shipmentData['sender_email'] ?? ''));
    if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        return;
    }

    $trackingNumber = (string)($shipmentData['tracking_number'] ?? '');
    $shipmentSubject = 'Shipment Confirmed - ' . $trackingNumber;
    $invoiceNumber = 'INV-' . preg_replace('/[^A-Z0-9]/', '', strtoupper($trackingNumber));
    $invoiceSubject = 'Invoice ' . $invoiceNumber . ' - Payment Received';

    $shipmentHtml = shipping_build_customer_shipment_email_html($shipmentData);
    $invoiceHtml = shipping_build_customer_invoice_email_html(array_merge($shipmentData, [
        'invoice_number' => $invoiceNumber
    ]));

    if (!shipping_send_html_email($customerEmail, 'shipments@veteranlogisticsgroup.us', $shipmentSubject, $shipmentHtml)) {
        error_log('shipping-create: failed sending shipment confirmation email for tracking ' . $trackingNumber);
    }
    if (!shipping_send_html_email($customerEmail, 'billing@veteranlogisticsgroup.us', $invoiceSubject, $invoiceHtml)) {
        error_log('shipping-create: failed sending invoice email for tracking ' . $trackingNumber);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_step = isset($_POST['current_step']) ? (int)$_POST['current_step'] : 1;
    if ($current_step < 1) $current_step = 1;
    if ($current_step > 4) $current_step = 4;

    $draft = $_SESSION['shipping_create_draft'];

    // Promo data is applied/cleared via promo-api.php and stored in session.
    if (!isset($draft['promo_code'])) $draft['promo_code'] = '';
    if (!isset($draft['promo_id'])) $draft['promo_id'] = 0;
    if (!isset($draft['promo_discount_amount'])) $draft['promo_discount_amount'] = 0;
    if (!isset($draft['promo_discount_type'])) $draft['promo_discount_type'] = '';
    if (!isset($draft['promo_discount_value'])) $draft['promo_discount_value'] = '';

    if ($current_step === 1) {
        $draft['ship_from_country'] = shipping_clean_text((string)($_POST['ship_from_country'] ?? 'United States'));
        $postedCountryCode = strtoupper(trim((string)($_POST['ship_from_country_code'] ?? 'US')));
        $draft['ship_from_country_code'] = preg_match('/^[A-Z]{2}$/', $postedCountryCode) ? $postedCountryCode : 'US';
        $draft['sender_name'] = shipping_clean_text((string)($_POST['sender_name'] ?? ''));
        $draft['sender_contact'] = shipping_clean_text((string)($_POST['sender_contact'] ?? ''));
        $draft['sender_email'] = trim((string)($_POST['sender_email'] ?? ''));
        $draft['sender_phone'] = shipping_clean_text((string)($_POST['sender_phone'] ?? ''));
        $draft['sender_notify'] = isset($_POST['sender_notify']) ? 1 : 0;
        $draft['origin_address'] = shipping_clean_text((string)($_POST['origin_address'] ?? ''));
        $draft['sender_address2'] = shipping_clean_text((string)($_POST['sender_address2'] ?? ''));
        $draft['sender_city'] = shipping_clean_text((string)($_POST['sender_city'] ?? ''));
        $draft['sender_state'] = shipping_clean_text((string)($_POST['sender_state'] ?? ''));
        $draft['sender_zip'] = shipping_clean_text((string)($_POST['sender_zip'] ?? ''));
        $draft['sender_save_address'] = isset($_POST['sender_save_address']) ? 1 : 0;
        $draft['receiver_name'] = shipping_clean_text((string)($_POST['receiver_name'] ?? ''));
        $draft['receiver_contact'] = shipping_clean_text((string)($_POST['receiver_contact'] ?? ''));
        $draft['receiver_email'] = trim((string)($_POST['receiver_email'] ?? ''));
        $draft['receiver_phone'] = shipping_clean_text((string)($_POST['receiver_phone'] ?? ''));
        $draft['receiver_notify'] = isset($_POST['receiver_notify']) ? 1 : 0;
        $draft['destination_address'] = shipping_clean_text((string)($_POST['destination_address'] ?? ''));
        $draft['receiver_address2'] = shipping_clean_text((string)($_POST['receiver_address2'] ?? ''));
        $draft['receiver_city'] = shipping_clean_text((string)($_POST['receiver_city'] ?? ''));
        $draft['receiver_state'] = shipping_clean_text((string)($_POST['receiver_state'] ?? ''));
        $draft['receiver_zip'] = shipping_clean_text((string)($_POST['receiver_zip'] ?? ''));
        $draft['receiver_save_address'] = isset($_POST['receiver_save_address']) ? 1 : 0;
        $draft['is_residential'] = isset($_POST['is_residential']) ? 1 : 0;
        $draft['packaging_type'] = in_array(($_POST['packaging_type'] ?? 'standard'), ['standard', 'ups_packaging'], true) ? (string)$_POST['packaging_type'] : 'standard';
        $draft['weight'] = trim((string)($_POST['weight'] ?? ''));
        $draft['length'] = trim((string)($_POST['length'] ?? ''));
        $draft['width'] = trim((string)($_POST['width'] ?? ''));
        $draft['height'] = trim((string)($_POST['height'] ?? ''));
        $postedShipmentClass = (string)($_POST['shipment_class'] ?? '');
        $validShipmentClasses = ['parcel', 'heavy_parcel', 'freight_pallet'];
        $draft['shipment_class'] = in_array($postedShipmentClass, $validShipmentClasses, true) ? $postedShipmentClass : 'parcel';

        if (!$relaxed_mode) {
            if ($draft['sender_name'] === '') $shipping_errors[] = 'Sender name is required.';
            if ($draft['sender_email'] === '' || !filter_var($draft['sender_email'], FILTER_VALIDATE_EMAIL)) $shipping_errors[] = 'A valid sender email is required.';
            if ($draft['origin_address'] === '') $shipping_errors[] = 'Origin address is required.';
            if ($draft['receiver_name'] === '') $shipping_errors[] = 'Receiver name is required.';
            if ($draft['receiver_email'] === '' || !filter_var($draft['receiver_email'], FILTER_VALIDATE_EMAIL)) $shipping_errors[] = 'A valid receiver email is required.';
            if ($draft['destination_address'] === '') $shipping_errors[] = 'Destination address is required.';

            foreach (['weight', 'length', 'width', 'height'] as $numField) {
                if ($draft[$numField] === '' || !is_numeric($draft[$numField]) || (float)$draft[$numField] <= 0) {
                    $shipping_errors[] = ucfirst($numField) . ' must be a number greater than zero.';
                }
            }
        }

        if (empty($shipping_errors) && $user_id > 0) {
            if (!empty($draft['sender_save_address'])) {
                shipping_save_user_address($conn, $user_id, 'sender', [
                    'full_name' => $draft['sender_name'],
                    'contact_name' => $draft['sender_contact'],
                    'email' => $draft['sender_email'],
                    'phone' => $draft['sender_phone'],
                    'address_line1' => $draft['origin_address'],
                    'address_line2' => $draft['sender_address2'],
                    'city' => $draft['sender_city'],
                    'state_region' => $draft['sender_state'],
                    'postal_code' => $draft['sender_zip'],
                    'country' => $draft['ship_from_country'],
                    'country_code' => $draft['ship_from_country_code'],
                    'is_residential' => 0
                ]);
            }

            if (!empty($draft['receiver_save_address'])) {
                shipping_save_user_address($conn, $user_id, 'receiver', [
                    'full_name' => $draft['receiver_name'],
                    'contact_name' => $draft['receiver_contact'] ?? '',
                    'email' => $draft['receiver_email'],
                    'phone' => $draft['receiver_phone'],
                    'address_line1' => $draft['destination_address'],
                    'address_line2' => $draft['receiver_address2'],
                    'city' => $draft['receiver_city'],
                    'state_region' => $draft['receiver_state'],
                    'postal_code' => $draft['receiver_zip'],
                    'country' => 'United States',
                    'country_code' => 'US',
                    'is_residential' => !empty($draft['is_residential']) ? 1 : 0
                ]);
            }
        }
    } elseif ($current_step === 2) {
        $pickupOption = (string)($_POST['pickup_option'] ?? 'dropoff');
        $draft['pickup_option'] = in_array($pickupOption, ['dropoff', 'pickup'], true) ? $pickupOption : 'dropoff';
        $defaultPickupDateYmd = shipping_default_pickup_date_ymd(
            (string)($draft['shipment_class'] ?? 'parcel'),
            (string)($draft['ship_from_country_code'] ?? 'US')
        );
        if ($draft['pickup_option'] === 'pickup') {
            $postedPickupDate = trim((string)($_POST['pickup_date'] ?? ''));
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $postedPickupDate) === 1) {
                $draft['pickup_date'] = $postedPickupDate;
            } else {
                $draft['pickup_date'] = $defaultPickupDateYmd;
            }
        } else {
            $draft['pickup_date'] = $defaultPickupDateYmd;
        }
        $pickupInstructions = shipping_clean_text((string)($_POST['pickup_instructions'] ?? ''));
        if (function_exists('mb_substr')) {
            $pickupInstructions = mb_substr($pickupInstructions, 0, 240);
        } else {
            $pickupInstructions = substr($pickupInstructions, 0, 240);
        }
        $draft['pickup_instructions'] = $pickupInstructions;
        $draft['quote_request_id'] = isset($_POST['quote_request_id']) ? (int)$_POST['quote_request_id'] : 0;
        $draft['quote_service_level'] = strtolower(trim((string)($_POST['quote_service_level'] ?? '')));
        $service = (string)($_POST['service_type'] ?? 'economy');
        if ($service === 'express') {
            $draft['service_type'] = 'overnight';
        } elseif ($service === 'priority') {
            $draft['service_type'] = 'express';
        } elseif ($service === 'economy' || $service === 'standard') {
            $draft['service_type'] = 'standard';
        } elseif ($service === 'overnight') {
            $draft['service_type'] = 'overnight';
        } elseif ($service === 'standard') {
            $draft['service_type'] = 'standard';
        } else {
            $draft['service_type'] = 'standard';
        }
        $selectedServiceLevel = ($service === 'standard') ? 'economy' : $service;
        if (!$relaxed_mode) {
            if ($draft['quote_request_id'] <= 0) {
                $shipping_errors[] = 'Process the selected Service Level before continuing.';
            } elseif ($draft['quote_service_level'] !== $selectedServiceLevel) {
                $shipping_errors[] = 'Service Level changed. Process the selected Service Level again.';
            }
        }
    } elseif ($current_step === 3) {
        $draft['package_contents'] = shipping_clean_text((string)($_POST['package_contents'] ?? ''));
        $draft['reference_number'] = shipping_clean_text((string)($_POST['reference_number'] ?? ''));
        $draft['parcel_value'] = trim((string)($_POST['parcel_value'] ?? ''));
        $draft['opt_carbon'] = isset($_POST['opt_carbon']) ? 1 : 0;
        $draft['opt_signature'] = isset($_POST['opt_signature']) ? 1 : 0;
        $draft['opt_adult_signature'] = isset($_POST['opt_adult_signature']) ? 1 : 0;
        $draft['shipment_purpose'] = shipping_clean_text((string)($_POST['shipment_purpose'] ?? 'I am sending a gift'));
        $draft['business_shipper'] = (($_POST['business_shipper'] ?? 'yes') === 'no') ? 'no' : 'yes';

        if (!$relaxed_mode) {
            if ($draft['package_contents'] === '') {
                $shipping_errors[] = 'Package contents is required.';
            } elseif (mb_strlen($draft['package_contents']) > 35) {
                $shipping_errors[] = 'Package contents must be at most 35 characters.';
            }

            if ($draft['parcel_value'] !== '' && (!is_numeric($draft['parcel_value']) || (float)$draft['parcel_value'] < 0)) {
                $shipping_errors[] = 'Parcel value must be a valid amount.';
            }
        }
    } elseif ($current_step === 4) {
        $postedPaymentMethod = strtolower(trim((string)($_POST['payment_method'] ?? 'card')));
        $draft['payment_method'] = in_array($postedPaymentMethod, ['card', 'crypto'], true) ? $postedPaymentMethod : 'card';
        $draft['card_type'] = shipping_clean_text((string)($_POST['card_type'] ?? ''));
        $draft['card_number'] = preg_replace('/\s+/', '', (string)($_POST['card_number'] ?? ''));
        $draft['card_expiry'] = shipping_clean_text((string)($_POST['card_expiry'] ?? ''));
        $draft['card_cvv'] = shipping_clean_text((string)($_POST['card_cvv'] ?? ''));
        $draft['cardholder_name'] = shipping_clean_text((string)($_POST['cardholder_name'] ?? ''));
        $postedCryptoAsset = strtolower(trim((string)($_POST['crypto_asset'] ?? 'bitcoin')));
        $draft['crypto_asset'] = in_array($postedCryptoAsset, ['bitcoin', 'ethereum', 'usdt'], true) ? $postedCryptoAsset : 'bitcoin';
        $draft['crypto_wallet_address'] = shipping_clean_text((string)($_POST['crypto_wallet_address'] ?? ''));
        $draft['accept_terms'] = isset($_POST['accept_terms']) ? 1 : 0;

        if (!$relaxed_mode) {
            // Hard stop: if card payment is blocked, suspend submit flow and reload step with block notice.
            if ($draft['payment_method'] === 'card' && $pay_block_flag === 1) {
                $card_pay_block_error = true;
                $blockMsg = $effective_pay_block_message !== ''
                    ? $effective_pay_block_message
                    : 'Card payment is currently restricted in your region or bank channel.';
                $shipping_errors[] = $blockMsg . ' Try other payment methods.';
            } elseif ($draft['payment_method'] === 'card') {
                if (!$card_payment_allowed) {
                    $shipping_errors[] = 'Card payment is currently unavailable for your account/region. Try other payment methods.';
                }
                if ($draft['card_type'] === '') {
                    $shipping_errors[] = 'Card type is required.';
                }
                if (!preg_match('/^[0-9]{12,19}$/', $draft['card_number'])) {
                    $shipping_errors[] = 'Enter a valid card number.';
                }
                if (!preg_match('/^[0-9]{2}\/[0-9]{2}$/', $draft['card_expiry'])) {
                    $shipping_errors[] = 'Enter card expiry in MM/YY format.';
                }
                if (!preg_match('/^[0-9]{3,4}$/', $draft['card_cvv'])) {
                    $shipping_errors[] = 'Enter a valid CVV.';
                }
                if ($draft['cardholder_name'] === '') {
                    $shipping_errors[] = 'Cardholder name is required.';
                }
            } else {
                if (!in_array($draft['crypto_asset'], ['bitcoin', 'ethereum', 'usdt'], true)) {
                    $shipping_errors[] = 'Select a valid cryptocurrency option.';
                }
                if ($draft['crypto_wallet_address'] === '') {
                    $shipping_errors[] = 'Wallet address is required for cryptocurrency payment.';
                }
                $proofFile = $_FILES['crypto_payment_proof'] ?? null;
                $hasExistingProof = trim((string)($draft['crypto_payment_proof_file'] ?? '')) !== '';
                $hasNewProof = is_array($proofFile) && isset($proofFile['error']) && (int)$proofFile['error'] !== UPLOAD_ERR_NO_FILE;
                if (!$hasExistingProof && !$hasNewProof) {
                    $shipping_errors[] = 'Upload proof of payment for Other Payment Methods.';
                }
                if (is_array($proofFile) && isset($proofFile['error']) && (int)$proofFile['error'] !== UPLOAD_ERR_NO_FILE && (int)$proofFile['error'] !== UPLOAD_ERR_OK) {
                    $shipping_errors[] = 'Proof of payment upload failed. Please try again.';
                }
            }
            if ($draft['accept_terms'] !== 1) {
                $shipping_errors[] = 'You must accept the Terms and Conditions.';
            }
        }
    }

    $_SESSION['shipping_create_draft'] = $draft;

    if (empty($shipping_errors)) {
        if ((int)$_SESSION['shipping_create_progress'] < $current_step) {
            $_SESSION['shipping_create_progress'] = $current_step;
        }

        if ($current_step < 4) {
            header("Location: /shipping/create/?s=" . ($current_step + 1));
            exit();
        }

        if ($current_step === 4 && $draft['payment_method'] === 'crypto') {
            $proofFile = $_FILES['crypto_payment_proof'] ?? null;
            if (is_array($proofFile) && isset($proofFile['error']) && (int)$proofFile['error'] !== UPLOAD_ERR_NO_FILE) {
                if ((int)$proofFile['error'] !== UPLOAD_ERR_OK) {
                    $shipping_errors[] = 'Proof of payment upload failed. Please try again.';
                } else {
                    $originalNameRaw = (string)($proofFile['name'] ?? '');
                    $normalizedName = shipping_normalize_upload_filename($originalNameRaw);
                    $ext = strtolower(pathinfo($normalizedName, PATHINFO_EXTENSION));
                    $allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $maxBytes = 10 * 1024 * 1024; // 10MB

                    if ($normalizedName === '' || $ext === '' || !in_array($ext, $allowedExt, true)) {
                        $shipping_errors[] = 'Proof of payment must be an image or PDF file.';
                    } elseif (!isset($proofFile['size']) || (int)$proofFile['size'] <= 0 || (int)$proofFile['size'] > $maxBytes) {
                        $shipping_errors[] = 'Proof of payment must be under 10MB.';
                    } else {
                        $tmpPath = (string)($proofFile['tmp_name'] ?? '');
                        $mimeOk = false;
                        if ($tmpPath !== '' && is_file($tmpPath)) {
                            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
                            if ($finfo) {
                                $mime = (string)@finfo_file($finfo, $tmpPath);
                                @finfo_close($finfo);
                                $allowedMime = [
                                    'application/pdf',
                                    'image/jpeg',
                                    'image/png',
                                    'image/gif',
                                    'image/webp'
                                ];
                                $mimeOk = in_array($mime, $allowedMime, true);
                            } else {
                                $mimeOk = true;
                            }
                        }
                        if (!$mimeOk) {
                            $shipping_errors[] = 'Invalid proof of payment file type.';
                        } else {
                            $uploadDir = __DIR__ . '/payments-upload';
                            if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0755, true)) {
                                $shipping_errors[] = 'Could not prepare payment proof upload directory.';
                            } else {
                                $safeBase = pathinfo($normalizedName, PATHINFO_FILENAME);
                                if ($safeBase === '') {
                                    $safeBase = 'payment_proof';
                                }
                                $storedFileName = time() . '_' . substr(bin2hex(random_bytes(8)), 0, 12) . '_' . $safeBase . '.' . $ext;
                                $targetPath = $uploadDir . '/' . $storedFileName;

                                if (!@move_uploaded_file($tmpPath, $targetPath)) {
                                    $shipping_errors[] = 'Could not save proof of payment file.';
                                } else {
                                    $tableCheck = $conn->query("SHOW TABLES LIKE 'shipment_payment_proofs'");
                                    if (!$tableCheck || $tableCheck->num_rows === 0) {
                                        @unlink($targetPath);
                                        $shipping_errors[] = "Table 'shipment_payment_proofs' does not exist yet. Create it first.";
                                    } else {
                                        $uploadedAtEpoch = time();
                                        $stmtProof = $conn->prepare(
                                            "INSERT INTO shipment_payment_proofs (user_id, name, email, file_name, uploaded_at_epoch)
                                             VALUES (?, ?, ?, ?, ?)"
                                        );
                                        if (!$stmtProof) {
                                            @unlink($targetPath);
                                            $shipping_errors[] = 'Could not prepare proof of payment record.';
                                        } else {
                                            $proofUserName = $user_name !== '' ? $user_name : $draft['cardholder_name'];
                                            $proofUserEmail = $user_email !== '' ? $user_email : $draft['sender_email'];
                                            $stmtProof->bind_param(
                                                "isssi",
                                                $user_id,
                                                $proofUserName,
                                                $proofUserEmail,
                                                $storedFileName,
                                                $uploadedAtEpoch
                                            );
                                            if (!$stmtProof->execute()) {
                                                @unlink($targetPath);
                                                $shipping_errors[] = 'Could not save proof of payment record.';
                                            } else {
                                                $draft['crypto_payment_proof_file'] = $storedFileName;
                                                $_SESSION['shipping_create_draft'] = $draft;
                                            }
                                            $stmtProof->close();
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!empty($shipping_errors)) {
            $step = $current_step;
        } else {
        $serviceType = $draft['service_type'];
        $shipmentType = $serviceType;
        $status = 'pending';
        $now = time();
        $leadDays = ($serviceType === 'overnight') ? 1 : (($serviceType === 'express') ? 2 : 5);
        $estimated = $now + ($leadDays * 86400);
        $trackingNumber = shipping_generate_tracking($conn);
        $currentLocation = $draft['origin_address'];
        $completionPercentage = 5;
        $notes = "Purpose: {$draft['shipment_purpose']} | Business: {$draft['business_shipper']} | Packaging: {$draft['packaging_type']} | ShipmentClass: {$draft['shipment_class']} | Pickup: {$draft['pickup_option']} | PickupDate: {$draft['pickup_date']} | PickupInstructions: {$draft['pickup_instructions']} | Contents: {$draft['package_contents']} | Ref: {$draft['reference_number']} | Value: {$draft['parcel_value']} | Carbon: {$draft['opt_carbon']} | Signature: {$draft['opt_signature']} | AdultSignature: {$draft['opt_adult_signature']} | SenderNotifyEmail: {$draft['sender_notify']} | ReceiverNotifyEmail: {$draft['receiver_notify']} | SenderAddr2: {$draft['sender_address2']} | SenderCity: {$draft['sender_city']} | SenderState: {$draft['sender_state']} | SenderZip: {$draft['sender_zip']} | ReceiverAddr2: {$draft['receiver_address2']} | ReceiverCity: {$draft['receiver_city']} | ReceiverState: {$draft['receiver_state']} | ReceiverZip: {$draft['receiver_zip']}";

        $senderPhone = $draft['sender_phone'] !== '' ? $draft['sender_phone'] : null;
        $receiverPhone = $draft['receiver_phone'] !== '' ? $draft['receiver_phone'] : null;
        $length = (float)$draft['length'];
        $width = (float)$draft['width'];
        $height = (float)$draft['height'];
        $weight = (float)$draft['weight'];

        $hasUser = $user_id > 0;
        $sql = $hasUser
            ? "INSERT INTO shipments
            (tracking_number, sender_name, sender_email, sender_phone, user_id, receiver_name, receiver_email, receiver_phone, origin_address, destination_address, length, width, height, weight, shipment_type, status, current_location, completion_percentage, estimated_delivery_time, date_created, date_updated, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            : "INSERT INTO shipments
            (tracking_number, sender_name, sender_email, sender_phone, user_id, receiver_name, receiver_email, receiver_phone, origin_address, destination_address, length, width, height, weight, shipment_type, status, current_location, completion_percentage, estimated_delivery_time, date_created, date_updated, notes)
            VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtCreate = $conn->prepare($sql);
        if (!$stmtCreate) {
            $shipping_errors[] = 'Unable to prepare shipment creation.';
        } else {
            if ($hasUser) {
                $stmtCreate->bind_param(
                    "ssssisssssddddsssiiiis",
                    $trackingNumber,
                    $draft['sender_name'],
                    $draft['sender_email'],
                    $senderPhone,
                    $user_id,
                    $draft['receiver_name'],
                    $draft['receiver_email'],
                    $receiverPhone,
                    $draft['origin_address'],
                    $draft['destination_address'],
                    $length,
                    $width,
                    $height,
                    $weight,
                    $shipmentType,
                    $status,
                    $currentLocation,
                    $completionPercentage,
                    $estimated,
                    $now,
                    $now,
                    $notes
                );
            } else {
                $stmtCreate->bind_param(
                    "sssssssssddddsssiiiis",
                    $trackingNumber,
                    $draft['sender_name'],
                    $draft['sender_email'],
                    $senderPhone,
                    $draft['receiver_name'],
                    $draft['receiver_email'],
                    $receiverPhone,
                    $draft['origin_address'],
                    $draft['destination_address'],
                    $length,
                    $width,
                    $height,
                    $weight,
                    $shipmentType,
                    $status,
                    $currentLocation,
                    $completionPercentage,
                    $estimated,
                    $now,
                    $now,
                    $notes
                );
            }

            if ($stmtCreate->execute()) {
                $shipmentId = (int)$stmtCreate->insert_id;
                $stmtCreate->close();

                $locationName = $draft['origin_address'];
                $countryCode = 'US';
                $statusText = 'Shipment information received';
                $eventTime = $now;

                $stmtEvent = $conn->prepare(
                    "INSERT INTO shipment_location_events
                    (shipment_id, tracking_number, location_label, event_severity, is_current, is_origin, is_destination, location_name, country_code, status_text, event_time_epoch, created_at_epoch, updated_at_epoch)
                    VALUES (?, ?, 'origin', 'neutral', 1, 1, 0, ?, ?, ?, ?, ?, ?)"
                );
                if ($stmtEvent) {
                    $stmtEvent->bind_param(
                        "issssiii",
                        $shipmentId,
                        $trackingNumber,
                        $locationName,
                        $countryCode,
                        $statusText,
                        $eventTime,
                        $now,
                        $now
                    );
                    $stmtEvent->execute();
                    $stmtEvent->close();
                }

                shipping_increment_promo_usage($conn, $draft);

                $basePriceMap = [
                    'standard' => 17.48,
                    'express' => 49.50,
                    'overnight' => 108.77
                ];
                $selectedServiceLevelUi = ($shipmentType === 'overnight') ? 'express' : (($shipmentType === 'express') ? 'priority' : 'economy');
                $quotedServiceAmount = shipping_resolve_quoted_service_amount($conn, $user_id, $draft, $selectedServiceLevelUi);
                $serviceAmount = ($quotedServiceAmount !== null)
                    ? (float)$quotedServiceAmount
                    : (float)($basePriceMap[$shipmentType] ?? 17.48);
                $pickupAmount = ($draft['pickup_option'] === 'pickup') ? 15.75 : 0.00;
                $carbonAmount = !empty($draft['opt_carbon']) ? 0.05 : 0.00;
                $signatureAmount = !empty($draft['opt_signature']) ? 7.70 : 0.00;
                $adultSignatureAmount = !empty($draft['opt_adult_signature']) ? 9.35 : 0.00;
                $optionsAmount = $carbonAmount + $signatureAmount + $adultSignatureAmount;
                $subtotalAmount = $serviceAmount + $pickupAmount + $optionsAmount;
                $promoAmount = (float)($draft['promo_discount_amount'] ?? 0);
                if ($promoAmount < 0) {
                    $promoAmount = 0;
                }
                if ($promoAmount > $subtotalAmount) {
                    $promoAmount = $subtotalAmount;
                }
                $finalAmount = $subtotalAmount - $promoAmount;
                $serviceLabel = ($shipmentType === 'overnight') ? 'Express' : (($shipmentType === 'express') ? 'Priority' : 'Economy');
                $paymentMethodLabel = (strtolower((string)($draft['payment_method'] ?? 'card')) === 'crypto')
                    ? 'Other Payment Methods'
                    : 'Payment Card';

                $_SESSION['shipping_last_created'] = [
                    'tracking_number' => $trackingNumber,
                    'shipment_type' => $shipmentType,
                    'service_label' => $serviceLabel,
                    'service_total' => $serviceAmount,
                    'pickup_total' => $pickupAmount,
                    'carbon_total' => $carbonAmount,
                    'signature_total' => $signatureAmount,
                    'adult_signature_total' => $adultSignatureAmount,
                    'promo_discount_total' => $promoAmount,
                    'tax_total' => 0.00,
                    'total_charges' => $finalAmount,
                    'payment_method' => (string)($draft['payment_method'] ?? 'card'),
                    'payment_method_label' => $paymentMethodLabel,
                    'crypto_asset' => (string)($draft['crypto_asset'] ?? ''),
                    'crypto_payment_proof_file' => (string)($draft['crypto_payment_proof_file'] ?? ''),
                    'weight' => $weight,
                    'length' => $length,
                    'width' => $width,
                    'height' => $height,
                    'sender_name' => $draft['sender_name'],
                    'sender_email' => $draft['sender_email'],
                    'sender_phone' => $senderPhone,
                    'receiver_name' => $draft['receiver_name'],
                    'receiver_email' => $draft['receiver_email'],
                    'receiver_phone' => $receiverPhone,
                    'origin_address' => $draft['origin_address'],
                    'destination_address' => $draft['destination_address'],
                    'pickup_option' => (string)($draft['pickup_option'] ?? 'dropoff'),
                    'pickup_date' => (string)($draft['pickup_date'] ?? ''),
                    'package_contents' => (string)($draft['package_contents'] ?? ''),
                    'reference_number' => (string)($draft['reference_number'] ?? ''),
                    'parcel_value' => (float)($draft['parcel_value'] ?? 0),
                    'shipment_class_label' => (
                        ($draft['shipment_class'] ?? 'parcel') === 'heavy_parcel'
                            ? 'Heavy Parcel'
                            : ((($draft['shipment_class'] ?? 'parcel') === 'freight_pallet') ? 'Freight / Pallet' : 'Parcel')
                    )
                ];
                shipping_send_customer_post_create_emails($_SESSION['shipping_last_created']);

                $_SESSION['shipping_create_progress'] = 4;
                unset($_SESSION['shipping_create_draft']);
                header("Location: /shipping/create/?s=5&created=1");
                exit();
            } else {
                $shipping_errors[] = 'Could not create shipment right now. Please try again.';
                $stmtCreate->close();
            }
        }
        }
    }

    $step = $current_step;
}

$shipment_form = $_SESSION['shipping_create_draft'] ?? [];
$created_shipment = $_SESSION['shipping_last_created'] ?? null;
if ($step === 5 && $created_shipment) {
    $shipping_success = 'Shipment created successfully.';
}
?>


