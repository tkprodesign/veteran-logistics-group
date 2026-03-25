<?php include("../app.php"); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Proofs | Veteran Logistics Group</title>
    <link rel="stylesheet" href="/assets/stylesheets/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/stylesheets/control-panel.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="shortcut icon" href="/assets/images/branding/mark-only.png?v=<?php echo time(); ?>" type="image/png">
</head>
<body>
    <?php include("../partials/header.php"); ?>

    <div class="header-2">
        <div class="container">
            <h2 class="greeting"><span class="material-symbols-outlined" aria-hidden="true">upload_file</span> Payment Proofs</h2>
            <h1 class="cutomer-name">All shipment_payment_proofs Records</h1>
        </div>
    </div>

    <div class="container content">
        <section class="cp-card">
            <div class="cp-card-head">
                <div>
                    <h2>Payment Proof Directory</h2>
                    <p>Uploaded cryptocurrency proof of payment records</p>
                </div>
                <a class="cp-btn cp-btn-secondary" href="/control-panel/page/">Back to Control Panel</a>
            </div>
            <?php
            $proofTableCheck = $dbconn->query("SHOW TABLES LIKE 'shipment_payment_proofs'");
            $proofTableExists = ($proofTableCheck && $proofTableCheck->num_rows > 0);
            ?>
            <?php if (!$proofTableExists): ?>
                <p class="cp-quote-notice is-error">Table <code>shipment_payment_proofs</code> does not exist yet.</p>
            <?php else: ?>
                <div class="cp-table-wrap">
                    <table class="cp-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>File Name</th>
                                <th>Uploaded</th>
                                <th>File</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $proofSql = "SELECT id, user_id, name, email, file_name, uploaded_at_epoch FROM shipment_payment_proofs ORDER BY id DESC";
                            $proofResult = $dbconn->query($proofSql);
                            if ($proofResult && $proofResult->num_rows > 0):
                                while ($proof = $proofResult->fetch_assoc()):
                                    $uploadedTs = (int)$proof['uploaded_at_epoch'];
                                    if ($uploadedTs > 1000000000000) {
                                        $uploadedTs = (int)($uploadedTs / 1000);
                                    }
                                    $uploadedDisplay = $uploadedTs > 0 ? date("M d, Y H:i", $uploadedTs) : "-";
                                    $fileName = (string)($proof['file_name'] ?? '');
                                    $fileHref = '/shipping/create/payments-upload/' . rawurlencode($fileName);
                            ?>
                                <tr>
                                    <td><?= (int)$proof['id'] ?></td>
                                    <td><?= (int)$proof['user_id'] ?></td>
                                    <td><?= htmlspecialchars((string)$proof['name']) ?></td>
                                    <td><?= htmlspecialchars((string)$proof['email']) ?></td>
                                    <td><?= htmlspecialchars($fileName) ?></td>
                                    <td><?= htmlspecialchars($uploadedDisplay) ?></td>
                                    <td>
                                        <?php if ($fileName !== ''): ?>
                                            <a class="cp-table-link" href="<?= htmlspecialchars($fileHref) ?>" target="_blank" rel="noopener noreferrer">View File</a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php
                                endwhile;
                            else:
                            ?>
                                <tr>
                                    <td colspan="7">No payment proof records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <?php include("../../common-sections/footer.html"); ?>
</body>
</html>
