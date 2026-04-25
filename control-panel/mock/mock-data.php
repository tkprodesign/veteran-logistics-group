<?php

function cp_mock_table_exists(mysqli $dbconn, string $table): bool {
    $tableEsc = $dbconn->real_escape_string($table);
    $sql = "SHOW TABLES LIKE '{$tableEsc}'";
    $res = $dbconn->query($sql);
    return (bool)($res && $res->num_rows > 0);
}

function cp_mock_table_columns(mysqli $dbconn, string $table): array {
    $columns = [];
    $tableEsc = $dbconn->real_escape_string($table);
    $res = $dbconn->query("SHOW COLUMNS FROM `{$tableEsc}`");
    if (!$res) {
        return $columns;
    }
    while ($row = $res->fetch_assoc()) {
        $field = strtolower(trim((string)($row['Field'] ?? '')));
        if ($field !== '') {
            $columns[$field] = true;
        }
    }
    return $columns;
}

function cp_mock_has_column(array $columns, string $column): bool {
    return isset($columns[strtolower($column)]);
}

function cp_mock_parse_epochish($raw): int {
    if ($raw === null || $raw === '') {
        return 0;
    }
    if (is_numeric((string)$raw)) {
        $epoch = (int)$raw;
        if ($epoch > 1000000000000) {
            $epoch = (int)($epoch / 1000);
        }
        return $epoch > 0 ? $epoch : 0;
    }
    $parsed = strtotime((string)$raw);
    return ($parsed !== false && $parsed > 0) ? (int)$parsed : 0;
}

function cp_mock_epoch_display($raw): string {
    $epoch = cp_mock_parse_epochish($raw);
    return $epoch > 0 ? date('M j, Y H:i', $epoch) : '-';
}

