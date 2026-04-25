<?php include("../app.php"); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Quotes | Control Panel</title>
    <link rel="stylesheet" href="/assets/stylesheets/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/stylesheets/control-panel.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="shortcut icon" href="/assets/images/branding/mark-only.png?v=<?php echo time(); ?>" type="image/png">
</head>
<body>
    <?php include("../partials/header.php"); ?>

    <div class="header-2">
        <div class="container">
            <h2 class="greeting"><span class="material-symbols-outlined" aria-hidden="true">request_quote</span> Service Quotes</h2>
            <h1 class="cutomer-name">All shipment_service_quotes Records</h1>
        </div>
    </div>

    <div class="container content">
        <section class="cp-card">
            <div class="cp-card-head">
                <div>
                    <h2>Service Quote Directory</h2>
                    <p>Complete list of quote processing records</p>
                </div>
                <a class="cp-btn cp-btn-secondary" href="/control-panel/page/">Back to Control Panel</a>
            </div>
            <div class="cp-table-wrap">
                <table class="cp-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User ID</th>
                            <th>Email</th>
                            <th>Service Level</th>
                            <th>Status</th>
                            <th>Price</th>
                            <th>Duration</th>
                            <th>Description</th>
                            <th>Created</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $quotesSql = "
                            SELECT q.id, q.user_id, q.service_level, q.processing_status, q.price, q.duration, q.description_text, q.created_at_epoch, q.updated_at_epoch, u.email
                            FROM shipment_service_quotes q
                            LEFT JOIN users u ON u.id = q.user_id
                            ORDER BY q.id DESC
                        ";
                        $quotesResult = $dbconn->query($quotesSql);
                        if ($quotesResult && $quotesResult->num_rows > 0):
                            while ($q = $quotesResult->fetch_assoc()):
                                $createdTs = (int)$q['created_at_epoch'];
                                $updatedTs = (int)$q['updated_at_epoch'];
                                if ($createdTs > 1000000000000) $createdTs = (int)($createdTs / 1000);
                                if ($updatedTs > 1000000000000) $updatedTs = (int)($updatedTs / 1000);
                                $createdDisplay = $createdTs > 0 ? date("M d, Y H:i", $createdTs) : "-";
                                $updatedDisplay = $updatedTs > 0 ? date("M d, Y H:i", $updatedTs) : "-";
                                $priceDisplay = ($q['price'] !== null && $q['price'] !== '') ? ('$' . number_format((float)$q['price'], 2)) : '-';
                        ?>
                        <tr>
                            <td><?= (int)$q['id'] ?></td>
                            <td><?= (int)$q['user_id'] ?></td>
                            <td><?= htmlspecialchars((string)($q['email'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars((string)$q['service_level']) ?></td>
                            <td><?= htmlspecialchars((string)$q['processing_status']) ?></td>
                            <td><?= htmlspecialchars($priceDisplay) ?></td>
                            <td><?= isset($q['duration']) && $q['duration'] !== null && $q['duration'] !== '' ? ((int)$q['duration'] . ' day' . (((int)$q['duration'] === 1) ? '' : 's')) : '-' ?></td>
                            <td><?= htmlspecialchars((string)($q['description_text'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars($createdDisplay) ?></td>
                            <td><?= htmlspecialchars($updatedDisplay) ?></td>
                        </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="10">No service quote records found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <?php include("../../common-sections/footer.html"); ?>
    <script src="/assets/scripts/control-panel-tables.js?v=<?php echo time(); ?>"></script>
</body>
</html>
