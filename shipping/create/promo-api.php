<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../common-sections/globals.php';
session_start();

function promo_json(array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload);
    exit();
}

function promo_find_first_column(array $columns, array $candidates): ?string {
    foreach ($candidates as $candidate) {
        if (isset($columns[$candidate])) {
            return $candidate;
        }
    }
    return null;
}

function promo_get_user(mysqli $conn): ?array {
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

    $stmt = $conn->prepare("SELECT id, email FROM users WHERE email = ? LIMIT 1");
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

function promo_get_draft(): array {
    $draft = $_SESSION['shipping_create_draft'] ?? [];
    return is_array($draft) ? $draft : [];
}

function promo_compute_subtotal(array $draft): float {
    $service = (string)($draft['service_type'] ?? 'standard');
    $basePrices = [
        'standard' => 17.48,
        'express' => 49.50,
        'overnight' => 108.77,
    ];
    $serviceTotal = $basePrices[$service] ?? 17.48;
    $pickupFee = ((string)($draft['pickup_option'] ?? 'dropoff') === 'pickup') ? 15.75 : 0.00;
    $optionCarbon = !empty($draft['opt_carbon']) ? 0.05 : 0.00;
    $optionSignature = !empty($draft['opt_signature']) ? 7.70 : 0.00;
    $optionAdult = !empty($draft['opt_adult_signature']) ? 9.35 : 0.00;
    return (float)($serviceTotal + $pickupFee + $optionCarbon + $optionSignature + $optionAdult);
}

function promo_normalize_type(string $typeRaw): string {
    $t = strtolower(trim($typeRaw));
    if (in_array($t, ['percent', 'percentage', 'pct'], true)) return 'percent';
    if (in_array($t, ['fixed', 'flat', 'amount'], true)) return 'fixed';
    return '';
}

function promo_compute_discount(float $subtotal, string $type, float $value): float {
    if ($subtotal <= 0 || $value <= 0) return 0.0;
    if ($type === 'percent') {
        $discount = $subtotal * ($value / 100);
    } elseif ($type === 'fixed') {
        $discount = $value;
    } else {
        return 0.0;
    }
    if ($discount < 0) $discount = 0.0;
    if ($discount > $subtotal) $discount = $subtotal;
    return round($discount, 2);
}

$user = promo_get_user($conn);
if (!$user || (int)$user['id'] <= 0) {
    promo_json(['ok' => false, 'message' => 'Authentication required.'], 401);
}

$action = strtolower((string)($_POST['action'] ?? $_GET['action'] ?? 'apply'));
$draft = promo_get_draft();

if ($action === 'clear') {
    $draft['promo_code'] = '';
    $draft['promo_id'] = 0;
    $draft['promo_discount_amount'] = 0;
    $draft['promo_discount_type'] = '';
    $draft['promo_discount_value'] = '';
    $_SESSION['shipping_create_draft'] = $draft;

    $subtotal = promo_compute_subtotal($draft);
    promo_json([
        'ok' => true,
        'applied' => false,
        'message' => 'Promo code removed.',
        'subtotal' => round($subtotal, 2),
        'discount_amount' => 0,
        'total' => round($subtotal, 2),
    ]);
}

$tableCheck = $conn->query("SHOW TABLES LIKE 'promocode'");
if (!$tableCheck || $tableCheck->num_rows === 0) {
    promo_json(['ok' => false, 'message' => 'Promo system unavailable.'], 500);
}

$columnsRes = $conn->query("SHOW COLUMNS FROM promocode");
if (!$columnsRes) {
    promo_json(['ok' => false, 'message' => 'Promo system unavailable.'], 500);
}
$columns = [];
while ($row = $columnsRes->fetch_assoc()) {
    $columns[$row['Field']] = true;
}

$idCol = promo_find_first_column($columns, ['id', 'promo_id']);
$codeCol = promo_find_first_column($columns, ['code', 'promo_code', 'coupon_code']);
$activeCol = promo_find_first_column($columns, ['is_active', 'active', 'status']);
$expiresCol = promo_find_first_column($columns, ['expires_at_epoch', 'expires_epoch', 'expiry_epoch', 'valid_until_epoch', 'expires_at']);
$startsCol = promo_find_first_column($columns, ['starts_at_epoch', 'start_at_epoch', 'valid_from_epoch', 'starts_at']);
$limitCol = promo_find_first_column($columns, ['usage_limit', 'max_uses', 'limit_count', 'max_use_count']);
$usedCol = promo_find_first_column($columns, ['used_count', 'times_used', 'usage_count', 'used']);
$typeCol = promo_find_first_column($columns, ['discount_type', 'type', 'benefit_type']);
$valueCol = promo_find_first_column($columns, ['discount_value', 'value', 'amount', 'discount_amount', 'percent_off']);

if ($codeCol === null || $typeCol === null || $valueCol === null) {
    promo_json(['ok' => false, 'message' => 'Promo table columns are incomplete.'], 500);
}

$inputCode = trim((string)($_POST['code'] ?? ''));
if ($inputCode === '') {
    promo_json(['ok' => false, 'applied' => false, 'status' => 'invalid', 'message' => 'Enter a promo code.'], 400);
}

$sql = "SELECT * FROM promocode WHERE LOWER({$codeCol}) = LOWER(?) LIMIT 1";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    promo_json(['ok' => false, 'message' => 'Unable to validate promo code.'], 500);
}
$stmt->bind_param('s', $inputCode);
$stmt->execute();
$res = $stmt->get_result();
$promo = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$promo) {
    promo_json(['ok' => false, 'applied' => false, 'status' => 'invalid', 'message' => 'Invalid promo code.']);
}

