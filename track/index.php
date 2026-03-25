<?php
require_once __DIR__ . '/../common-sections/globals.php';

$trackRequestPath = (string)($_SERVER['REQUEST_URI'] ?? '/track/');
$trackSignedIn = !empty($_COOKIE['user_email']) || !empty($_COOKIE['user_Email']);
if (!$trackSignedIn) {
    header('Location: /login/?required_login=1&redirect=' . urlencode($trackRequestPath));
    exit();
}

$tracking_id_raw = isset($_GET['id']) ? (string)$_GET['id'] : '1Z999AA10123456784';
$tracking_id = htmlspecialchars($tracking_id_raw);
$statusKey = 'in_transit';
$status = "In Transit";
$progress_percent = 65;
$estimated_delivery_text = "Thursday, March 5, 2026";
$estimated_delivery_hint = "By End of Day";
$history = [];

if (isset($conn) && $conn instanceof mysqli) {
    $shipmentSql = "SELECT status, estimated_delivery_time FROM shipments WHERE tracking_number = ? LIMIT 1";
    $stmtShipment = $conn->prepare($shipmentSql);
    if ($stmtShipment) {
        $stmtShipment->bind_param("s", $tracking_id_raw);
        $stmtShipment->execute();
        $shipmentRes = $stmtShipment->get_result();
        $shipmentRow = $shipmentRes ? $shipmentRes->fetch_assoc() : null;
        $stmtShipment->close();

        if ($shipmentRow) {
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
            $statusKey = strtolower(trim((string)($shipmentRow['status'] ?? 'in_transit')));
            $status = $statusMap[$statusKey] ?? 'In Transit';

            $progressMap = [
                'pending' => 10,
                'incoming' => 25,
                'outgoing' => 25,
                'picked_up' => 30,
                'in_store' => 45,
                'shipped' => 55,
                'in_transit' => 65,
                'out_for_delivery' => 85,
                'delivered' => 100,
                'failed' => 65,
                'cancelled' => 10
            ];
            $progress_percent = $progressMap[$statusKey] ?? 65;

            $etaEpoch = (int)($shipmentRow['estimated_delivery_time'] ?? 0);
            if ($etaEpoch > 0) {
                if ($etaEpoch > 1000000000000) {
                    $etaEpoch = (int)($etaEpoch / 1000);
                }
                $estimated_delivery_text = date("l, F j, Y", $etaEpoch);
            }
        }
    }

    $eventsSql = "
        SELECT id, event_time_epoch, status_text, city, state_region, country_code, location_name, event_severity, issue_note
        FROM shipment_location_events
        WHERE tracking_number = ?
        ORDER BY event_time_epoch DESC, id DESC
        LIMIT 25
    ";
    $stmtEvents = $conn->prepare($eventsSql);
    if ($stmtEvents) {
        $stmtEvents->bind_param("s", $tracking_id_raw);
        $stmtEvents->execute();
        $eventsRes = $stmtEvents->get_result();
        if ($eventsRes) {
            while ($row = $eventsRes->fetch_assoc()) {
                $epoch = (int)($row['event_time_epoch'] ?? 0);
                if ($epoch > 1000000000000) {
                    $epoch = (int)($epoch / 1000);
                }

                $pieces = [];
                if (!empty($row['location_name'])) $pieces[] = (string)$row['location_name'];
                if (!empty($row['city'])) $pieces[] = (string)$row['city'];
                if (!empty($row['state_region'])) $pieces[] = (string)$row['state_region'];
                if (!empty($row['country_code'])) $pieces[] = strtoupper((string)$row['country_code']);
                $locationText = implode(', ', $pieces);

                $severity = strtolower(trim((string)($row['event_severity'] ?? 'neutral')));
                $isNegative = ($severity === 'negative');

                $history[] = [
                    "event_id" => (int)($row['id'] ?? 0),
                    "time" => $epoch > 0 ? date("h:i A", $epoch) : "--:--",
                    "date" => $epoch > 0 ? date("M j, Y", $epoch) : "-",
                    "location" => $locationText !== '' ? $locationText : "-",
                    "activity" => (string)($row['status_text'] ?? 'Update'),
                    "is_negative" => $isNegative,
                    "issue_note" => (string)($row['issue_note'] ?? '')
                ];
            }
        }
        $stmtEvents->close();
    }
}

if (empty($history)) {
    $history = [
        ["event_id" => 0, "time" => "10:30 AM", "date" => "Mar 2, 2026", "location" => "Port Harcourt, NG", "activity" => "Arrived at Facility", "is_negative" => false, "issue_note" => ""],
        ["event_id" => 0, "time" => "08:15 AM", "date" => "Mar 2, 2026", "location" => "Lagos, NG", "activity" => "Departed from Facility", "is_negative" => false, "issue_note" => ""],
        ["event_id" => 0, "time" => "04:00 PM", "date" => "Mar 1, 2026", "location" => "Lagos, NG", "activity" => "Processed at UPS Facility", "is_negative" => false, "issue_note" => ""],
        ["event_id" => 0, "time" => "11:00 AM", "date" => "Mar 1, 2026", "location" => "Lagos, NG", "activity" => "Shipped / Picked Up", "is_negative" => false, "issue_note" => ""]
    ];
}

$progress_nodes = [
    ['label' => 'Label Created', 'icon' => 'package_2', 'state' => 'pending'],
    ['label' => 'In Transit', 'icon' => 'local_shipping', 'state' => 'pending'],
    ['label' => 'Delivered', 'icon' => 'inventory_2', 'state' => 'pending'],
];

