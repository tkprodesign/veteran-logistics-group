<?php include("../app.php"); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exception Payments | Veteran Logistics Group</title>
    <link rel="stylesheet" href="/assets/stylesheets/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/stylesheets/control-panel.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="shortcut icon" href="/assets/images/branding/mark-only.png?v=<?php echo time(); ?>" type="image/png">
</head>
<body>
    <?php include("../partials/header.php"); ?>

    <div class="header-2">
        <div class="container">
            <h2 class="greeting"><span class="material-symbols-outlined" aria-hidden="true">payments</span> Exception Payments</h2>
            <h1 class="cutomer-name">All exception_issue_payments Records</h1>
        </div>
    </div>

    <div class="container content">
        <section id="cp-exception-payments" class="cp-card">
            <div class="cp-card-head">
                <div>
                    <h2>Exception Payment Directory</h2>
                    <p>Submitted payments for shipment exceptions and issue clarification</p>
                </div>
                <a class="cp-btn cp-btn-secondary" href="/control-panel/page/#cp-exception-payments">Back to Control Panel</a>
            </div>

            <?php if (!empty($cp_exception_payment_notice)): ?>
                <p class="cp-quote-notice <?= ($cp_exception_payment_notice_type === 'success') ? 'is-success' : 'is-error' ?>">
                    <?= htmlspecialchars($cp_exception_payment_notice) ?>
                </p>
            <?php endif; ?>

            <?php
            $exceptionPaymentTableCheck = $dbconn->query("SHOW TABLES LIKE 'exception_issue_payments'");
            $exceptionPaymentTableExists = ($exceptionPaymentTableCheck && $exceptionPaymentTableCheck->num_rows > 0);
            ?>
            <?php if (!$exceptionPaymentTableExists): ?>
                <p class="cp-quote-notice is-error">Table <code>exception_issue_payments</code> does not exist yet.</p>
            <?php else: ?>
                <div class="cp-table-wrap">
                    <table class="cp-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tracking</th>
                                <th>User ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Amount</th>
                                <th>Payment For</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Invoice</th>
                                <th>Proof</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $exceptionPaymentSql = "
                                SELECT id, tracking_number, user_id, name, email, amount, payment_for, payment_method, status, invoice_number, proof_file_name, created_at_epoch
                                FROM exception_issue_payments
                                ORDER BY id DESC
                            ";
                            $exceptionPaymentResult = $dbconn->query($exceptionPaymentSql);
                            if ($exceptionPaymentResult && $exceptionPaymentResult->num_rows > 0):
                                while ($payment = $exceptionPaymentResult->fetch_assoc()):
                                    $createdTs = (int)($payment['created_at_epoch'] ?? 0);
                                    if ($createdTs > 1000000000000) {
                                        $createdTs = (int)($createdTs / 1000);
                                    }
                                    $createdDisplay = $createdTs > 0 ? date("M d, Y H:i", $createdTs) : "-";
                                    $proofFileName = trim((string)($payment['proof_file_name'] ?? ''));
                                    $proofHref = '/shipping/create/payments-upload/' . rawurlencode($proofFileName);
                                    $methodLabel = strtolower((string)($payment['payment_method'] ?? 'card')) === 'crypto' ? 'Other Payment Methods' : 'Payment Card';
                            ?>
                                <tr>
                                    <td><?= (int)$payment['id'] ?></td>
                                    <td><?= htmlspecialchars((string)$payment['tracking_number']) ?></td>
                                    <td><?= (int)$payment['user_id'] ?></td>
                                    <td><?= htmlspecialchars((string)$payment['name']) ?></td>
                                    <td><?= htmlspecialchars((string)$payment['email']) ?></td>
                                    <td>$<?= number_format((float)($payment['amount'] ?? 0), 2) ?></td>
                                    <td><?= htmlspecialchars((string)$payment['payment_for']) ?></td>
                                    <td><?= htmlspecialchars($methodLabel) ?></td>
                                    <td><?= htmlspecialchars((string)$payment['status']) ?></td>
                                    <td><?= htmlspecialchars((string)($payment['invoice_number'] ?? '-')) ?></td>
                                    <td>
                                        <?php if ($proofFileName !== ''): ?>
                                            <a class="cp-table-link" href="<?= htmlspecialchars($proofHref) ?>" target="_blank" rel="noopener noreferrer">View Proof</a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($createdDisplay) ?></td>
                                    <td>
                                        <?php if (strtolower((string)($payment['status'] ?? '')) === 'pending_confirmation'): ?>
                                            <form method="post" class="cp-inline-form">
                                                <input type="hidden" name="exception_payment_id" value="<?= (int)$payment['id'] ?>">
                                                <button class="cp-btn" type="submit" name="confirm_exception_payment" value="1">Confirm Payment</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="cp-table-status"><?= htmlspecialchars(ucfirst((string)$payment['status'])) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php
                                endwhile;
                            else:
                            ?>
                                <tr>
                                    <td colspan="13">No exception payment records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <?php include("../../common-sections/footer.html"); ?>
    <script src="/assets/scripts/control-panel-tables.js?v=<?php echo time(); ?>"></script>
</body>
</html>
