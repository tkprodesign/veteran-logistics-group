<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../common-sections/globals.php';
session_start();

function quote_json(array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload);
    exit();
}

function quote_clean_text(string $value): string {
    return trim(preg_replace('/\s+/', ' ', $value));
}

function quote_get_user(mysqli $conn): ?array {
    $activeEmail = '';
    if (!empty($_SESSION['email'])) {
        $activeEmail = trim((string)$_SESSION['email']);
    } elseif (!empty($_COOKIE['user_email'])) {
        $activeEmail = trim((string)$_COOKIE['user_email']);
        $_SESSION['email'] = $activeEmail;
    }
    if ($activeEmail === '') {
        return null;
    }

    $stmt = $conn->prepare("SELECT id, name, email, username, country_code, phone_number FROM users WHERE email = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $activeEmail);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $user ?: null;
}

function quote_ensure_table(mysqli $conn): bool {
    $sql = "
        CREATE TABLE IF NOT EXISTS shipment_service_quotes (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT NOT NULL,
            service_level ENUM('priority','express','economy') NOT NULL,
            payload_hash CHAR(64) NOT NULL,
            payload_json MEDIUMTEXT NOT NULL,
            price DECIMAL(10,2) NULL,
            duration INT UNSIGNED NULL,
            description_text TEXT NULL,
            comment_text TEXT NULL,
            processing_status ENUM('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
            email_sent_epoch INT UNSIGNED NULL,
            created_at_epoch INT UNSIGNED NOT NULL,
            updated_at_epoch INT UNSIGNED NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_quote_payload_service (user_id, service_level, payload_hash),
            KEY idx_quote_user_created (user_id, created_at_epoch),
            KEY idx_quote_status (processing_status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";

    return $conn->query($sql) === true;
}

function quote_snapshot_from_draft(array $draft): array {
    return [
        'sender_name' => quote_clean_text((string)($draft['sender_name'] ?? '')),
        'sender_email' => quote_clean_text((string)($draft['sender_email'] ?? '')),
        'sender_phone' => quote_clean_text((string)($draft['sender_phone'] ?? '')),
        'origin_address' => quote_clean_text((string)($draft['origin_address'] ?? '')),
        'receiver_name' => quote_clean_text((string)($draft['receiver_name'] ?? '')),
        'receiver_email' => quote_clean_text((string)($draft['receiver_email'] ?? '')),
        'receiver_phone' => quote_clean_text((string)($draft['receiver_phone'] ?? '')),
        'destination_address' => quote_clean_text((string)($draft['destination_address'] ?? '')),
        'weight' => quote_clean_text((string)($draft['weight'] ?? '')),
        'length' => quote_clean_text((string)($draft['length'] ?? '')),
        'width' => quote_clean_text((string)($draft['width'] ?? '')),
        'height' => quote_clean_text((string)($draft['height'] ?? '')),
        'shipment_class' => quote_clean_text((string)($draft['shipment_class'] ?? 'parcel')),
        'packaging_type' => quote_clean_text((string)($draft['packaging_type'] ?? 'standard')),
        'pickup_option' => quote_clean_text((string)($draft['pickup_option'] ?? 'dropoff')),
        'pickup_date' => quote_clean_text((string)($draft['pickup_date'] ?? date('Y-m-d'))),
        'pickup_instructions' => quote_clean_text((string)($draft['pickup_instructions'] ?? '')),
    ];
}

function quote_payload_hash(array $snapshot): string {
    $normalized = json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return hash('sha256', (string)$normalized);
}

function quote_send_resend_admin_email(string $subject, string $text): bool {
    $apiKey = trim((string)getenv('RESEND_API_KEY'));
    if ($apiKey === '') {
        error_log('quote-api: missing RESEND_API_KEY');
        return false;
    }

    $payload = [
        'from' => 'noreply@veteranlogisticsgroup.us',
        'to' => ['admin@veteranlogisticsgroup.com'],
        'subject' => $subject,
        'text' => $text,
    ];

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$apiKey}",
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr !== '') {
        error_log('quote-api: resend curl error: ' . $curlErr);
        return false;
    }
    if ($httpCode !== 200 && $httpCode !== 201) {
        error_log('quote-api: resend rejected request (' . $httpCode . '): ' . (string)$response);
        return false;
    }

    return true;
}

$user = quote_get_user($conn);
if (!$user) {
    quote_json(['ok' => false, 'message' => 'Authentication required.'], 401);
}
if (!quote_ensure_table($conn)) {
    quote_json(['ok' => false, 'message' => 'Unable to initialize processing table.'], 500);
}

$action = strtolower((string)($_REQUEST['action'] ?? ''));
$now = time();
$uid = (int)$user['id'];
$draft = $_SESSION['shipping_create_draft'] ?? [];
if (!is_array($draft)) {
    $draft = [];
}
$snapshot = quote_snapshot_from_draft($draft);
$payloadHash = quote_payload_hash($snapshot);

if ($action === 'request') {
    $serviceLevel = strtolower((string)($_POST['service_level'] ?? $_POST['service_type'] ?? 'economy'));
    if (!in_array($serviceLevel, ['priority', 'express', 'economy'], true)) {
        quote_json(['ok' => false, 'message' => 'Invalid service level.'], 400);
    }

    $stmtExisting = $conn->prepare("SELECT id, price, duration, description_text, comment_text, processing_status, created_at_epoch, updated_at_epoch FROM shipment_service_quotes WHERE user_id = ? AND service_level = ? AND payload_hash = ? LIMIT 1");
    if (!$stmtExisting) {
        quote_json(['ok' => false, 'message' => 'Unable to check existing request.'], 500);
    }
    $stmtExisting->bind_param('iss', $uid, $serviceLevel, $payloadHash);
    $stmtExisting->execute();
    $resExisting = $stmtExisting->get_result();
    $existing = $resExisting ? $resExisting->fetch_assoc() : null;
    $stmtExisting->close();

    if ($existing) {
        $price = $existing['price'];
        $ready = ($price !== null && (float)$price > 0);
        $_SESSION['shipping_create_draft']['quote_request_id'] = (int)$existing['id'];
        $_SESSION['shipping_create_draft']['quote_service_level'] = $serviceLevel;
        quote_json([
            'ok' => true,
            'already_exists' => true,
            'ready' => $ready,
            'request_id' => (int)$existing['id'],
            'payload_hash' => $payloadHash,
            'record' => [
                'id' => (int)$existing['id'],
                'user_id' => $uid,
                'service_level' => $serviceLevel,
                'payload_hash' => $payloadHash,
                'price' => ($price === null) ? null : (float)$price,
                'duration' => $existing['duration'] !== null ? (int)$existing['duration'] : null,
                'description_text' => $existing['description_text'] !== null ? (string)$existing['description_text'] : null,
                'comment_text' => $existing['comment_text'] !== null ? (string)$existing['comment_text'] : null,
                'processing_status' => (string)$existing['processing_status'],
                'created_at_epoch' => (int)$existing['created_at_epoch'],
                'updated_at_epoch' => (int)$existing['updated_at_epoch'],
            ]
        ]);
    }

    $payload = [
        'customer' => [
            'id' => $uid,
            'name' => (string)$user['name'],
            'email' => (string)$user['email'],
            'username' => (string)$user['username'],
            'country_code' => (string)$user['country_code'],
            'phone_number' => (string)$user['phone_number'],
        ],
        'shipment_snapshot' => $snapshot,
        'service_level' => $serviceLevel,
        'payload_hash' => $payloadHash,
        'requested_at_epoch' => $now,
        'request_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'request_user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
    ];
    $payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $stmtInsert = $conn->prepare("INSERT INTO shipment_service_quotes (user_id, service_level, payload_hash, payload_json, processing_status, created_at_epoch, updated_at_epoch) VALUES (?, ?, ?, ?, 'pending', ?, ?)");
    if (!$stmtInsert) {
        quote_json(['ok' => false, 'message' => 'Could not create processing record.'], 500);
    }
    $stmtInsert->bind_param('isssii', $uid, $serviceLevel, $payloadHash, $payloadJson, $now, $now);
    if (!$stmtInsert->execute()) {
        $stmtInsert->close();
        quote_json(['ok' => false, 'message' => 'Could not save processing request.'], 500);
    }
    $requestId = (int)$stmtInsert->insert_id;
    $stmtInsert->close();

    $_SESSION['shipping_create_draft']['quote_request_id'] = $requestId;
    $_SESSION['shipping_create_draft']['quote_service_level'] = $serviceLevel;

    $subject = "Shipment Service Processing Request #{$requestId}";
    $message = "A new service processing request was created.\n\n"
        . "Request ID: {$requestId}\n"
        . "User ID: {$uid}\n"
        . "User Email: {$user['email']}\n"
        . "Service Level: {$serviceLevel}\n"
        . "Payload Hash: {$payloadHash}\n"
        . "Created At Epoch: {$now}\n\n"
        . "Payload JSON:\n{$payloadJson}\n";
    $emailSent = quote_send_resend_admin_email($subject, $message);

    if ($emailSent) {
        $stmtMail = $conn->prepare("UPDATE shipment_service_quotes SET email_sent_epoch = ?, updated_at_epoch = ? WHERE id = ? LIMIT 1");
        if ($stmtMail) {
            $stmtMail->bind_param('iii', $now, $now, $requestId);
            $stmtMail->execute();
            $stmtMail->close();
        }
    }

    quote_json([
        'ok' => true,
        'already_exists' => false,
        'ready' => false,
        'request_id' => $requestId,
        'payload_hash' => $payloadHash,
        'email_dispatched' => (bool)$emailSent
    ]);
}

if ($action === 'status') {
    $requestId = isset($_GET['request_id']) ? (int)$_GET['request_id'] : 0;
    if ($requestId <= 0) {
        quote_json(['ok' => false, 'message' => 'Invalid request id.'], 400);
    }

    $stmt = $conn->prepare("SELECT * FROM shipment_service_quotes WHERE id = ? AND user_id = ? LIMIT 1");
    if (!$stmt) {
        quote_json(['ok' => false, 'message' => 'Unable to read processing record.'], 500);
    }
    $stmt->bind_param('ii', $requestId, $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        quote_json(['ok' => false, 'message' => 'Record not found.'], 404);
    }

    $price = $row['price'];
    $ready = ($price !== null && (float)$price > 0);

    quote_json([
        'ok' => true,
        'ready' => $ready,
        'record' => [
            'id' => (int)$row['id'],
            'user_id' => (int)$row['user_id'],
            'service_level' => (string)$row['service_level'],
            'payload_hash' => (string)$row['payload_hash'],
            'price' => ($price === null) ? null : (float)$price,
            'duration' => $row['duration'] !== null ? (int)$row['duration'] : null,
            'description_text' => $row['description_text'] !== null ? (string)$row['description_text'] : null,
            'comment_text' => $row['comment_text'] !== null ? (string)$row['comment_text'] : null,
            'processing_status' => (string)$row['processing_status'],
            'email_sent_epoch' => $row['email_sent_epoch'] !== null ? (int)$row['email_sent_epoch'] : null,
            'created_at_epoch' => (int)$row['created_at_epoch'],
            'updated_at_epoch' => (int)$row['updated_at_epoch'],
        ]
    ]);
}

if ($action === 'list') {
    $stmt = $conn->prepare("SELECT id, service_level, payload_hash, price, duration, description_text, comment_text, processing_status, created_at_epoch, updated_at_epoch FROM shipment_service_quotes WHERE user_id = ? AND payload_hash = ? ORDER BY id DESC");
    if (!$stmt) {
        quote_json(['ok' => false, 'message' => 'Unable to list processing records.'], 500);
    }
    $stmt->bind_param('is', $uid, $payloadHash);
    $stmt->execute();
    $res = $stmt->get_result();

    $records = [];
    while ($row = $res->fetch_assoc()) {
        $price = $row['price'];
        $records[] = [
            'id' => (int)$row['id'],
            'service_level' => (string)$row['service_level'],
            'payload_hash' => (string)$row['payload_hash'],
            'price' => ($price === null) ? null : (float)$price,
            'duration' => $row['duration'] !== null ? (int)$row['duration'] : null,
            'description_text' => $row['description_text'] !== null ? (string)$row['description_text'] : null,
            'comment_text' => $row['comment_text'] !== null ? (string)$row['comment_text'] : null,
            'processing_status' => (string)$row['processing_status'],
            'ready' => ($price !== null && (float)$price > 0),
            'created_at_epoch' => (int)$row['created_at_epoch'],
            'updated_at_epoch' => (int)$row['updated_at_epoch'],
        ];
    }
    $stmt->close();

    quote_json([
        'ok' => true,
        'payload_hash' => $payloadHash,
        'records' => $records
    ]);
}

quote_json(['ok' => false, 'message' => 'Unsupported action.'], 400);
