<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

date_default_timezone_set('America/New_York');

require_once __DIR__ . '/../common-sections/globals.php';

session_start();

function epoch_to_seconds($t) {
    if ($t === null) return null;
    $t = (int)$t;
    return ($t > 1000000000000) ? (int)($t / 1000) : $t;
}

if (!function_exists('bind_dynamic_params')) {
    function bind_dynamic_params(mysqli_stmt $stmt, string $types, array $params): void {
        $refs = [];
        $refs[] = &$types;
        foreach ($params as $k => $v) {
            $refs[] = &$params[$k];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }
}

function record_payment_method_event(mysqli $conn, ?int $payment_method_id, int $user_id, string $event_type, string $event_message): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $now = time();

    $sql = "
        INSERT INTO payment_method_events
        (payment_method_id, user_id, event_type, event_message, ip_address, user_agent, created_at_epoch)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return;
    $stmt->bind_param("iissssi", $payment_method_id, $user_id, $event_type, $event_message, $ip, $agent, $now);
    $stmt->execute();
    $stmt->close();
}

function fetch_user_payment_methods(mysqli $conn, int $user_id): array {
    $items = [];
    $sql = "
        SELECT id, method_type, card_brand, card_last4, exp_month, exp_year, wallet_network, wallet_address, display_label
        FROM payment_methods
        WHERE user_id = ?
          AND record_status = 'active'
        ORDER BY is_default DESC, id DESC
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return $items;
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $label = '';
        $meta = '';
        if ($row['method_type'] === 'card') {
            $brand = trim((string)$row['card_brand']);
            $last4 = trim((string)$row['card_last4']);
            $label = $row['display_label'] ?: trim($brand . ' **** ' . $last4);
            if (!empty($row['exp_month']) && !empty($row['exp_year'])) {
                $meta = 'Expires ' . str_pad((string)$row['exp_month'], 2, '0', STR_PAD_LEFT) . '/' . $row['exp_year'];
            } else {
                $meta = 'Card on file';
            }
        } else {
            $network = trim((string)$row['wallet_network']);
            $address = trim((string)$row['wallet_address']);
            $shortAddress = strlen($address) > 12 ? substr($address, 0, 6) . '...' . substr($address, -4) : $address;
            $label = $row['display_label'] ?: trim($network . ' ' . $shortAddress);
            $meta = 'Wallet verified by signed message';
        }
        $items[] = [
            'id' => (int)$row['id'],
            'type' => (string)$row['method_type'],
            'label' => $label,
            'meta' => $meta,
        ];
    }
    $stmt->close();
    return $items;
}

if (isset($_COOKIE['user_email']) && !empty($_COOKIE['user_email'])) {
    if (!isset($_SESSION['email'])) {
        $_SESSION['email'] = $_COOKIE['user_email'];
    }
} else {
    header("Location: /login");
    exit();
}

$stmt = $conn->prepare(
    "SELECT id, name, email, country_code, phone_number, username, created_at
     FROM users
     WHERE email = ?
     LIMIT 1"
);
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$result = $stmt->get_result();
$userRow = $result->fetch_assoc();
$stmt->close();

if (!$userRow) {
    header("Location: /login");
    exit();
}

$user_id         = (int)$userRow['id'];
$user_name       = $userRow['name'];
$user_email      = $userRow['email'];
$user_country    = $userRow['country_code'];
$user_phone      = $userRow['phone_number'];
$user_username   = $userRow['username'];
$user_created_at = epoch_to_seconds($userRow['created_at']);
$joined_display  = $user_created_at ? date('F Y', $user_created_at) : "Unknown";
$profile_notice = isset($_SESSION['profile_notice']) ? (string)$_SESSION['profile_notice'] : '';
$profile_error = '';
$profile_edit_open = (isset($_GET['edit']) && $_GET['edit'] === 'phone');

if (isset($_SESSION['profile_notice'])) {
    unset($_SESSION['profile_notice']);
}

$stmtLogin = $conn->prepare(
    "SELECT login_at
     FROM user_logins
     WHERE user_id = ?
     ORDER BY login_at DESC
     LIMIT 1 OFFSET 1"
);
$stmtLogin->bind_param("i", $user_id);
$stmtLogin->execute();
$loginResult = $stmtLogin->get_result();
$prevLoginRow = $loginResult->fetch_assoc();
$stmtLogin->close();

