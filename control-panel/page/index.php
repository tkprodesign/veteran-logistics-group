<?php include("../app.php");?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Control Panel | Veteran Logistics Group</title>
    <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css"/> -->
    <link rel="stylesheet" href="/assets/stylesheets/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/stylesheets/control-panel.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="shortcut icon" href="/assets/images/branding/mark-only.png?v=<?php echo time(); ?>" type="image/png">
    <!-- <script src="https://kit.fontawesome.com/4fee328683.js" crossorigin="anonymous"></script> -->
</head>
<body>
    <?php include("../partials/header.php");?>
    <div class="header-2">
        <div class="container">
            <h2 class="greeting" id="adminGreeting"><span class="material-symbols-outlined" aria-hidden="true">waving_hand</span> Welcome!</h2>
            <h1 class="cutomer-name" id="adminName">Admin</h1>
        </div>
    </div>
    <div class="container content">
        
        <section id="cp-add-location-event" class="cp-card">
            <div class="cp-card-head">
                <div>
                    <h2>Add Shipping Location Event</h2>
                    <p>Add a new tracking update for a shipment and timeline.</p>
                </div>
            </div>
            <?php if (!empty($cp_location_event_notice)): ?>
                <p class="cp-quote-notice <?= ($cp_location_event_notice_type === 'success') ? 'is-success' : 'is-error' ?>">
                    <?= htmlspecialchars($cp_location_event_notice) ?>
                </p>
            <?php endif; ?>
            <p class="cp-form-helper">Use the shipment ID and matching tracking number from the shipment record, then enter the customer-facing status update below.</p>
            <form method="post" class="cp-location-form" novalidate>
                <div class="cp-location-grid">
                    <div>
                        <label for="event_shipment_id">Shipment ID</label>
                        <input id="event_shipment_id" type="number" min="1" step="1" name="event_shipment_id" inputmode="numeric" required>
                    </div>
                    <div>
                        <label for="event_tracking_number">Tracking Number</label>
                        <input id="event_tracking_number" type="text" name="event_tracking_number" required>
                    </div>
                    <div>
                        <label for="event_location_label">Location Label</label>
                        <select id="event_location_label" name="event_location_label">
                            <option value="checkpoint">checkpoint</option>
                            <option value="origin">origin</option>
                            <option value="exception">exception</option>
                            <option value="destination">destination</option>
                        </select>
                    </div>
                    <div>
                        <label for="event_severity">Event Severity</label>
                        <select id="event_severity" name="event_severity">
                            <option value="neutral">neutral</option>
                            <option value="negative">negative</option>
                        </select>
                    </div>
                    <div>
                        <label for="event_country_code">Country Code</label>
                        <input id="event_country_code" type="text" name="event_country_code" maxlength="2" value="US" required>
                    </div>
                    <div>
                        <label for="event_location_name">Location Name</label>
                        <input id="event_location_name" type="text" name="event_location_name" required>
                    </div>
                    <div>
                        <label for="event_city">City</label>
                        <input id="event_city" type="text" name="event_city">
                    </div>
                    <div>
                        <label for="event_state_region">State/Region</label>
                        <input id="event_state_region" type="text" name="event_state_region">
                    </div>
                    <div>
                        <label for="event_postal_code">Postal Code</label>
                        <input id="event_postal_code" type="text" name="event_postal_code">
                    </div>
                    <div class="cp-location-grid-wide">
                        <label for="event_status_text">Status Text</label>
                        <input id="event_status_text" type="text" name="event_status_text" placeholder="Example: Arrived at Hub, Shipment Delayed, Out for Delivery" required>
                    </div>
                    <div>
                        <label for="event_payment_amount">Payment Amount</label>
                        <input id="event_payment_amount" type="number" min="0" step="0.01" name="event_payment_amount" placeholder="Optional">
                    </div>
                    <div class="cp-location-grid-wide">
                        <label for="event_payment_reason">What the Payment Is For</label>
                        <input id="event_payment_reason" type="text" name="event_payment_reason" placeholder="Example: Customs clarification fee, documentation review fee">
                    </div>
                </div>
                <div class="cp-quote-actions">
                    <button class="cp-btn" type="submit" name="add_location_event" value="1">Add Location Event</button>
                </div>
            </form>
        </section>

        <section id="cp-exception-payments" class="cp-card">
            <div class="cp-card-head">
                <div>
                    <h2>Exception Payments</h2>
                    <p>Latest 10 records from exception_issue_payments</p>
                </div>
                <a class="cp-btn" href="/control-panel/exception-payments/">See All Exception Payments</a>
            </div>
            <?php
            $exceptionPaymentTableCheck = $dbconn->query("SHOW TABLES LIKE 'exception_issue_payments'");
            $exceptionPaymentTableExists = ($exceptionPaymentTableCheck && $exceptionPaymentTableCheck->num_rows > 0);
            ?>
            <?php if (!empty($cp_exception_payment_notice)): ?>
                <p class="cp-quote-notice <?= ($cp_exception_payment_notice_type === 'success') ? 'is-success' : 'is-error' ?>">
                    <?= htmlspecialchars($cp_exception_payment_notice) ?>
                </p>
            <?php endif; ?>
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
                                <th>Amount</th>
                                <th>Payment For</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Proof</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $exceptionPaymentSql = "
                                SELECT id, tracking_number, user_id, name, amount, payment_for, payment_method, proof_file_name, status, created_at_epoch
                                FROM exception_issue_payments
                                ORDER BY id DESC
                                LIMIT 10
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
                                <td>$<?= number_format((float)($payment['amount'] ?? 0), 2) ?></td>
                                <td><?= htmlspecialchars((string)$payment['payment_for']) ?></td>
                                <td><?= htmlspecialchars($methodLabel) ?></td>
                                <td><?= htmlspecialchars((string)$payment['status']) ?></td>
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
                                <td colspan="11">No exception payment records found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="cp-card-foot">
                    <a class="cp-btn cp-btn-secondary" href="/control-panel/exception-payments/">View Complete Exception Payment List</a>
                </div>
            <?php endif; ?>
        </section>

        <section id="cp-user-payment-block" class="cp-card">
            <div class="cp-card-head">
                <div>
                    <h2>User Payment Block</h2>
                </div>
            </div>
            <?php if (!empty($cp_user_pay_block_notice)): ?>
                <p class="cp-quote-notice <?= ($cp_user_pay_block_notice_type === 'success') ? 'is-success' : 'is-error' ?>">
                    <?= htmlspecialchars($cp_user_pay_block_notice) ?>
                </p>
            <?php endif; ?>
            <form method="post" class="cp-quote-form">
                <div class="cp-quote-grid">
                    <div>
                        <label for="user_id">User ID</label>
                        <input id="user_id" type="number" min="1" step="1" name="user_id" required>
                    </div>
                    <div>
                        <label for="pay_block_tittle">Pay Block Tittle</label>
                        <select id="pay_block_tittle" name="pay_block_tittle" required>
                            <option value="">Select Error Type</option>
                            <option value="Gateway Error">Gateway Error</option>
                            <option value="Transaction Processing Error">Transaction Processing Error</option>
                            <option value="Issuer / Bank System Problem">Issuer / Bank System Problem</option>
                            <option value="Not Available in Your Country">Not Available in Your Country</option>
                        </select>
                    </div>
                    <div>
                        <label for="pay_block_message_preview">Pay Block Message</label>
                        <input id="pay_block_message_preview" type="text" value="" placeholder="Auto-filled from selected error type" readonly>
                        <input id="pay_block_message" type="hidden" name="pay_block_message" value="">
                    </div>
                </div>
                <div class="cp-quote-actions">
                    <button class="cp-btn" type="submit" name="update_user_pay_block" value="1">Update User Block</button>
                </div>
            </form>
        </section>

        <section id="cp-support-email" class="cp-card">
            <div class="cp-card-head">
                <div>
                    <h2>Send Support Email</h2>
