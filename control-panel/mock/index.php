<?php
include('../app.php');
include(__DIR__ . '/mock-data.php');

$mockData = cp_mock_fetch_dashboard_data($dbconn);
$mockDesigns = cp_mock_design_options();
$summary = $mockData['summary'];
$recentShipments = $mockData['recent_shipments'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Panel Mock Hub | Veteran Logistics Group</title>
    <link rel="stylesheet" href="/assets/stylesheets/control-panel-mock.css?v=<?php echo time(); ?>">
    <link rel="shortcut icon" href="/assets/images/branding/mark-only.png?v=<?php echo time(); ?>" type="image/png">
</head>
<body class="mock-body" data-mock-design="hub">
<main class="mock-wrap">
    <header class="mock-topbar">
        <div>
            <p class="mock-kicker">Control Panel UX Sandbox</p>
            <h1>Mock Design Hub</h1>
            <p>Pick one layout below to preview with live site data from your current database.</p>
        </div>
        <div class="mock-topbar-actions">
            <a class="mock-btn mock-btn-ghost" href="/control-panel/page/">Back to Control Panel</a>
        </div>
    </header>

    <section class="mock-summary-grid">
        <article class="mock-metric"><span>Total Users</span><strong><?= number_format((int)$summary['users']) ?></strong></article>
        <article class="mock-metric"><span>Total Shipments</span><strong><?= number_format((int)$summary['shipments']) ?></strong></article>
        <article class="mock-metric"><span>Service Quotes</span><strong><?= number_format((int)$summary['quotes']) ?></strong></article>
        <article class="mock-metric"><span>Exception Payments</span><strong><?= number_format((int)$summary['exception_payments']) ?></strong></article>
    </section>

    <section class="mock-links-grid">
        <?php foreach ($mockDesigns as $id => $design): ?>
            <article class="mock-link-card">
                <p class="mock-tag"><?= htmlspecialchars($design['tag']) ?></p>
                <h2><?= htmlspecialchars($design['title']) ?></h2>
                <p><?= htmlspecialchars($design['description']) ?></p>
                <a class="mock-btn" href="/control-panel/mock/<?= htmlspecialchars($design['slug']) ?>">Open mock design <?= (int)$id ?></a>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="mock-table-card">
        <div class="mock-table-head">
            <h2>Recent Shipments (live data sample)</h2>
            <p>Latest <?= count($recentShipments) ?> records from <code>shipments</code>.</p>
        </div>
        <div class="mock-table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Tracking</th>
                    <th>Type</th>
                    <th>Sender</th>
                    <th>Receiver</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($recentShipments)): ?>
                    <?php foreach ($recentShipments as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['tracking_number']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($row['shipment_type'])) ?></td>
                            <td><?= htmlspecialchars($row['sender_name']) ?></td>
                            <td><?= htmlspecialchars($row['receiver_name']) ?></td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                            <td><?= htmlspecialchars($row['created_display']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6">No shipments found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
<script src="/assets/scripts/control-panel-mock.js?v=<?php echo time(); ?>"></script>
</body>
</html>