function cp_mock_pad_rows(array $rows, array $blankRow, int $maxRows = 10): array {
    $padded = array_slice($rows, 0, $maxRows);
    while (count($padded) < $maxRows) {
        $next = $blankRow;
        $next['_is_placeholder'] = true;
        $padded[] = $next;
    }
    return $padded;
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
    ];

    foreach ($countQueries as $key => $cfg) {
        if (!cp_mock_table_exists($dbconn, $cfg['table'])) {
            continue;
        }
        try {
            $res = $dbconn->query($cfg['sql']);
            if ($res && ($row = $res->fetch_assoc())) {
                $summary[$key] = (int)($row['total'] ?? 0);
            }
        } catch (Throwable $e) {
            // Ignore schema mismatch issues in mock pages.
        }
    }

    if (cp_mock_table_exists($dbconn, 'shipment_payment_proofs')) {
        $proofColumns = cp_mock_table_columns($dbconn, 'shipment_payment_proofs');
        $proofSql = cp_mock_has_column($proofColumns, 'status')
            ? "SELECT COUNT(*) AS total FROM shipment_payment_proofs WHERE LOWER(COALESCE(status, 'pending_confirmation')) = 'pending_confirmation'"
            : "SELECT COUNT(*) AS total FROM shipment_payment_proofs";
        try {
            $proofRes = $dbconn->query($proofSql);
            if ($proofRes && ($proofRow = $proofRes->fetch_assoc())) {
                $summary['pending_proofs'] = (int)($proofRow['total'] ?? 0);
            }
        } catch (Throwable $e) {
            // Ignore schema mismatch issues in mock pages.
        }
    }

    $recentShipments = [];
    if (cp_mock_table_exists($dbconn, 'shipments')) {
        $shipmentColumns = cp_mock_table_columns($dbconn, 'shipments');
        $idSelect = cp_mock_has_column($shipmentColumns, 'id') ? 'id' : '0 AS id';
        $trackingSelect = cp_mock_has_column($shipmentColumns, 'tracking_number') ? 'tracking_number' : "'' AS tracking_number";
        $typeSelect = cp_mock_has_column($shipmentColumns, 'shipment_type') ? 'shipment_type' : "'standard' AS shipment_type";
        $senderSelect = cp_mock_has_column($shipmentColumns, 'sender_name') ? 'sender_name' : "'-' AS sender_name";
        $receiverSelect = cp_mock_has_column($shipmentColumns, 'receiver_name') ? 'receiver_name' : "'-' AS receiver_name";
        $statusSelect = cp_mock_has_column($shipmentColumns, 'status') ? 'status' : "'created' AS status";

        $createdOrderColumn = '';
        if (cp_mock_has_column($shipmentColumns, 'created_at_epoch')) {
            $createdSelect = 'created_at_epoch AS mock_created_epoch';
            $createdOrderColumn = 'created_at_epoch';
        } elseif (cp_mock_has_column($shipmentColumns, 'created_epoch')) {
            $createdSelect = 'created_epoch AS mock_created_epoch';
            $createdOrderColumn = 'created_epoch';
        } elseif (cp_mock_has_column($shipmentColumns, 'created_at')) {
            $createdSelect = 'UNIX_TIMESTAMP(created_at) AS mock_created_epoch';
            $createdOrderColumn = 'created_at';
        } elseif (cp_mock_has_column($shipmentColumns, 'created_on')) {
            $createdSelect = 'UNIX_TIMESTAMP(created_on) AS mock_created_epoch';
            $createdOrderColumn = 'created_on';
        } else {
            $createdSelect = '0 AS mock_created_epoch';
        }

        $orderBy = cp_mock_has_column($shipmentColumns, 'id')
            ? 'ORDER BY id DESC'
            : (($createdOrderColumn !== '') ? "ORDER BY {$createdOrderColumn} DESC" : '');

        $shipmentSql = "
            SELECT {$idSelect}, {$trackingSelect}, {$typeSelect}, {$senderSelect}, {$receiverSelect}, {$statusSelect}, {$createdSelect}
            FROM shipments
            {$orderBy}
            LIMIT 6
        ";
        try {
            $shipmentRes = $dbconn->query($shipmentSql);
            if ($shipmentRes) {
                while ($row = $shipmentRes->fetch_assoc()) {
                    $createdEpoch = (int)($row['mock_created_epoch'] ?? 0);
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
        } catch (Throwable $e) {
            // Ignore schema mismatch issues in mock pages.
        }
    }

    $lists = [
        'users' => [],
        'shipments' => [],
        'quotes' => [],
        'exception_payments' => [],
    ];

    if (cp_mock_table_exists($dbconn, 'users')) {
        $userColumns = cp_mock_table_columns($dbconn, 'users');
        $userSql = "
            SELECT
                " . (cp_mock_has_column($userColumns, 'id') ? 'id' : '0') . " AS id,
                " . (cp_mock_has_column($userColumns, 'name') ? 'name' : "'-'") . " AS name,
                " . (cp_mock_has_column($userColumns, 'email') ? 'email' : "'-'") . " AS email,
                " . (cp_mock_has_column($userColumns, 'username') ? 'username' : "'-'") . " AS username,
                " . (cp_mock_has_column($userColumns, 'phone_number') ? 'phone_number' : "'-'") . " AS phone_number,
                " . (cp_mock_has_column($userColumns, 'created_at') ? 'created_at' : '0') . " AS created_at
            FROM users
            " . (cp_mock_has_column($userColumns, 'id') ? 'ORDER BY id DESC' : '') . "
            LIMIT 10
        ";
        try {
            $userRes = $dbconn->query($userSql);
            if ($userRes) {
                while ($row = $userRes->fetch_assoc()) {
                    $lists['users'][] = [
                        'ID' => (string)((int)($row['id'] ?? 0)),
                        'Name' => (string)($row['name'] ?? '-'),
                        'Email' => (string)($row['email'] ?? '-'),
                        'Username' => (string)($row['username'] ?? '-'),
                        'Phone' => (string)($row['phone_number'] ?? '-'),
                        'Joined' => cp_mock_epoch_display($row['created_at'] ?? 0),
                    ];
                }
            }
        } catch (Throwable $e) {
            // Ignore mock-only schema mismatch issues.
        }
    }

    if (cp_mock_table_exists($dbconn, 'shipments')) {
        $shipmentColumns = cp_mock_table_columns($dbconn, 'shipments');
        $shipmentListSql = "
            SELECT
                " . (cp_mock_has_column($shipmentColumns, 'id') ? 'id' : '0') . " AS id,
                " . (cp_mock_has_column($shipmentColumns, 'tracking_number') ? 'tracking_number' : "'-'") . " AS tracking_number,
                " . (cp_mock_has_column($shipmentColumns, 'status') ? 'status' : "'created'") . " AS status,
                " . (cp_mock_has_column($shipmentColumns, 'sender_name') ? 'sender_name' : "'-'") . " AS sender_name,
                " . (cp_mock_has_column($shipmentColumns, 'receiver_name') ? 'receiver_name' : "'-'") . " AS receiver_name,
                " . (cp_mock_has_column($shipmentColumns, 'date_created') ? 'date_created' : (cp_mock_has_column($shipmentColumns, 'created_at_epoch') ? 'created_at_epoch' : (cp_mock_has_column($shipmentColumns, 'created_at') ? 'created_at' : '0'))) . " AS created_value
            FROM shipments
            " . (cp_mock_has_column($shipmentColumns, 'id') ? 'ORDER BY id DESC' : '') . "
            LIMIT 10
        ";
        try {
            $shipmentListRes = $dbconn->query($shipmentListSql);
            if ($shipmentListRes) {
                while ($row = $shipmentListRes->fetch_assoc()) {
                    $lists['shipments'][] = [
                        'ID' => (string)((int)($row['id'] ?? 0)),
                        'Tracking' => (string)($row['tracking_number'] ?? '-'),
                        'Status' => (string)($row['status'] ?? 'created'),
                        'Sender' => (string)($row['sender_name'] ?? '-'),
                        'Receiver' => (string)($row['receiver_name'] ?? '-'),
                        'Created' => cp_mock_epoch_display($row['created_value'] ?? 0),
                    ];
                }
            }
        } catch (Throwable $e) {
            // Ignore mock-only schema mismatch issues.
        }
    }

    if (cp_mock_table_exists($dbconn, 'shipment_service_quotes')) {
        $quoteColumns = cp_mock_table_columns($dbconn, 'shipment_service_quotes');
        $quoteSql = "
            SELECT
                " . (cp_mock_has_column($quoteColumns, 'id') ? 'id' : '0') . " AS id,
                " . (cp_mock_has_column($quoteColumns, 'user_id') ? 'user_id' : '0') . " AS user_id,
                " . (cp_mock_has_column($quoteColumns, 'service_level') ? 'service_level' : "'-'") . " AS service_level,
                " . (cp_mock_has_column($quoteColumns, 'processing_status') ? 'processing_status' : "'-'") . " AS processing_status,
                " . (cp_mock_has_column($quoteColumns, 'price') ? 'price' : '0') . " AS price,
                " . (cp_mock_has_column($quoteColumns, 'created_at_epoch') ? 'created_at_epoch' : (cp_mock_has_column($quoteColumns, 'created_at') ? 'created_at' : '0')) . " AS created_value
            FROM shipment_service_quotes
            " . (cp_mock_has_column($quoteColumns, 'id') ? 'ORDER BY id DESC' : '') . "
            LIMIT 10
        ";
        try {
            $quoteRes = $dbconn->query($quoteSql);
            if ($quoteRes) {
                while ($row = $quoteRes->fetch_assoc()) {
                    $lists['quotes'][] = [
                        'ID' => (string)((int)($row['id'] ?? 0)),
                        'User ID' => (string)((int)($row['user_id'] ?? 0)),
                        'Service' => (string)($row['service_level'] ?? '-'),
                        'Status' => (string)($row['processing_status'] ?? '-'),
                        'Price' => '$' . number_format((float)($row['price'] ?? 0), 2),
                        'Created' => cp_mock_epoch_display($row['created_value'] ?? 0),
                    ];
                }
            }
        } catch (Throwable $e) {
            // Ignore mock-only schema mismatch issues.
        }
    }

    if (cp_mock_table_exists($dbconn, 'exception_issue_payments')) {
        $exceptionColumns = cp_mock_table_columns($dbconn, 'exception_issue_payments');
        $exceptionSql = "
            SELECT
                " . (cp_mock_has_column($exceptionColumns, 'id') ? 'id' : '0') . " AS id,
                " . (cp_mock_has_column($exceptionColumns, 'tracking_number') ? 'tracking_number' : "'-'") . " AS tracking_number,
                " . (cp_mock_has_column($exceptionColumns, 'name') ? 'name' : "'-'") . " AS name,
                " . (cp_mock_has_column($exceptionColumns, 'amount') ? 'amount' : '0') . " AS amount,
                " . (cp_mock_has_column($exceptionColumns, 'payment_method') ? 'payment_method' : "'card'") . " AS payment_method,
                " . (cp_mock_has_column($exceptionColumns, 'status') ? 'status' : "'pending_confirmation'") . " AS status,
                " . (cp_mock_has_column($exceptionColumns, 'created_at_epoch') ? 'created_at_epoch' : (cp_mock_has_column($exceptionColumns, 'created_at') ? 'created_at' : '0')) . " AS created_value
            FROM exception_issue_payments
            " . (cp_mock_has_column($exceptionColumns, 'id') ? 'ORDER BY id DESC' : '') . "
            LIMIT 10
        ";
        try {
            $exceptionRes = $dbconn->query($exceptionSql);
            if ($exceptionRes) {
                while ($row = $exceptionRes->fetch_assoc()) {
                    $method = strtolower((string)($row['payment_method'] ?? 'card')) === 'crypto' ? 'Other Payment Methods' : 'Payment Card';
                    $lists['exception_payments'][] = [
                        'ID' => (string)((int)($row['id'] ?? 0)),
                        'Tracking' => (string)($row['tracking_number'] ?? '-'),
                        'Name' => (string)($row['name'] ?? '-'),
                        'Amount' => '$' . number_format((float)($row['amount'] ?? 0), 2),
                        'Method' => $method,
                        'Status' => (string)($row['status'] ?? 'pending_confirmation'),
                        'Created' => cp_mock_epoch_display($row['created_value'] ?? 0),
                    ];
                }
            }
        } catch (Throwable $e) {
            // Ignore mock-only schema mismatch issues.
        }
    }

    $blankUserRow = ['ID' => '-', 'Name' => '-', 'Email' => '-', 'Username' => '-', 'Phone' => '-', 'Joined' => '-'];
    $blankShipmentRow = ['ID' => '-', 'Tracking' => '-', 'Status' => '-', 'Sender' => '-', 'Receiver' => '-', 'Created' => '-'];
    $blankQuoteRow = ['ID' => '-', 'User ID' => '-', 'Service' => '-', 'Status' => '-', 'Price' => '-', 'Created' => '-'];
    $blankExceptionRow = ['ID' => '-', 'Tracking' => '-', 'Name' => '-', 'Amount' => '-', 'Method' => '-', 'Status' => '-', 'Created' => '-'];

    $lists['users'] = cp_mock_pad_rows($lists['users'], $blankUserRow, 10);
    $lists['shipments'] = cp_mock_pad_rows($lists['shipments'], $blankShipmentRow, 10);
    $lists['quotes'] = cp_mock_pad_rows($lists['quotes'], $blankQuoteRow, 10);
    $lists['exception_payments'] = cp_mock_pad_rows($lists['exception_payments'], $blankExceptionRow, 10);

    return [
        'summary' => $summary,
        'recent_shipments' => $recentShipments,
        'lists' => $lists,
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
