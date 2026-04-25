<?php

function cp_mock_table_exists(mysqli $dbconn, string $table): bool {
    $tableEsc = $dbconn->real_escape_string($table);
    $sql = "SHOW TABLES LIKE '{$tableEsc}'";
    $res = $dbconn->query($sql);
    return (bool)($res && $res->num_rows > 0);
}

function cp_mock_fetch_dashboard_data(mysqli $dbconn): array {
    $summary = [
        'users' => 0,
        'shipments' => 0,
        'quotes' => 0,
        'exception_payments' => 0,
        'pending_proofs' => 0,
    ];

    $countQueries = [
        'users' => ['table' => 'users', 'sql' => 'SELECT COUNT(*) AS total FROM users'],
        'shipments' => ['table' => 'shipments', 'sql' => 'SELECT COUNT(*) AS total FROM shipments'],
        'quotes' => ['table' => 'shipment_service_quotes', 'sql' => 'SELECT COUNT(*) AS total FROM shipment_service_quotes'],
        'exception_payments' => ['table' => 'exception_issue_payments', 'sql' => 'SELECT COUNT(*) AS total FROM exception_issue_payments'],
        'pending_proofs' => ['table' => 'shipment_payment_proofs', 'sql' => "SELECT COUNT(*) AS total FROM shipment_payment_proofs WHERE LOWER(COALESCE(status, 'pending_confirmation')) = 'pending_confirmation'"],
    ];

    foreach ($countQueries as $key => $cfg) {
        if (!cp_mock_table_exists($dbconn, $cfg['table'])) {
            continue;
        }
        $res = $dbconn->query($cfg['sql']);
        if ($res && ($row = $res->fetch_assoc())) {
            $summary[$key] = (int)($row['total'] ?? 0);
        }
    }

    $recentShipments = [];
    if (cp_mock_table_exists($dbconn, 'shipments')) {
        $shipmentSql = "
            SELECT id, tracking_number, shipment_type, sender_name, receiver_name, status, created_at_epoch
            FROM shipments
            ORDER BY id DESC
            LIMIT 6
        ";
        $shipmentRes = $dbconn->query($shipmentSql);
        if ($shipmentRes) {
            while ($row = $shipmentRes->fetch_assoc()) {
                $createdEpoch = (int)($row['created_at_epoch'] ?? 0);
                if ($createdEpoch > 1000000000000) {
                    $createdEpoch = (int)($createdEpoch / 1000);
                }
                $recentShipments[] = [
                    'id' => (int)($row['id'] ?? 0),
                    'tracking_number' => (string)($row['tracking_number'] ?? ''),
                    'shipment_type' => (string)($row['shipment_type'] ?? 'standard'),
                    'sender_name' => (string)($row['sender_name'] ?? '-'),
                    'receiver_name' => (string)($row['receiver_name'] ?? '-'),
                    'status' => (string)($row['status'] ?? 'created'),
                    'created_display' => $createdEpoch > 0 ? date('M j, Y H:i', $createdEpoch) : '-',
                ];
            }
        }
    }

    return [
        'summary' => $summary,
        'recent_shipments' => $recentShipments,
    ];
}

function cp_mock_design_options(): array {
    return [
        1 => [
            'title' => 'Design 1 · Sticky total with expandable breakdown',
            'slug' => 'design-1.php',
            'description' => 'Total stays visible while charges expand inline for fast clarity.',
            'tag' => 'Balanced clarity'
        ],
        2 => [
            'title' => 'Design 2 · Always-visible compact summary card',
            'slug' => 'design-2.php',
            'description' => 'Compact rows are always visible with less tapping.',
            'tag' => 'Zero-friction reading'
        ],
        3 => [
            'title' => 'Design 3 · Bottom sheet charges',
            'slug' => 'design-3.php',
            'description' => 'A clean form with full charges inside a mobile-style sheet.',
            'tag' => 'Focused form flow'
        ],
        4 => [
            'title' => 'Design 4 · Common checkout accordion',
            'slug' => 'design-4.php',
            'description' => 'Standard e-commerce pattern with familiar order summary accordion.',
            'tag' => 'Commonly used UX'
        ],
    ];
}
