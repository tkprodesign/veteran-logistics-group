<?php
require_once __DIR__ . '/../../common-sections/globals.php';

if (isset($conn) && $conn instanceof mysqli) {
    try {
        $conn->query("ALTER TABLE shipment_location_events ADD COLUMN payment_amount DECIMAL(10,2) NULL DEFAULT NULL");
    } catch (Throwable $e) {
    }
    try {
        $conn->query("ALTER TABLE shipment_location_events ADD COLUMN payment_reason VARCHAR(255) NULL DEFAULT NULL");
    } catch (Throwable $e) {
    }
}

$exceptionRequestPath = (string)($_SERVER['REQUEST_URI'] ?? '/track/exception/');
$exceptionSignedIn = !empty($_COOKIE['user_email']) || !empty($_COOKIE['user_Email']);
if (!$exceptionSignedIn) {
    header('Location: /login/?required_login=1&redirect=' . urlencode($exceptionRequestPath));
    exit();
}

$tracking_id_raw = isset($_GET['tn']) ? trim((string)$_GET['tn']) : '';
$event_id = isset($_GET['eid']) ? (int)$_GET['eid'] : 0;

$has_error = false;
$error_text = '';
$event = null;
$shipment_status_text = 'In Transit';
$eta_text = '';
$timeline = [];

if ($tracking_id_raw === '' || $event_id <= 0) {
    $has_error = true;
    $error_text = 'Invalid exception link. Please open details from the tracking history.';
}

