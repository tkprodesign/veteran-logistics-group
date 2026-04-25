<?php
declare(strict_types=1);

/**
 * Data cleanup + ID resequencing risk audit.
 *
 * Run:
 *   php tools/data_cleanup_audit.php
 */

require_once __DIR__ . '/../common-sections/globals.php';

if (!isset($conn) || !($conn instanceof mysqli) || !empty($conn->connect_error)) {
    fwrite(STDERR, "Database connection is not available.\n");
    exit(1);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset('utf8mb4');

$dbResult = $conn->query("SELECT DATABASE() AS db_name");
$dbRow = $dbResult ? $dbResult->fetch_assoc() : null;
$dbName = (string)($dbRow['db_name'] ?? '');
if ($dbName === '') {
    fwrite(STDERR, "Could not determine active database.\n");
    exit(1);
}

function println(string $line = ''): void {
    echo $line . PHP_EOL;
}

function tableExists(mysqli $conn, string $dbName, string $tableName): bool {
    $stmt = $conn->prepare(
        "SELECT 1
         FROM information_schema.tables
         WHERE table_schema = ?
           AND table_name = ?
         LIMIT 1"
    );
    $stmt->bind_param('ss', $dbName, $tableName);
    $stmt->execute();
    $res = $stmt->get_result();
    $exists = (bool)($res && $res->fetch_row());
    $stmt->close();
    return $exists;
}

function gatherAutoIncrementTables(mysqli $conn, string $dbName): array {
    $sql = "SELECT table_name, auto_increment
            FROM information_schema.tables
            WHERE table_schema = ?
              AND table_type = 'BASE TABLE'
            ORDER BY table_name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $dbName);
    $stmt->execute();
    $res = $stmt->get_result();

    $tables = [];
    while ($row = $res->fetch_assoc()) {
        $tableName = (string)$row['table_name'];

        $pkSql = "SELECT column_name, data_type, extra
                  FROM information_schema.columns
                  WHERE table_schema = ?
                    AND table_name = ?
                    AND column_key = 'PRI'
                  ORDER BY ordinal_position";
        $pkStmt = $conn->prepare($pkSql);
        $pkStmt->bind_param('ss', $dbName, $tableName);
        $pkStmt->execute();
        $pkRes = $pkStmt->get_result();

        $pkColumns = [];
        $idPkAuto = false;
        while ($pk = $pkRes->fetch_assoc()) {
            $col = (string)$pk['column_name'];
            $pkColumns[] = $col;
            if ($col === 'id' && stripos((string)$pk['extra'], 'auto_increment') !== false) {
                $idPkAuto = true;
            }
        }
        $pkStmt->close();

        $tables[$tableName] = [
            'pk_columns' => $pkColumns,
            'id_pk_auto' => $idPkAuto,
            'next_auto_increment' => isset($row['auto_increment']) ? (int)$row['auto_increment'] : null,
        ];
    }

    return $tables;
}

function countRows(mysqli $conn, string $tableName): int {
    $sql = "SELECT COUNT(*) AS total FROM `{$tableName}`";
    $res = $conn->query($sql);
    $row = $res ? $res->fetch_assoc() : ['total' => 0];
    return (int)($row['total'] ?? 0);
}

function idStats(mysqli $conn, string $tableName): array {
    $sql = "SELECT COUNT(*) AS total, MIN(id) AS min_id, MAX(id) AS max_id FROM `{$tableName}`";
    $res = $conn->query($sql);
    $row = $res ? $res->fetch_assoc() : ['total' => 0, 'min_id' => null, 'max_id' => null];

    $total = (int)($row['total'] ?? 0);
    $minId = isset($row['min_id']) ? (int)$row['min_id'] : null;
    $maxId = isset($row['max_id']) ? (int)$row['max_id'] : null;
    $gaps = 0;

    if ($total > 0 && $minId !== null && $maxId !== null) {
        $range = $maxId - $minId + 1;
        $gaps = max(0, $range - $total);
    }

    return [
        'total' => $total,
        'min_id' => $minId,
        'max_id' => $maxId,
        'gap_count' => $gaps,
    ];
}