switch ($statusKey) {
    case 'pending':
        $progress_percent = 0;
        $progress_nodes[0]['state'] = 'active';
        $estimated_delivery_hint = 'Shipment information received';
        break;

    case 'incoming':
    case 'outgoing':
    case 'picked_up':
        $progress_percent = 18;
        $progress_nodes[0]['label'] = 'Shipped';
        $progress_nodes[0]['state'] = 'active';
        $estimated_delivery_hint = 'Picked up and moving';
        break;

    case 'in_store':
    case 'shipped':
    case 'in_transit':
        $progress_percent = 64;
        $progress_nodes[0]['label'] = 'Shipped';
        $progress_nodes[0]['state'] = 'done';
        $progress_nodes[1]['state'] = 'active';
        $estimated_delivery_hint = 'By End of Day';
        break;

    case 'out_for_delivery':
        $progress_percent = 82;
        $progress_nodes[0]['label'] = 'Shipped';
        $progress_nodes[0]['state'] = 'done';
        $progress_nodes[1]['label'] = 'Out for Delivery';
        $progress_nodes[1]['state'] = 'active';
        $estimated_delivery_hint = 'Expected today';
        break;

    case 'delivered':
        $progress_percent = 100;
        $progress_nodes[0]['label'] = 'Shipped';
        $progress_nodes[0]['state'] = 'done';
        $progress_nodes[1]['state'] = 'done';
        $progress_nodes[2]['state'] = 'active';
        $estimated_delivery_hint = 'Delivered';
        break;

    case 'failed':
        $progress_percent = 64;
        $progress_nodes[0]['label'] = 'Shipped';
        $progress_nodes[0]['state'] = 'done';
        $progress_nodes[1]['label'] = 'Exception';
        $progress_nodes[1]['state'] = 'active';
        $estimated_delivery_hint = 'Delivery update required';
        break;

    case 'cancelled':
        $progress_percent = 0;
        $progress_nodes[0]['state'] = 'active';
        $estimated_delivery_hint = 'Shipment cancelled';
        break;

    default:
        $progress_percent = 64;
        $progress_nodes[0]['label'] = 'Shipped';
        $progress_nodes[0]['state'] = 'done';
        $progress_nodes[1]['state'] = 'active';
        $estimated_delivery_hint = 'By End of Day';
        break;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Shipment | TK Pro Design</title>
    <link rel="shortcut icon" href="/assets/images/branding/mark-only.png?v=<?php echo time(); ?>" type="image/png">
    <link rel="stylesheet" href="/assets/stylesheets/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/stylesheets/tracking.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
</head>
<body>
<?php include("../common-sections/header.html"); ?>
    <main class="track-container">
        <header class="track-header">
            <h1>Tracking</h1>
            <form class="search-bar" method="get" action="/track/">
                <input type="text" name="id" placeholder="Tracking Number" value="<?= $tracking_id ?>" required>
                <button class="btn-track" type="submit">Track</button>
            </form>
        </header>

        <div class="track-grid">
            <section class="main-card">
                <div class="status-header">
                    <div class="id-group">
                        <span>Tracking Number</span>
                        <strong><?= $tracking_id ?></strong>
                    </div>
                    <div class="status-badge <?= str_replace(' ', '-', strtolower($status)) ?>">
                        <?= $status ?>
                    </div>
                </div>

                <div class="tracking-visual">
                    <div class="progress-line">
                        <div class="fill" style="width: <?= $progress_percent ?>%;"></div>
                    </div>
                    <div class="nodes">
                        <?php foreach ($progress_nodes as $node): ?>
                            <div class="node <?= htmlspecialchars($node['state']) ?>">
                                <i class="material-symbols-outlined"><?= htmlspecialchars($node['icon']) ?></i>
                                <span><?= htmlspecialchars($node['label']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="estimated-delivery">
                    <p>Estimated Delivery</p>
                    <h2><?= htmlspecialchars($estimated_delivery_text) ?></h2>
                    <span><?= htmlspecialchars($estimated_delivery_hint) ?></span>
                </div>
            </section>

            <section class="history-card">
                <h3>Detailed History</h3>
                <div class="timeline">
                    <?php foreach($history as $event): ?>
                        <div class="timeline-item <?= !empty($event['is_negative']) ? 'is-negative' : '' ?>">
                            <div class="time-col">
                                <strong><?= htmlspecialchars((string)$event['time']) ?></strong>
                                <span><?= htmlspecialchars((string)$event['date']) ?></span>
                            </div>
                            <div class="activity-col">
                                <strong><?= htmlspecialchars((string)$event['activity']) ?></strong>
                                <span><?= htmlspecialchars((string)$event['location']) ?></span>
                                <?php if (!empty($event['is_negative'])): ?>
                                    <a
                                        class="urgent-cta"
                                        href="/track/exception/?tn=<?= urlencode($tracking_id_raw) ?>&eid=<?= (int)($event['event_id'] ?? 0) ?>"
                                    >
                                        <span class="material-symbols-outlined" aria-hidden="true">warning</span>
                                        Click for more details
                                    </a>
                                    <?php if (!empty($event['issue_note'])): ?>
                                        <span class="issue-note"><?= htmlspecialchars((string)$event['issue_note']) ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>
<?php include("../common-sections/footer.html"); ?>
</body>
</html>
