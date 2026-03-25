diff --git a/c:\Users\USER\Downloads\consignment\track/index.php b/c:\Users\USER\Downloads\consignment\track/index.php
--- a/c:\Users\USER\Downloads\consignment\track/index.php
+++ b/c:\Users\USER\Downloads\consignment\track/index.php
@@ -1,14 +1,109 @@
-<?php
-// Mock Data for "TK Pro Design" demonstration
-$tracking_id = isset($_GET['id']) ? htmlspecialchars($_GET['id']) : '1Z999AA10123456784';
-$status = "In Transit"; // Options: Label Created, Shipped, In Transit, Out for Delivery, Delivered
-$progress_percent = 65; 
-
-$history = [
-    ["time" => "10:30 AM", "date" => "Mar 2, 2026", "location" => "Port Harcourt, NG", "activity" => "Arrived at Facility"],
-    ["time" => "08:15 AM", "date" => "Mar 2, 2026", "location" => "Lagos, NG", "activity" => "Departed from Facility"],
-    ["time" => "04:00 PM", "date" => "Mar 1, 2026", "location" => "Lagos, NG", "activity" => "Processed at UPS Facility"],
-    ["time" => "11:00 AM", "date" => "Mar 1, 2026", "location" => "Lagos, NG", "activity" => "Shipped / Picked Up"]
-];
-?>
+<?php
+require_once __DIR__ . '/../common-sections/globals.php';
+
+date_default_timezone_set('America/New_York');
+
+function epoch_to_seconds($t) {
+    if ($t === null || $t === '') {
+        return null;
+    }
+    $t = (int)$t;
+    return ($t > 1000000000000) ? (int)($t / 1000) : $t;
+}
+
+$tracking_input = isset($_GET['id']) ? trim((string)$_GET['id']) : '';
+$tracking_id = preg_replace('/[^A-Za-z0-9\-]/', '', $tracking_input);
+
+$shipment = null;
+$history = [];
+$status = 'Not Found';
+$progress_percent = 10;
+$estimated_delivery_label = 'Not available';
+$shipment_id = 0;
+$status_key = '';
+
+if ($tracking_id !== '') {
+    $stmt = $conn->prepare("
+        SELECT id, tracking_number, status, completion_percentage, estimated_delivery_time
+        FROM shipments
+        WHERE tracking_number = ?
+        LIMIT 1
+    ");
+
+    if ($stmt) {
+        $stmt->bind_param("s", $tracking_id);
+        $stmt->execute();
+        $res = $stmt->get_result();
+        $shipment = $res ? $res->fetch_assoc() : null;
+        $stmt->close();
+    }
+
+    if ($shipment) {
+        $shipment_id = (int)$shipment['id'];
+        $tracking_id = (string)$shipment['tracking_number'];
+        $status_key = strtolower(trim((string)$shipment['status']));
+        $status = ucwords(str_replace('_', ' ', $status_key));
+
+        $raw_progress = is_numeric($shipment['completion_percentage'] ?? null)
+            ? (float)$shipment['completion_percentage']
+            : 0;
+        $progress_percent = $raw_progress <= 0 ? 10 : max(10, min(100, (int)round($raw_progress)));
+
+        $eta_seconds = epoch_to_seconds($shipment['estimated_delivery_time'] ?? null);
+        if ($eta_seconds) {
+            $estimated_delivery_label = date('D, M j, Y', $eta_seconds);
+        }
+
+        $evtStmt = $conn->prepare("
+            SELECT id, location_name, city, state_region, country_code, status_text, issue_note, event_time_epoch, event_severity
+            FROM shipment_location_events
+            WHERE shipment_id = ?
+            ORDER BY event_time_epoch DESC, id DESC
+        ");
+
+        if ($evtStmt) {
+            $evtStmt->bind_param("i", $shipment_id);
+            $evtStmt->execute();
+            $evtRes = $evtStmt->get_result();
+
+            while ($row = $evtRes->fetch_assoc()) {
+                $event_ts = epoch_to_seconds($row['event_time_epoch']);
+                $location_parts = array_filter([
+                    $row['location_name'] ?? '',
+                    $row['city'] ?? '',
+                    $row['state_region'] ?? '',
+                    $row['country_code'] ?? ''
+                ], fn($v) => $v !== null && $v !== '');
+
+                $history[] = [
+                    'time' => $event_ts ? date('h:i A', $event_ts) : '--:--',
+                    'date' => $event_ts ? date('M j, Y', $event_ts) : 'Unknown',
+                    'location' => implode(', ', $location_parts),
+                    'activity' => (string)($row['status_text'] ?: 'Shipment Update'),
+                    'issue_note' => (string)($row['issue_note'] ?? ''),
+                    'is_negative' => strtolower((string)($row['event_severity'] ?? '')) === 'negative',
+                ];
+            }
+
+            $evtStmt->close();
+        }
+    }
+}
+
+$status_to_stage = [
+    'label_created' => 0,
+    'pending' => 0,
+    'shipped' => 1,
+    'picked_up' => 1,
+    'incoming' => 2,
+    'in_transit' => 2,
+    'out_for_delivery' => 2,
+    'in_store' => 2,
+    'outgoing' => 2,
+    'delivered' => 3,
+    'completed' => 3,
+];
+
+$active_stage = $status_to_stage[$status_key] ?? (($progress_percent >= 95) ? 3 : (($progress_percent >= 40) ? 2 : 1));
+?>
 
@@ -16,72 +111,104 @@
 <html lang="en">
-<head>
-    <meta charset="UTF-8">
-    <meta name="viewport" content="width=device-width, initial-scale=1.0">
-    <title>Track Your Shipment | TK Pro Design</title>
-    <link rel="stylesheet" href="/assets/stylesheets/main.css">
-    <link rel="stylesheet" href="/assets/stylesheets/tracking.css">
-    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
-</head>
-<body>
-<?php include("../common-sections/header.html"); ?>
-    <main class="track-container">
-        <header class="track-header">
-            <h1>Tracking</h1>
-            <div class="search-bar">
-                <input type="text" placeholder="Tracking Number" value="<?= $tracking_id ?>">
-                <button class="btn-track">Track</button>
-            </div>
-        </header>
-
-        <div class="track-grid">
-            <section class="main-card">
-                <div class="status-header">
-                    <div class="id-group">
-                        <span>Tracking Number</span>
-                        <strong><?= $tracking_id ?></strong>
-                    </div>
-                    <div class="status-badge <?= str_replace(' ', '-', strtolower($status)) ?>">
-                        <?= $status ?>
-                    </div>
-                </div>
-
-                <div class="tracking-visual">
-                    <div class="progress-line">
-                        <div class="fill" style="width: <?= $progress_percent ?>%;"></div>
-                    </div>
-                    <div class="nodes">
-                        <div class="node done"><i class="material-symbols-outlined">package_2</i><span>Shipped</span></div>
-                        <div class="node active"><i class="material-symbols-outlined">local_shipping</i><span>In Transit</span></div>
-                        <div class="node"><i class="material-symbols-outlined">inventory_2</i><span>Delivered</span></div>
-                    </div>
-                </div>
-
-                <div class="estimated-delivery">
-                    <p>Estimated Delivery</p>
-                    <h2>Thursday, March 5, 2026</h2>
-                    <span>By End of Day</span>
-                </div>
-            </section>
-
-            <section class="history-card">
-                <h3>Detailed History</h3>
-                <div class="timeline">
-                    <?php foreach($history as $event): ?>
-                        <div class="timeline-item">
-                            <div class="time-col">
-                                <strong><?= $event['time'] ?></strong>
-                                <span><?= $event['date'] ?></span>
-                            </div>
-                            <div class="activity-col">
-                                <strong><?= $event['activity'] ?></strong>
-                                <span><?= $event['location'] ?></span>
-                            </div>
-                        </div>
-                    <?php endforeach; ?>
-                </div>
-            </section>
-        </div>
-    </main>
-<?php include("../common-sections/footer.html"); ?>
-</body>
-</html>
\ No newline at end of file
+<head>
+    <meta charset="UTF-8">
+    <meta name="viewport" content="width=device-width, initial-scale=1.0">
+    <title>Track | UPS Express Services - United States</title>
+    <link rel="stylesheet" href="/assets/stylesheets/main.css?v=<?php echo time(); ?>">
+    <link rel="stylesheet" href="/assets/stylesheets/tracking.css?v=<?php echo time(); ?>">
+    <link rel="stylesheet" href="/assets/stylesheets/ts/main.css?v=<?php echo time(); ?>" media="screen and (max-width: 1120px)">
+    <link rel="stylesheet" href="/assets/stylesheets/ms/main.css?v=<?php echo time(); ?>" media="screen and (max-width: 760px)">
+    <link rel="shortcut icon" href="/assets/images/branding/favicon.png?v=<?php echo time(); ?>" type="image/png">
+    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
+</head>
+<body>
+<?php include("../common-sections/header.html"); ?>
+    <main class="track-container">
+        <header class="track-header">
+            <h1>Tracking</h1>
+            <div class="search-bar">
+                <form action="/track/" method="get" class="track-form">
+                    <input type="text" name="id" placeholder="Tracking Number" value="<?= htmlspecialchars($tracking_id) ?>">
+                    <button class="btn-track" type="submit">Track</button>
+                </form>
+            </div>
+        </header>
+
+        <?php if ($tracking_id === ''): ?>
+            <section class="empty-track">
+                <h2>Enter a tracking number</h2>
+                <p>Use the search bar above to view shipment progress and detailed history.</p>
+            </section>
+        <?php elseif (!$shipment): ?>
+            <section class="empty-track">
+                <h2>No shipment found</h2>
+                <p>Tracking number <strong><?= htmlspecialchars($tracking_id) ?></strong> was not found.</p>
+            </section>
+        <?php else: ?>
+        <div class="track-grid">
+            <section class="main-card">
+                <div class="status-header">
+                    <div class="id-group">
+                        <span>Tracking Number</span>
+                        <strong><?= htmlspecialchars($tracking_id) ?></strong>
+                    </div>
+                    <div class="status-badge <?= htmlspecialchars(str_replace(' ', '-', strtolower($status))) ?>">
+                        <?= htmlspecialchars($status) ?>
+                    </div>
+                </div>
+
+                <div class="tracking-visual">
+                    <div class="progress-line">
+                        <div class="fill" style="width: <?= $progress_percent ?>%;"></div>
+                    </div>
+                    <div class="nodes">
+                        <div class="node <?= $active_stage >= 1 ? 'done' : '' ?>">
+                            <i class="material-symbols-outlined">package_2</i><span>Shipped</span>
+                        </div>
+                        <div class="node <?= $active_stage >= 2 ? 'done' : '' ?>">
+                            <i class="material-symbols-outlined">local_shipping</i><span>In Transit</span>
+                        </div>
+                        <div class="node <?= $active_stage >= 3 ? 'done' : '' ?>">
+                            <i class="material-symbols-outlined">inventory_2</i><span>Delivered</span>
+                        </div>
+                    </div>
+                </div>
+
+                <div class="estimated-delivery">
+                    <p>Estimated Delivery</p>
+                    <h2><?= htmlspecialchars($estimated_delivery_label) ?></h2>
+                    <span>Based on latest shipment record</span>
+                </div>
+            </section>
+
+            <section class="history-card">
+                <h3>
+                    <a href="/shipping/view/?id=<?= (int)$shipment_id ?>">Detailed History</a>
+                </h3>
+                <div class="timeline">
+                    <?php if (!empty($history)): ?>
+                    <?php foreach($history as $event): ?>
+                        <div class="timeline-item <?= $event['is_negative'] ? 'is-negative' : 'is-neutral' ?>">
+                            <div class="time-col">
+                                <strong><?= htmlspecialchars($event['time']) ?></strong>
+                                <span><?= htmlspecialchars($event['date']) ?></span>
+                            </div>
+                            <div class="activity-col">
+                                <strong><?= htmlspecialchars($event['activity']) ?></strong>
+                                <?php if (!empty($event['issue_note'])): ?>
+                                    <small><?= htmlspecialchars($event['issue_note']) ?></small>
+                                <?php endif; ?>
+                                <span><?= htmlspecialchars($event['location']) ?></span>
+                            </div>
+                        </div>
+                    <?php endforeach; ?>
+                    <?php else: ?>
+                        <p class="empty-history">No location events available yet.</p>
+                    <?php endif; ?>
+                </div>
+            </section>
+        </div>
+        <?php endif; ?>
+    </main>
+<?php include("../common-sections/footer.html"); ?>
+</body>
+</html>