if (!$has_error && isset($conn) && $conn instanceof mysqli) {
    $eventSql = "
        SELECT id, tracking_number, event_time_epoch, status_text, event_severity, issue_note, payment_amount, payment_reason,
               location_name, city, state_region, country_code
        FROM shipment_location_events
        WHERE id = ? AND tracking_number = ?
        LIMIT 1
    ";
    $stmtEvent = $conn->prepare($eventSql);
    if ($stmtEvent) {
        $stmtEvent->bind_param("is", $event_id, $tracking_id_raw);
        $stmtEvent->execute();
        $resEvent = $stmtEvent->get_result();
        $rowEvent = $resEvent ? $resEvent->fetch_assoc() : null;
        $stmtEvent->close();

        if ($rowEvent) {
            $epoch = (int)($rowEvent['event_time_epoch'] ?? 0);
            if ($epoch > 1000000000000) {
                $epoch = (int)($epoch / 1000);
            }
            $parts = [];
            if (!empty($rowEvent['location_name'])) $parts[] = (string)$rowEvent['location_name'];
            if (!empty($rowEvent['city'])) $parts[] = (string)$rowEvent['city'];
            if (!empty($rowEvent['state_region'])) $parts[] = (string)$rowEvent['state_region'];
            if (!empty($rowEvent['country_code'])) $parts[] = strtoupper((string)$rowEvent['country_code']);

            $event = [
                'id' => (int)$rowEvent['id'],
                'tracking_number' => (string)$rowEvent['tracking_number'],
                'status_text' => (string)($rowEvent['status_text'] ?? 'Shipment update'),
                'event_severity' => strtolower(trim((string)($rowEvent['event_severity'] ?? 'neutral'))),
                'issue_note' => trim((string)($rowEvent['issue_note'] ?? '')),
                'payment_amount' => isset($rowEvent['payment_amount']) ? (float)$rowEvent['payment_amount'] : 0.0,
                'payment_reason' => trim((string)($rowEvent['payment_reason'] ?? '')),
                'date_text' => $epoch > 0 ? date('F j, Y', $epoch) : '-',
                'time_text' => $epoch > 0 ? date('h:i A', $epoch) : '--:--',
                'location_text' => !empty($parts) ? implode(', ', $parts) : '-'
            ];
        } else {
            $has_error = true;
            $error_text = 'Exception event not found for this tracking number.';
        }
    }

    $shipmentSql = "SELECT status, estimated_delivery_time FROM shipments WHERE tracking_number = ? LIMIT 1";
    $stmtShipment = $conn->prepare($shipmentSql);
    if ($stmtShipment) {
        $stmtShipment->bind_param("s", $tracking_id_raw);
        $stmtShipment->execute();
        $resShipment = $stmtShipment->get_result();
        $rowShipment = $resShipment ? $resShipment->fetch_assoc() : null;
        $stmtShipment->close();

        if ($rowShipment) {
            $statusMap = [
                'pending' => 'Label Created',
                'incoming' => 'Shipped',
                'outgoing' => 'Shipped',
                'picked_up' => 'Shipped',
                'in_store' => 'In Transit',
                'shipped' => 'In Transit',
                'in_transit' => 'In Transit',
                'out_for_delivery' => 'Out for Delivery',
                'delivered' => 'Delivered',
                'failed' => 'Exception',
                'cancelled' => 'Cancelled'
            ];
            $statusKey = strtolower(trim((string)($rowShipment['status'] ?? 'in_transit')));
            $shipment_status_text = $statusMap[$statusKey] ?? 'In Transit';
            $etaEpoch = (int)($rowShipment['estimated_delivery_time'] ?? 0);
            if ($etaEpoch > 1000000000000) {
                $etaEpoch = (int)($etaEpoch / 1000);
            }
            if ($etaEpoch > 0) {
                $eta_text = date('F j, Y', $etaEpoch);
            }
        }
    }

    if (!$has_error) {
        $timelineSql = "
            SELECT id, event_time_epoch, status_text, event_severity, location_name, city, state_region, country_code
            FROM shipment_location_events
            WHERE tracking_number = ?
            ORDER BY event_time_epoch DESC, id DESC
            LIMIT 8
        ";
        $stmtTimeline = $conn->prepare($timelineSql);
        if ($stmtTimeline) {
            $stmtTimeline->bind_param("s", $tracking_id_raw);
            $stmtTimeline->execute();
            $resTimeline = $stmtTimeline->get_result();
            if ($resTimeline) {
                while ($row = $resTimeline->fetch_assoc()) {
                    $epoch = (int)($row['event_time_epoch'] ?? 0);
                    if ($epoch > 1000000000000) {
                        $epoch = (int)($epoch / 1000);
                    }
                    $locParts = [];
                    if (!empty($row['location_name'])) $locParts[] = (string)$row['location_name'];
                    if (!empty($row['city'])) $locParts[] = (string)$row['city'];
                    if (!empty($row['state_region'])) $locParts[] = (string)$row['state_region'];
                    if (!empty($row['country_code'])) $locParts[] = strtoupper((string)$row['country_code']);

                    $timeline[] = [
                        'id' => (int)$row['id'],
                        'time_text' => $epoch > 0 ? date('h:i A', $epoch) : '--:--',
                        'date_text' => $epoch > 0 ? date('M j, Y', $epoch) : '-',
                        'status_text' => (string)($row['status_text'] ?? 'Update'),
                        'severity' => strtolower(trim((string)($row['event_severity'] ?? 'neutral'))),
                        'location_text' => !empty($locParts) ? implode(', ', $locParts) : '-'
                    ];
                }
            }
            $stmtTimeline->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipment Exception | Veteran Logistics Group</title>
    <link rel="shortcut icon" href="/assets/images/branding/mark-only.png?v=<?php echo time(); ?>" type="image/png">
    <link rel="stylesheet" href="/assets/stylesheets/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/stylesheets/tracking.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
</head>
<body>
<?php include("../../common-sections/header.html"); ?>

<main class="track-container exception-container">
    <header class="track-header">
        <h1>Shipment Exception</h1>
        <div class="exception-actions">
            <a class="btn-track-back" href="/track/?id=<?= urlencode($tracking_id_raw) ?>">Back to Tracking</a>
            <a class="btn-track-back" href="/support/">Contact Support</a>
        </div>
    </header>

    <?php if ($has_error): ?>
        <section class="main-card exception-focus">
            <h2>Unable to load exception details</h2>
            <p><?= htmlspecialchars($error_text) ?></p>
        </section>
    <?php else: ?>
        <div class="track-grid exception-grid">
            <section class="main-card exception-focus">
                <div class="exception-title-row">
                    <span class="material-symbols-outlined">warning</span>
                    <h2><?= htmlspecialchars((string)$event['status_text']) ?></h2>
                </div>
                <p class="exception-subtext">
                    Tracking Number <strong><?= htmlspecialchars((string)$event['tracking_number']) ?></strong> is currently in an exception state.
                </p>

                <div class="exception-meta-grid">
                    <div>
                        <small>Event Time</small>
                        <strong><?= htmlspecialchars((string)$event['time_text']) ?></strong>
                        <span><?= htmlspecialchars((string)$event['date_text']) ?></span>
                    </div>
                    <div>
                        <small>Location</small>
                        <strong><?= htmlspecialchars((string)$event['location_text']) ?></strong>
                    </div>
                    <div>
                        <small>Current Shipment Status</small>
                        <strong><?= htmlspecialchars($shipment_status_text) ?></strong>
                    </div>
                    <div>
                        <small>Estimated Delivery</small>
                        <strong><?= htmlspecialchars($eta_text !== '' ? $eta_text : 'To be updated') ?></strong>
                    </div>
                </div>

                <div class="exception-note-box">
                    <h3>Issue Details</h3>
                    <p><?= htmlspecialchars($event['issue_note'] !== '' ? (string)$event['issue_note'] : 'No additional issue note has been provided yet.') ?></p>
                </div>

                <?php if (((float)($event['payment_amount'] ?? 0)) > 0): ?>
                    <div class="exception-payment-box">
                        <div class="exception-payment-copy">
                            <h3>Pay to Clarify the Issue</h3>
                            <p><?= htmlspecialchars($event['payment_reason'] !== '' ? (string)$event['payment_reason'] : 'A payment is required before this issue can be clarified.') ?></p>
                        </div>
                        <div class="exception-payment-meta">
                            <span class="exception-payment-amount">$<?= number_format((float)$event['payment_amount'], 2) ?></span>
                    <a class="btn-track-back btn-track-pay" href="/track/exception/pay/?tn=<?= urlencode((string)$event['tracking_number']) ?>&eid=<?= (int)$event['id'] ?>">Pay to clarify the issue</a>
                </div>
                    </div>
                <?php endif; ?>
            </section>

            <section class="history-card">
                <h3>Related Timeline</h3>
                <div class="timeline">
                    <?php foreach ($timeline as $item): ?>
                        <div class="timeline-item <?= $item['severity'] === 'negative' ? 'is-negative' : '' ?> <?= ((int)$item['id'] === (int)$event['id']) ? 'is-selected' : '' ?>">
                            <div class="time-col">
                                <strong><?= htmlspecialchars((string)$item['time_text']) ?></strong>
                                <span><?= htmlspecialchars((string)$item['date_text']) ?></span>
                            </div>
                            <div class="activity-col">
                                <strong><?= htmlspecialchars((string)$item['status_text']) ?></strong>
                                <span><?= htmlspecialchars((string)$item['location_text']) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    <?php endif; ?>
</main>

<?php include("../../common-sections/footer.html"); ?>
</body>
</html>