<p>Send styled support emails via Resend from support@veteranlogisticsgroup.com</p>
                </div>
            </div>
            <?php if (!empty($cp_support_email_notice)): ?>
                <p class="cp-quote-notice <?= ($cp_support_email_notice_type === 'success') ? 'is-success' : 'is-error' ?>">
                    <?= htmlspecialchars($cp_support_email_notice) ?>
                </p>
            <?php endif; ?>
            <form method="post" class="cp-quote-form">
                <div class="cp-quote-grid">
                    <div>
                        <label for="support_receiver_email">Receiver Email</label>
                        <input id="support_receiver_email" type="email" name="support_receiver_email" required>
                    </div>
                    <div>
                        <label for="support_subject">Subject</label>
                        <input id="support_subject" type="text" name="support_subject" required>
                    </div>
                    <div class="cp-quote-grid-wide">
                        <label for="support_message">Message</label>
                        <textarea id="support_message" name="support_message" rows="6" required></textarea>
                    </div>
                </div>
                <div class="cp-quote-actions">
                    <button class="cp-btn" type="submit" name="send_support_email" value="1">Send Support Email</button>
                </div>
            </form>
        </section>

        <section id="cp-shipments" class="cp-card">
            <div class="cp-card-head">
                <div>
                    <h2>Shipments</h2>
                    <p>Latest 10 shipment records</p>
                </div>
                <a class="cp-btn" href="/control-panel/shipments/">See All Shipments</a>
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
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $shipSql = "
                            SELECT s.id, s.tracking_number, s.user_id, s.status, s.date_created, u.email, u.name
                            FROM shipments s
                            LEFT JOIN users u ON u.id = s.user_id
                            ORDER BY s.id DESC
                            LIMIT 10
                        ";
                        $shipResult = $dbconn->query($shipSql);
                        if ($shipResult && $shipResult->num_rows > 0):
                            while ($s = $shipResult->fetch_assoc()):
                                $shipTs = (int)$s['date_created'];
                                if ($shipTs > 1000000000000) {
                                    $shipTs = (int)($shipTs / 1000);
                                }
                                $shipDisplay = $shipTs > 0 ? date("M d, Y H:i", $shipTs) : "-";
                        ?>
                        <tr>
                            <td><?= (int)$s['id'] ?></td>
                            <td><?= htmlspecialchars((string)$s['tracking_number']) ?></td>
                            <td><?= (int)$s['user_id'] ?></td>
                            <td><?= htmlspecialchars((string)($s['email'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars((string)($s['name'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars((string)$s['status']) ?></td>
                            <td><?= htmlspecialchars($shipDisplay) ?></td>
                        </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="7">No shipments found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="cp-card-foot">
                <a class="cp-btn cp-btn-secondary" href="/control-panel/shipments/">View Complete Shipment List</a>
            </div>
        </section>
        
        <section id="cp-service-quotes" class="cp-card">
            <div class="cp-card-head">
                <div>
                    <h2>Service Quotes</h2>
                    <p>Latest 10 records from shipment_service_quotes</p>
                </div>
                <a class="cp-btn" href="/control-panel/service-quotes/">See All Service Quotes</a>
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
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $quotesSql = "
                            SELECT q.id, q.user_id, q.service_level, q.processing_status, q.price, q.duration, q.created_at_epoch, u.email
                            FROM shipment_service_quotes q
                            LEFT JOIN users u ON u.id = q.user_id
                            ORDER BY q.id DESC
                            LIMIT 10
                        ";
                        $quotesResult = $dbconn->query($quotesSql);
                        if ($quotesResult && $quotesResult->num_rows > 0):
                            while ($q = $quotesResult->fetch_assoc()):
                                $createdTs = (int)$q['created_at_epoch'];
                                if ($createdTs > 1000000000000) {
                                    $createdTs = (int)($createdTs / 1000);
                                }
                                $createdDisplay = $createdTs > 0 ? date("M d, Y H:i", $createdTs) : "-";
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
                            <td><?= htmlspecialchars($createdDisplay) ?></td>
                        </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="8">No service quote records found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="cp-card-foot">
                <a class="cp-btn cp-btn-secondary" href="/control-panel/service-quotes/">View Complete Service Quote List</a>
            </div>
        </section>

        <section id="cp-payment-proofs" class="cp-card">
            <div class="cp-card-head">
                <div>
                    <h2>Payment Proofs</h2>
                    <p>Latest 10 records from shipment_payment_proofs</p>
                </div>
                <a class="cp-btn" href="/control-panel/payment-proofs/">See All Payment Proofs</a>
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
                            $proofSql = "SELECT id, user_id, name, email, file_name, uploaded_at_epoch FROM shipment_payment_proofs ORDER BY id DESC LIMIT 10";
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
                <div class="cp-card-foot">
                    <a class="cp-btn cp-btn-secondary" href="/control-panel/payment-proofs/">View Complete Payment Proof List</a>
                </div>
            <?php endif; ?>
        </section>
        <section id="cp-site-users" class="cp-card">
            <div class="cp-card-head">
                <div>
                    <h2>Site Users</h2>
                    <p>Latest 10 registered users</p>
                </div>
                <a class="cp-btn" href="/control-panel/site-users/">See All Users</a>
            </div>
            <div class="cp-table-wrap">
                <table class="cp-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Country Code</th>
                            <th>Phone</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $usersSql = "SELECT id, name, email, username, country_code, phone_number, created_at FROM users ORDER BY id DESC LIMIT 10";
                        $usersResult = $dbconn->query($usersSql);
                        if ($usersResult && $usersResult->num_rows > 0):
                            while ($u = $usersResult->fetch_assoc()):
                                $joinedTs = (int)$u['created_at'];
                                if ($joinedTs > 1000000000000) {
                                    $joinedTs = (int)($joinedTs / 1000);
                                }
                                $joinedDisplay = $joinedTs > 0 ? date("M d, Y H:i", $joinedTs) : "-";
                        ?>
                        <tr>
                            <td><?= (int)$u['id'] ?></td>
                            <td><?= htmlspecialchars((string)$u['name']) ?></td>
                            <td><?= htmlspecialchars((string)$u['email']) ?></td>
                            <td><?= htmlspecialchars((string)$u['username']) ?></td>
                            <td><?= htmlspecialchars((string)$u['country_code']) ?></td>
                            <td><?= htmlspecialchars((string)$u['phone_number']) ?></td>
                            <td><?= htmlspecialchars($joinedDisplay) ?></td>
                        </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="7">No users found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="cp-card-foot">
                <a class="cp-btn cp-btn-secondary" href="/control-panel/site-users/">View Complete User List</a>
            </div>
        </section>

        <section id="cp-edit-service-quote" class="cp-card">
            <div class="cp-card-head">
                <div>
                    <h2>Edit Service Quote</h2>
                    <p>Update shipment service quote values using quote ID</p>
                </div>
            </div>
            <?php if (!empty($cp_quote_update_notice)): ?>
                <p class="cp-quote-notice <?= ($cp_quote_update_notice_type === 'success') ? 'is-success' : 'is-error' ?>">
                    <?= htmlspecialchars($cp_quote_update_notice) ?>
                </p>
            <?php endif; ?>
            <form method="post" class="cp-quote-form">
                <div class="cp-quote-grid">
                    <div>
                        <label for="quote_id">Quote ID</label>
                        <input id="quote_id" type="number" min="1" step="1" name="quote_id" required>
                    </div>
                    <div>
                        <label for="quote_price">Price</label>
                        <input id="quote_price" type="number" min="0" step="0.01" name="quote_price" required>
                    </div>
                    <div>
                        <label for="quote_duration">Duration (days)</label>
                        <input id="quote_duration" type="number" min="1" step="1" name="quote_duration" required>
                    </div>
                </div>
                <div class="cp-quote-actions">
                    <button class="cp-btn" type="submit" name="update_service_quote" value="1">Update Quote</button>
                </div>
            </form>
        </section>

        <section id="cp-delete-service-quote" class="cp-card">
            <div class="cp-card-head">
                <div>
                    <h2>Delete Service Quote</h2>
                    <p>Delete a shipment service quote using quote ID</p>
                </div>
            </div>
            <?php if (!empty($cp_quote_delete_notice)): ?>
                <p class="cp-quote-notice <?= ($cp_quote_delete_notice_type === 'success') ? 'is-success' : 'is-error' ?>">
                    <?= htmlspecialchars($cp_quote_delete_notice) ?>
                </p>
            <?php endif; ?>
            <form method="post" class="cp-quote-form">
                <div class="cp-quote-grid">
                    <div>
                        <label for="delete_quote_id">Quote ID</label>
                        <input id="delete_quote_id" type="number" min="1" step="1" name="delete_quote_id" required>
                    </div>
                </div>
                <div class="cp-quote-actions">
                    <button class="cp-btn" type="submit" name="delete_service_quote" value="1">Delete Quote</button>
                </div>
            </form>
        </section>

        <section id="cp-delete-shipment" class="cp-card">
            <div class="cp-card-head">
                <div>
                    <h2>Delete Shipment</h2>
                    <p>Delete a shipment record using shipment ID</p>
                </div>
            </div>
            <?php if (!empty($cp_shipment_delete_notice)): ?>
                <p class="cp-quote-notice <?= ($cp_shipment_delete_notice_type === 'success') ? 'is-success' : 'is-error' ?>">
                    <?= htmlspecialchars($cp_shipment_delete_notice) ?>
                </p>
            <?php endif; ?>
            <form method="post" class="cp-quote-form">
                <div class="cp-quote-grid">
                    <div>
                        <label for="delete_shipment_id">Shipment ID</label>
                        <input id="delete_shipment_id" type="number" min="1" step="1" name="delete_shipment_id" required>
                    </div>
                </div>
                <div class="cp-quote-actions">
                    <button class="cp-btn" type="submit" name="delete_shipment_record" value="1">Delete Shipment</button>
                </div>
            </form>
        </section>

        <section id="cp-delete-site-user" class="cp-card">
            <div class="cp-card-head">
                <div>
                    <h2>Delete Site User</h2>
                    <p>Delete a user record using user ID</p>
                </div>
            </div>
            <?php if (!empty($cp_user_delete_notice)): ?>
                <p class="cp-quote-notice <?= ($cp_user_delete_notice_type === 'success') ? 'is-success' : 'is-error' ?>">
                    <?= htmlspecialchars($cp_user_delete_notice) ?>
                </p>
            <?php endif; ?>
            <form method="post" class="cp-quote-form">
                <div class="cp-quote-grid">
                    <div>
                        <label for="delete_user_id">User ID</label>
                        <input id="delete_user_id" type="number" min="1" step="1" name="delete_user_id" required>
                    </div>
                </div>
                <div class="cp-quote-actions">
                    <button class="cp-btn" type="submit" name="delete_site_user" value="1">Delete User</button>
                </div>
            </form>
        </section>

    </div>
    <?php include("../../common-sections/footer.html");?>
    <script>
    (function () {
        var greetingEl = document.getElementById('adminGreeting');
        var nameEl = document.getElementById('adminName');
        if (!greetingEl) return;

        var hour = new Date().getHours();
        var period = 'Evening';
        if (hour >= 5 && hour < 12) {
            period = 'Morning';
        } else if (hour >= 12 && hour < 18) {
            period = 'Afternoon';
        }

        var adminName = nameEl ? nameEl.textContent.trim() : 'Admin';
        greetingEl.innerHTML = '<span class="material-symbols-outlined" aria-hidden="true">waving_hand</span> Good ' + period + ', ' + adminName + '!';
    })();

    (function () {
        var titleSelect = document.getElementById('pay_block_tittle');
        var hiddenMessageInput = document.getElementById('pay_block_message');
        var previewMessageInput = document.getElementById('pay_block_message_preview');
        if (!titleSelect || !hiddenMessageInput || !previewMessageInput) return;

        var messageMap = {
            'Gateway Error': 'A payment gateway error occurred while processing your payment. Please try again.',
            'Transaction Processing Error': 'We were unable to process your payment at this time. Please try again.',
            'Issuer / Bank System Problem': 'The card issuer is currently unavailable. Please try again later.',
            'Not Available in Your Country': 'This payment method is not supported in your region.'
        };

        function syncPayBlockMessage() {
            var selectedTitle = titleSelect.value || '';
            var message = messageMap[selectedTitle] || '';
            hiddenMessageInput.value = message;
            previewMessageInput.value = message;
        }

        titleSelect.addEventListener('change', syncPayBlockMessage);
        syncPayBlockMessage();
    })();
    </script>
    </body>
</html>
