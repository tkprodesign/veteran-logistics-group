<?php include("../app.php"); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipments | Control Panel</title>
    <link rel="stylesheet" href="/assets/stylesheets/main.css">
    <link rel="stylesheet" href="/assets/stylesheets/control-panel.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="shortcut icon" href="/assets/images/branding/mark-only.png?v=<?php echo time(); ?>" type="image/png">
</head>
<body>
    <?php include("../partials/header.php"); ?>

    <div class="header-2">
        <div class="container">
            <h2 class="greeting"><span class="material-symbols-outlined" aria-hidden="true">local_shipping</span> Shipments</h2>
            <h1 class="cutomer-name">All Shipment Records</h1>
        </div>
    </div>

    <div class="container content">
        <section class="cp-card">
            <div class="cp-card-head">
                <div>
                    <h2>Shipment Directory</h2>
                    <p>Complete list of shipments in the system</p>
                </div>
                <a class="cp-btn cp-btn-secondary" href="/control-panel/page/">Back to Control Panel</a>
            </div>
            <div class="cp-table-wrap">
                <table class="cp-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tracking Number</th>
                            <th>User ID</th>
                            <th>Email</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Arrival</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $shipSql = "
                            SELECT s.id, s.tracking_number, s.user_id, s.status, s.estimated_delivery_time, s.date_created, u.email, u.name
                            FROM shipments s
                            LEFT JOIN users u ON u.id = s.user_id
                            ORDER BY s.id DESC
                        ";
                        $shipResult = $dbconn->query($shipSql);
                        if ($shipResult && $shipResult->num_rows > 0):
                            while ($s = $shipResult->fetch_assoc()):
                                $shipTs = (int)$s['date_created'];
                                if ($shipTs > 1000000000000) {
                                    $shipTs = (int)($shipTs / 1000);
                                }
                                $shipDisplay = $shipTs > 0 ? date("M d, Y H:i", $shipTs) : "-";
                                $arrivalRaw = $s['estimated_delivery_time'] ?? null;
                                $arrivalDisplay = "-";
                                if ($arrivalRaw !== null && $arrivalRaw !== "") {
                                    if (is_numeric((string)$arrivalRaw)) {
                                        $arrivalTs = (int)$arrivalRaw;
                                        if ($arrivalTs > 1000000000000) {
                                            $arrivalTs = (int)($arrivalTs / 1000);
                                        }
                                        if ($arrivalTs > 0) {
                                            $arrivalDisplay = date("M d, Y H:i", $arrivalTs) . " (epoch)";
                                        }
                                    } else {
                                        $parsedArrival = strtotime((string)$arrivalRaw);
                                        if ($parsedArrival !== false && $parsedArrival > 0) {
                                            $arrivalDisplay = date("M d, Y H:i", $parsedArrival) . " (datetime)";
                                        } else {
                                            $arrivalDisplay = (string)$arrivalRaw;
                                        }
                                    }
                                }
                        ?>
                        <tr>
                            <td><?= (int)$s['id'] ?></td>
                            <td><?= htmlspecialchars((string)$s['tracking_number']) ?></td>
                            <td><?= (int)$s['user_id'] ?></td>
                            <td><?= htmlspecialchars((string)($s['email'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars((string)($s['name'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars((string)$s['status']) ?></td>
                            <td><?= htmlspecialchars($arrivalDisplay) ?></td>
                            <td><?= htmlspecialchars($shipDisplay) ?></td>
                        </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="8">No shipments found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <?php include("../../common-sections/footer.html"); ?>
</body>
</html>