$prev_login_ts = $prevLoginRow ? epoch_to_seconds($prevLoginRow['login_at']) : null;
$last_login_display = $prev_login_ts ? date('M j, Y', $prev_login_ts) : "Never";

$page = isset($_GET['t']) ? $_GET['t'] : 'overview';
$active_activity = isset($_GET['a']) ? $_GET['a'] : 'incoming';
$current_shipments = [];
$track_base_url = 'https://veteranlogisticsgroup.us/track?id=';
$wallet_payment_methods = [];
$wallet_notice = isset($_SESSION['wallet_notice']) ? (string)$_SESSION['wallet_notice'] : '';
$wallet_error = '';

if (isset($_SESSION['wallet_notice'])) {
    unset($_SESSION['wallet_notice']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['profile_action']) && $_POST['profile_action'] === 'update_phone') {
    $new_country_code = trim((string)($_POST['country_code'] ?? ''));
    $new_phone_number = trim((string)($_POST['phone_number'] ?? ''));
    $profile_edit_open = true;

    if ($new_country_code === '' || !preg_match('/^\+[0-9]{1,4}$/', $new_country_code)) {
        $profile_error = 'Choose a valid country code.';
    } elseif ($new_phone_number === '') {
        $profile_error = 'Phone number is required.';
    } elseif (preg_match('/\s/', $new_phone_number)) {
        $profile_error = 'Phone number cannot contain spaces.';
    } elseif (!preg_match('/^[0-9]+$/', $new_phone_number)) {
        $profile_error = 'Phone number must contain digits only.';
    } elseif (strlen($new_phone_number) < 7) {
        $profile_error = 'Phone number is too short.';
    }

    if ($profile_error === '') {
        $stmtUpdatePhone = $conn->prepare("UPDATE users SET country_code = ?, phone_number = ? WHERE id = ? LIMIT 1");
        if ($stmtUpdatePhone) {
            $stmtUpdatePhone->bind_param("ssi", $new_country_code, $new_phone_number, $user_id);
            if ($stmtUpdatePhone->execute()) {
                $user_country = $new_country_code;
                $user_phone = $new_phone_number;
                $_SESSION['profile_notice'] = 'Phone number updated successfully.';
                $stmtUpdatePhone->close();
                header("Location: /dashboard/?t=profile");
                exit();
            } else {
                $profile_error = 'Unable to update phone number right now.';
            }
            $stmtUpdatePhone->close();
        } else {
            $profile_error = 'Unable to prepare profile update.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wallet_action']) && $_POST['wallet_action'] === 'add_payment_method') {
    $method_type = isset($_POST['method_type']) ? strtolower(trim((string)$_POST['method_type'])) : '';
    $now = time();

    if (!in_array($method_type, ['card', 'crypto'], true)) {
        $wallet_error = 'Choose a payment method type.';
        record_payment_method_event($conn, null, $user_id, 'failed_validation', 'Invalid payment method type');
    } elseif ($method_type === 'card') {
        $card_brand = trim((string)($_POST['card_brand'] ?? ''));
        $card_last4 = preg_replace('/\D+/', '', (string)($_POST['card_last4'] ?? ''));
        $exp_month_raw = trim((string)($_POST['exp_month'] ?? ''));
        $exp_year_raw = trim((string)($_POST['exp_year'] ?? ''));
        $processor_token = trim((string)($_POST['processor_token'] ?? ''));
        $token_source_note = trim((string)($_POST['card_token_note'] ?? ''));
        $exp_month = ctype_digit($exp_month_raw) ? (int)$exp_month_raw : 0;
        $exp_year = ctype_digit($exp_year_raw) ? (int)$exp_year_raw : 0;
        $display_label = trim($card_brand . ' **** ' . $card_last4);

        if (
            $card_brand === '' ||
            strlen($card_last4) !== 4 ||
            $exp_month < 1 || $exp_month > 12 ||
            $exp_year < 2000 || $exp_year > 3000 ||
            $processor_token === ''
        ) {
            $wallet_error = 'Complete all required card fields.';
            record_payment_method_event($conn, null, $user_id, 'failed_validation', 'Card payment method validation failed');
        } else {
            $sql = "
                INSERT INTO payment_methods
                (user_id, method_type, card_brand, card_last4, exp_month, exp_year, processor_token, token_source_note, display_label, is_default, record_status, created_at_epoch)
                VALUES (?, 'card', ?, ?, ?, ?, ?, ?, ?, 0, 'active', ?)
            ";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("issiisssi", $user_id, $card_brand, $card_last4, $exp_month, $exp_year, $processor_token, $token_source_note, $display_label, $now);
                if ($stmt->execute()) {
                    $payment_method_id = (int)$stmt->insert_id;
                    record_payment_method_event($conn, $payment_method_id, $user_id, 'added', 'Card payment method added');
                } else {
                    $wallet_error = ((int)$stmt->errno === 1062)
                        ? 'This card token is already linked to your profile.'
                        : 'Unable to save card method right now.';
                    record_payment_method_event($conn, null, $user_id, 'failed_validation', 'Card insert failed');
                }
                $stmt->close();
            } else {
                $wallet_error = 'Unable to prepare card save operation.';
            }
        }
    } else {
        $wallet_network = trim((string)($_POST['wallet_network'] ?? ''));
        $wallet_address = trim((string)($_POST['wallet_address'] ?? ''));
        $ownership_proof = trim((string)($_POST['ownership_proof'] ?? ''));
        $short_address = strlen($wallet_address) > 12
            ? substr($wallet_address, 0, 6) . '...' . substr($wallet_address, -4)
            : $wallet_address;
        $display_label = trim($wallet_network . ' ' . $short_address);

        if ($wallet_network === '' || $wallet_address === '' || $ownership_proof === '') {
            $wallet_error = 'Complete all required crypto wallet fields.';
            record_payment_method_event($conn, null, $user_id, 'failed_validation', 'Crypto payment method validation failed');
        } else {
            $sql = "
                INSERT INTO payment_methods
                (user_id, method_type, wallet_network, wallet_address, ownership_proof, display_label, is_default, record_status, created_at_epoch)
                VALUES (?, 'crypto', ?, ?, ?, ?, 0, 'active', ?)
            ";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("issssi", $user_id, $wallet_network, $wallet_address, $ownership_proof, $display_label, $now);
                if ($stmt->execute()) {
                    $payment_method_id = (int)$stmt->insert_id;
                    record_payment_method_event($conn, $payment_method_id, $user_id, 'added', 'Crypto payment method added');
                } else {
                    $wallet_error = ((int)$stmt->errno === 1062)
                        ? 'This wallet is already linked to your profile.'
                        : 'Unable to save crypto wallet right now.';
                    record_payment_method_event($conn, null, $user_id, 'failed_validation', 'Crypto insert failed');
                }
                $stmt->close();
            } else {
                $wallet_error = 'Unable to prepare crypto save operation.';
            }
        }
    }

    if ($wallet_error === '') {
        $_SESSION['wallet_notice'] = 'Payment method added successfully.';
        header('Location: /dashboard/?t=wallet');
        exit();
    }
}

