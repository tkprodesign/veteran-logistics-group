<?php
require_once __DIR__ . '/globals.php';

$serviceAlertCount = 0;
$serviceAlertLink = '/support/';
$headerSignedInEmail = '';

if (isset($user_email) && trim((string)$user_email) !== '') {
    $headerSignedInEmail = trim((string)$user_email);
} elseif (isset($_SESSION['email']) && trim((string)$_SESSION['email']) !== '') {
    $headerSignedInEmail = trim((string)$_SESSION['email']);
} elseif (isset($_COOKIE['user_email']) && trim((string)$_COOKIE['user_email']) !== '') {
    $headerSignedInEmail = trim((string)$_COOKIE['user_email']);
}

if ($headerSignedInEmail !== '') {
    $serviceAlertLink = '/dashboard/?t=overview&a=incoming#shipment-activity';
    $serviceAlertStatuses = [
        'pending',
        'incoming',
        'in_transit',
        'out_for_delivery',
        'failed',
        'cancelled',
        'delayed',
        'exception',
        'on_hold',
    ];

    if (isset($conn) && $conn instanceof mysqli && empty($conn->connect_error)) {
        $headerConn = $conn;
    } elseif (isset($dbconn) && $dbconn instanceof mysqli && empty($dbconn->connect_error)) {
        $headerConn = $dbconn;
    } else {
        $headerConn = null;
    }

    if ($headerConn && empty($headerConn->connect_error)) {
        $email = $headerSignedInEmail;
        $placeholders = implode(',', array_fill(0, count($serviceAlertStatuses), '?'));
        $sql = "
            SELECT COUNT(*) AS alert_count
            FROM shipments s
            INNER JOIN users u ON u.id = s.user_id
            WHERE u.email = ?
              AND s.status IN ($placeholders)
        ";

        $stmt = $headerConn->prepare($sql);
        if ($stmt) {
            $types = 's' . str_repeat('s', count($serviceAlertStatuses));
            $params = array_merge([$email], $serviceAlertStatuses);
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                $row = $stmt->get_result()->fetch_assoc();
                $serviceAlertCount = isset($row['alert_count']) ? (int)$row['alert_count'] : 0;
            }
            $stmt->close();
        }
    }

}
$headerIsSignedIn = ($headerSignedInEmail !== '');
$mobileAccountHref = $headerIsSignedIn ? '/dashboard/' : '/login/';
$headerRequestPath = (string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
$logoutHref = (strpos($headerRequestPath, '/dashboard') === 0) ? '/logout/?next=login' : '/logout/?next=home';
?>

