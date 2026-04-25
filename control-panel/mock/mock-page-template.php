<?php
include('../app.php');
include(__DIR__ . '/mock-data.php');

$mockDesignId = isset($mockDesignId) ? (int)$mockDesignId : 1;
$mockDesigns = cp_mock_design_options();
if (!isset($mockDesigns[$mockDesignId])) {
    $mockDesignId = 1;
}
$currentDesign = $mockDesigns[$mockDesignId];
$mockData = cp_mock_fetch_dashboard_data($dbconn);
$summary = $mockData['summary'];
$recentShipments = $mockData['recent_shipments'];
$lists = is_array($mockData['lists'] ?? null) ? $mockData['lists'] : [];
$totalChargesDemo = max(0, (float)$summary['shipments'] * 0.01 + 52.25);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($currentDesign['title']) ?> | Control Panel Mock</title>
    <link rel="stylesheet" href="/assets/stylesheets/control-panel-mock.css?v=<?php echo time(); ?>">
    <link rel="shortcut icon" href="/assets/images/branding/mark-only.png?v=<?php echo time(); ?>" type="image/png">
</head>
<body class="mock-body" data-mock-design="<?= (int)$mockDesignId ?>">
<main class="mock-wrap">
    <header class="mock-topbar">
        <div>
            <p class="mock-kicker">Control Panel UX Sandbox</p>
            <h1><?= htmlspecialchars($currentDesign['title']) ?></h1>
            <p><?= htmlspecialchars($currentDesign['description']) ?></p>
        </div>
        <div class="mock-topbar-actions">
            <a class="mock-btn mock-btn-ghost" href="/control-panel/mock/">All Mock Designs</a>
            <a class="mock-btn mock-btn-ghost" href="/control-panel/page/">Back to Control Panel</a>
        </div>
    </header>

    <nav class="mock-design-nav" aria-label="Mock pages">
        <?php foreach ($mockDesigns as $id => $design): ?>
            <a
                class="mock-pill <?= ($id === $mockDesignId) ? 'is-active' : '' ?>"
                href="/control-panel/mock/<?= htmlspecialchars($design['slug']) ?>"
            >
                <span>Design <?= (int)$id ?></span>
                <small><?= htmlspecialchars($design['tag']) ?></small>
            </a>
        <?php endforeach; ?>
    </nav>

    <section class="mock-design-stage">
        <article class="mock-preview-card mock-preview-header">
            <h2>Live Snapshot</h2>
            <p>
                Users: <strong><?= number_format((int)$summary['users']) ?></strong> ·
                Shipments: <strong><?= number_format((int)$summary['shipments']) ?></strong> ·
                Quotes: <strong><?= number_format((int)$summary['quotes']) ?></strong> ·
                Pending Proofs: <strong><?= number_format((int)$summary['pending_proofs']) ?></strong>
            </p>
        </article>

        <article class="mock-preview-card mock-mobile-frame">
            <div class="mock-mobile-head">
                <p>Step 4 of 5</p>
                <h3>Payment</h3>
            </div>

            <div class="mock-design-shell">
                <div class="mock-total-line">
                    <span>Total Due</span>
                    <strong>$<?= number_format($totalChargesDemo, 2) ?></strong>
                </div>

                <div class="mock-charge-list" data-toggle-target>
                    <div><span>Service</span><strong>$49.50</strong></div>
                    <div><span>Postman Pickup</span><strong>$0.00</strong></div>
                    <div><span>Shipment Options</span><strong>$7.70</strong></div>
                    <div><span>Promo Discount</span><strong>- $5.00</strong></div>
                    <div class="mock-list-total"><span>Total</span><strong>$52.20</strong></div>
                </div>

                <button type="button" class="mock-toggle-btn" data-toggle-list>
                    Toggle cost breakdown
                </button>
            </div>

            <div class="mock-detail-grid">
                <article>
                    <h4>Recent Shipment</h4>
                    <?php if (!empty($recentShipments)): ?>
                        <p><strong>Tracking:</strong> <?= htmlspecialchars($recentShipments[0]['tracking_number']) ?></p>
                        <p><strong>Sender:</strong> <?= htmlspecialchars($recentShipments[0]['sender_name']) ?></p>
                        <p><strong>Receiver:</strong> <?= htmlspecialchars($recentShipments[0]['receiver_name']) ?></p>
                        <p><strong>Status:</strong> <?= htmlspecialchars($recentShipments[0]['status']) ?></p>
                    <?php else: ?>
                        <p>No shipment records available.</p>
                    <?php endif; ?>
                </article>
                <article>
                    <h4>Notes</h4>
                    <p>Each mock uses the same live metrics but applies a different mobile list pattern.</p>
                    <p>Use these pages to compare spacing, grouping, and readability before final implementation.</p>
                </article>
            </div>
        </article>

        <article class="mock-preview-card">
            <h2>Live Lists Preview (10 rows each)</h2>
            <p class="mock-muted">
                Each list below shows real records up to 10 rows. If fewer than 10 records exist, placeholder rows fill the remaining slots so you can compare visual density across all four designs.
            </p>

            <div class="mock-list-grid">
                <?php
                $listTitles = [
                    'users' => 'Site Users',
                    'shipments' => 'Shipments',
                    'quotes' => 'Service Quotes',
                    'exception_payments' => 'Exception Payments',
                ];
                ?>
                <?php foreach ($listTitles as $listKey => $listTitle): ?>
                    <?php $rows = is_array($lists[$listKey] ?? null) ? $lists[$listKey] : []; ?>
                    <section class="mock-list-card">
                        <div class="mock-list-head">
                            <h3><?= htmlspecialchars($listTitle) ?></h3>
                            <span><?= count($rows) ?> rows</span>
                        </div>
                        <div class="mock-list-wrap">
                            <table>
                                <?php if (!empty($rows)): ?>
                                    <thead>
                                    <tr>
                                        <?php foreach (array_keys($rows[0]) as $headerKey): ?>
                                            <?php if ($headerKey === '_is_placeholder') continue; ?>
                                            <th><?= htmlspecialchars((string)$headerKey) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($rows as $row): ?>
                                        <tr class="<?= !empty($row['_is_placeholder']) ? 'is-placeholder' : '' ?>">
                                            <?php foreach ($row as $cellKey => $cellValue): ?>
                                                <?php if ($cellKey === '_is_placeholder') continue; ?>
                                                <td><?= htmlspecialchars((string)$cellValue) ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                <?php else: ?>
                                    <tbody><tr><td>No records found.</td></tr></tbody>
                                <?php endif; ?>
                            </table>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
        </article>
    </section>
</main>
<script src="/assets/scripts/control-panel-mock.js?v=<?php echo time(); ?>"></script>
</body>
</html>