function discoverIdLikeReferences(mysqli $conn, string $dbName): array {
    $sql = "SELECT table_name, column_name
            FROM information_schema.columns
            WHERE table_schema = ?
              AND column_name LIKE '%\\_id' ESCAPE '\\\\'
              AND column_name <> 'id'
            ORDER BY table_name, ordinal_position";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $dbName);
    $stmt->execute();
    $res = $stmt->get_result();

    $manualTargets = [
        'user_id' => 'users',
        'shipment_id' => 'shipments',
        'event_id' => 'shipment_location_events',
        'payment_method_id' => 'payment_methods',
        'quote_id' => 'shipment_service_quotes',
        'order_id' => 'shipments',
    ];

    $edges = [];
    while ($row = $res->fetch_assoc()) {
        $source = (string)$row['table_name'];
        $column = (string)$row['column_name'];

        $target = $manualTargets[$column] ?? '';
        if ($target === '') {
            $base = substr($column, 0, -3);
            $candidates = [$base, $base . 's'];
            foreach ($candidates as $candidate) {
                if (tableExists($conn, $dbName, $candidate)) {
                    $target = $candidate;
                    break;
                }
            }
        }

        $edges[] = [
            'source' => $source,
            'column' => $column,
            'target' => $target,
        ];
    }

    $stmt->close();
    return $edges;
}

function classifyRisk(string $table, array $edges): string {
    $incoming = 0;
    $outgoing = 0;

    foreach ($edges as $edge) {
        if ($edge['target'] === $table) {
            $incoming++;
        }
        if ($edge['source'] === $table) {
            $outgoing++;
        }
    }

    if ($incoming > 0) {
        return 'HARD_TO_TOUCH';
    }

    if ($outgoing > 0) {
        return 'MEDIUM_TOUCH';
    }

    return 'COOL_TO_TOUCH';
}

println("Data cleanup audit for database: {$dbName}");
println(str_repeat('=', 72));
println();

$meetingTables = ['free_quotes_requests', 'quotes', 'shipment_service_quotes'];
println('1) Potential "meeting with us" counts (by likely intent):');
foreach ($meetingTables as $tableName) {
    if (!tableExists($conn, $dbName, $tableName)) {
        println("- {$tableName}: table not found");
        continue;
    }
    $count = countRows($conn, $tableName);
    println("- {$tableName}: {$count}");
}
println();

println('2) ID gap scan (tables with AUTO_INCREMENT primary key `id`):');
$tables = gatherAutoIncrementTables($conn, $dbName);
foreach ($tables as $tableName => $meta) {
    if (!$meta['id_pk_auto']) {
        continue;
    }
    $stats = idStats($conn, $tableName);
    $nextAi = $meta['next_auto_increment'];

    println(
        sprintf(
            '- %s: rows=%d, min_id=%s, max_id=%s, gaps=%d, next_auto_increment=%s',
            $tableName,
            $stats['total'],
            $stats['min_id'] === null ? 'NULL' : (string)$stats['min_id'],
            $stats['max_id'] === null ? 'NULL' : (string)$stats['max_id'],
            $stats['gap_count'],
            $nextAi === null ? 'NULL' : (string)$nextAi
        )
    );
}
println();

println('3) ID-like table relationships (inferred from *_id columns):');
$edges = discoverIdLikeReferences($conn, $dbName);
foreach ($edges as $edge) {
    $target = $edge['target'] !== '' ? $edge['target'] : '(unknown target)';
    println("- {$edge['source']}.{$edge['column']} -> {$target}.id");
}
println();

println('4) Resequencing risk classification:');
$allTableNames = array_keys($tables);
sort($allTableNames);
foreach ($allTableNames as $tableName) {
    $risk = classifyRisk($tableName, $edges);
    println("- {$tableName}: {$risk}");
}
println();

println('5) Recommendation:');
println('- Do NOT resequence primary IDs on production data.');
println('- Safe cleanup approach: DELETE unwanted rows, then set AUTO_INCREMENT to MAX(id)+1.');
println('- If you must resequence IDs, do it only on isolated tables with COOL_TO_TOUCH and full backup.');
println('- Re-sequencing HARD_TO_TOUCH tables will likely break related rows in other tables.');
println();

println('Suggested SQL pattern after deletions (for one table):');
println('  SET @next_id := (SELECT COALESCE(MAX(id), 0) + 1 FROM your_table);');
println('  SET @sql := CONCAT("ALTER TABLE your_table AUTO_INCREMENT = ", @next_id);');
println('  PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;');
