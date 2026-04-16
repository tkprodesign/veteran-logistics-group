<?php
require_once __DIR__ . '/../common-sections/globals.php';

$tracking_id_raw = isset($_GET['id']) ? (string)$_GET['id'] : '1Z999AA10123456784';
$tracking_id = htmlspecialchars($tracking_id_raw);
$status = "In Transit";
$progress_percent = 65;
$estimated_delivery_text = "Thursday, March 5, 2026";
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
        SELECT event_time_epoch, status_text, city, state_region, country_code, location_name, event_severity, issue_note, negative_event_paid
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
                $isNegativePaid = (int)($row['negative_event_paid'] ?? 0) === 1;

                $history[] = [
                    "time" => $epoch > 0 ? date("h:i A", $epoch) : "--:--",
                    "date" => $epoch > 0 ? date("M j, Y", $epoch) : "-",
                    "location" => $locationText !== '' ? $locationText : "-",
                    "activity" => (string)($row['status_text'] ?? 'Update'),
                    "is_negative" => ($isNegative && !$isNegativePaid),
                    "is_negative_paid" => $isNegativePaid,
                    "issue_note" => (string)($row['issue_note'] ?? '')
                ];
            }
        }
        $stmtEvents->close();
    }
}

if (empty($history)) {
    $history = [
        ["time" => "10:30 AM", "date" => "Mar 2, 2026", "location" => "Port Harcourt, NG", "activity" => "Arrived at Facility", "is_negative" => false, "issue_note" => ""],
        ["time" => "08:15 AM", "date" => "Mar 2, 2026", "location" => "Lagos, NG", "activity" => "Departed from Facility", "is_negative" => false, "issue_note" => ""],
        ["time" => "04:00 PM", "date" => "Mar 1, 2026", "location" => "Lagos, NG", "activity" => "Processed at UPS Facility", "is_negative" => false, "issue_note" => ""],
        ["time" => "11:00 AM", "date" => "Mar 1, 2026", "location" => "Lagos, NG", "activity" => "Shipped / Picked Up", "is_negative" => false, "issue_note" => ""]
    ];
}