$now = time();

if ($activeCol !== null) {
    $activeRaw = $promo[$activeCol] ?? null;
    if (is_string($activeRaw)) {
        $statusText = strtolower(trim($activeRaw));
        if (in_array($statusText, ['0', 'inactive', 'disabled', 'expired'], true)) {
            promo_json(['ok' => false, 'applied' => false, 'status' => 'expired', 'message' => 'This promo code has expired.']);
        }
    } elseif ((int)$activeRaw === 0) {
        promo_json(['ok' => false, 'applied' => false, 'status' => 'expired', 'message' => 'This promo code has expired.']);
    }
}

if ($startsCol !== null) {
    $startEpoch = (int)($promo[$startsCol] ?? 0);
    if ($startEpoch > 0 && $now < $startEpoch) {
        promo_json(['ok' => false, 'applied' => false, 'status' => 'invalid', 'message' => 'This promo code is not active yet.']);
    }
}

if ($expiresCol !== null) {
    $expiresEpoch = (int)($promo[$expiresCol] ?? 0);
    if ($expiresEpoch > 0 && $now > $expiresEpoch) {
        promo_json(['ok' => false, 'applied' => false, 'status' => 'expired', 'message' => 'This promo code has expired.']);
    }
}

if ($limitCol !== null && $usedCol !== null) {
    $limit = (int)($promo[$limitCol] ?? 0);
    $used = (int)($promo[$usedCol] ?? 0);
    if ($limit > 0 && $used >= $limit) {
        promo_json(['ok' => false, 'applied' => false, 'status' => 'limit_reached', 'message' => 'This promo code has reached its usage limit.']);
    }
}

$discountType = promo_normalize_type((string)($promo[$typeCol] ?? ''));
$discountValue = (float)($promo[$valueCol] ?? 0);
$subtotal = promo_compute_subtotal($draft);
$discountAmount = promo_compute_discount($subtotal, $discountType, $discountValue);
if ($discountAmount <= 0) {
    promo_json(['ok' => false, 'applied' => false, 'status' => 'invalid', 'message' => 'Promo code is not valid for this shipment.']);
}

$draft['promo_code'] = (string)($promo[$codeCol] ?? $inputCode);
$draft['promo_id'] = ($idCol !== null) ? (int)($promo[$idCol] ?? 0) : 0;
$draft['promo_discount_amount'] = $discountAmount;
$draft['promo_discount_type'] = $discountType;
$draft['promo_discount_value'] = (string)$discountValue;
$_SESSION['shipping_create_draft'] = $draft;

$total = round(max(0, $subtotal - $discountAmount), 2);
promo_json([
    'ok' => true,
    'applied' => true,
    'status' => 'valid',
    'message' => 'Promo code applied successfully.',
    'promo_code' => $draft['promo_code'],
    'subtotal' => round($subtotal, 2),
    'discount_amount' => $discountAmount,
    'total' => $total,
    'discount_type' => $discountType,
    'discount_value' => $discountValue,
]);