$wallet_payment_methods = fetch_user_payment_methods($conn, $user_id);

if ($page === 'overview') {
    $tabToStatuses = [
        'incoming'  => ['pending', 'incoming', 'in_transit', 'out_for_delivery'],
        'outgoing'  => ['outgoing', 'shipped'],
        'delivered' => ['delivered', 'failed', 'cancelled'],
        'pickups'   => ['picked_up'],
        'instore'   => ['in_store'],
    ];

    if (!isset($tabToStatuses[$active_activity])) {
        $active_activity = 'incoming';
    }

    $statuses = $tabToStatuses[$active_activity];
    $placeholders = implode(',', array_fill(0, count($statuses), '?'));

    $sql = "
        SELECT tracking_number, date_created, status
        FROM shipments
        WHERE user_id = ?
          AND status IN ($placeholders)
        ORDER BY date_created DESC, tracking_number DESC
        LIMIT 10
    ";

    $stmtShip = $conn->prepare($sql);
    $types = 'i' . str_repeat('s', count($statuses));
    $params = array_merge([(int)$user_id], $statuses);

    bind_dynamic_params($stmtShip, $types, $params);
    $stmtShip->execute();
    $resShip = $stmtShip->get_result();

    while ($row = $resShip->fetch_assoc()) {
        $ts = epoch_to_seconds($row['date_created']);
        $tracking = (string)$row['tracking_number'];

        $current_shipments[] = [
            'id'     => $tracking,
            'url'    => $track_base_url . rawurlencode($tracking),
            'date'   => $ts ? date('M d, Y', $ts) : "Unknown",
            'status' => ucwords(str_replace('_', ' ', $row['status'])),
        ];
    }

    $stmtShip->close();
}
?>
