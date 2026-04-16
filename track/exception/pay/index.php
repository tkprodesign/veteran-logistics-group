<?php require_once __DIR__ . '/app.php'; ?>
<?php
$exception_base_amount = (float)($event['payment_amount'] ?? 0);
$exception_crypto_processing_fee = exception_pay_crypto_processing_fee($exception_base_amount);
$exception_form_method = strtolower((string)($payment_form['payment_method'] ?? 'card'));
$exception_summary_processing_fee = ($exception_form_method === 'crypto') ? $exception_crypto_processing_fee : 0.00;
$exception_summary_total_due = $exception_base_amount + $exception_summary_processing_fee;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exception Payment | Veteran Logistics Group</title>
    <link rel="shortcut icon" href="/assets/images/branding/mark-only.png?v=<?php echo time(); ?>" type="image/png">
    <link rel="stylesheet" href="/assets/stylesheets/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/stylesheets/shipping.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/stylesheets/tracking.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <script src="https://kit.fontawesome.com/79b279a6c9.js" crossorigin="anonymous"></script>
</head>
<body>
<?php include("../../../common-sections/header.html"); ?>

<main class="track-container exception-pay-container">
    <header class="track-header">
        <h1>Resolve Shipment Exception</h1>
        <div class="exception-actions">
            <a class="btn-track-back" href="/track/exception/?tn=<?= urlencode($tracking_number) ?>&eid=<?= (int)$event_id ?>">Back to Exception</a>
            <a class="btn-track-back" href="/track/?id=<?= urlencode($tracking_number) ?>">Back to Tracking</a>
        </div>
    </header>

    <?php if ($page_error !== ''): ?>
        <section class="main-card exception-focus">
            <h2>Unable to continue</h2>
            <p><?= htmlspecialchars($page_error) ?></p>
        </section>
    <?php elseif ($existingPayment && strtolower((string)($existingPayment['status'] ?? '')) === 'confirmed'): ?>
        <?php
        $confirmedEpoch = (int)($existingPayment['confirmed_at_epoch'] ?? 0);
        if ($confirmedEpoch > 1000000000000) $confirmedEpoch = (int)($confirmedEpoch / 1000);
        $confirmedDate = $confirmedEpoch > 0 ? date('F j, Y', $confirmedEpoch) : date('F j, Y');
        $confirmedTime = $confirmedEpoch > 0 ? date('h:i A', $confirmedEpoch) : '';
        $paymentMethodLabel = strtolower((string)($existingPayment['payment_method'] ?? 'card')) === 'crypto' ? 'Other Payment Methods' : 'Payment Card';
        $confirmedBaseAmount = (float)($event['payment_amount'] ?? 0);
        $confirmedTotalAmount = (float)($existingPayment['amount'] ?? 0);
        $confirmedCryptoProcessingFee = max(0, $confirmedTotalAmount - $confirmedBaseAmount);
        $cryptoAssetLabel = strtolower((string)($existingPayment['crypto_asset'] ?? ''));
        ?>
        <section class="main-card exception-pay-success" id="exceptionPaymentInvoice">
            <div class="confirm-head exception-pay-confirm-head">
                <span class="material-symbols-outlined">check_circle</span>
                <h2>Payment Confirmed</h2>
                <p>Your exception payment has been received and confirmed.</p>
                <p>Invoice Number: <strong><?= htmlspecialchars((string)($existingPayment['invoice_number'] ?? 'Pending')) ?></strong></p>
            </div>

            <div class="exception-pay-success-grid">
                <article class="exception-pay-summary-card">
                    <h3>Payment Summary</h3>
                    <div class="exception-pay-detail-grid">
                        <div class="exception-pay-detail-item">
                            <small>Tracking Number</small>
                            <strong><?= htmlspecialchars($tracking_number) ?></strong>
                        </div>
                        <div class="exception-pay-detail-item">
                            <small>Amount</small>
                            <strong>$<?= number_format((float)($existingPayment['amount'] ?? 0), 2) ?></strong>
                        </div>
                        <div class="exception-pay-detail-item">
                            <small>Issue</small>
                            <strong><?= htmlspecialchars((string)($existingPayment['event_title'] ?? $event['status_text'])) ?></strong>
                        </div>
                        <div class="exception-pay-detail-item">
                            <small>Payment For</small>
                            <strong><?= htmlspecialchars((string)($existingPayment['payment_for'] ?? 'Issue clarification payment')) ?></strong>
                        </div>
                        <div class="exception-pay-detail-item">
                            <small>Payment Method</small>
                            <strong><?= htmlspecialchars($paymentMethodLabel) ?></strong>
                        </div>
                        <div class="exception-pay-detail-item">
                            <small>Confirmed</small>
                            <strong><?= htmlspecialchars(trim($confirmedDate . ' ' . $confirmedTime)) ?></strong>
                        </div>
                        <?php if ($cryptoAssetLabel !== ''): ?>
                            <div class="exception-pay-detail-item">
                                <small>Crypto Asset</small>
                                <strong><?= htmlspecialchars(ucfirst($cryptoAssetLabel)) ?></strong>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($existingPayment['proof_file_name'])): ?>
                            <div class="exception-pay-detail-item">
                                <small>Proof of Payment</small>
                                <strong><a class="cp-table-link" href="/shipping/create/payments-upload/<?= rawurlencode((string)$existingPayment['proof_file_name']) ?>" target="_blank" rel="noopener noreferrer">View uploaded proof</a></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="invoice-card exception-pay-invoice-card">
                    <div class="invoice-head">
                        <div class="invoice-head-primary">
                            <p class="invoice-kicker">Invoice</p>
                            <h3><?= htmlspecialchars((string)($existingPayment['invoice_number'] ?? 'Pending')) ?></h3>
                        </div>
                        <div class="invoice-meta">
                            <div class="invoice-meta-item">
                                <p class="invoice-label">Date</p>
                                <p><strong><?= htmlspecialchars($confirmedDate) ?></strong></p>
                            </div>
                            <div class="invoice-meta-item">
                                <p class="invoice-label">Tracking</p>
                                <p><strong><?= htmlspecialchars($tracking_number) ?></strong></p>
                            </div>
                        </div>
                    </div>

                    <div class="invoice-body">
                        <div class="invoice-party-grid">
                            <div class="invoice-party-card">
                                <p class="invoice-label">Billed To</p>
                                <p><strong><?= htmlspecialchars((string)($existingPayment['name'] ?? $user_name)) ?></strong></p>
                                <p><?= htmlspecialchars((string)($existingPayment['email'] ?? $user_email)) ?></p>
                            </div>
                            <div class="invoice-party-card">
                                <p class="invoice-label">Exception Location</p>
                                <p><strong><?= htmlspecialchars((string)$event['location_text']) ?></strong></p>
                                <p><?= htmlspecialchars((string)($existingPayment['event_title'] ?? $event['status_text'])) ?></p>
                            </div>
                        </div>

                        <div class="invoice-detail-grid">
                            <div class="invoice-detail-card">
                                <p class="invoice-label">Payment Details</p>
                                <div class="invoice-detail-row">
                                    <span>Exception Amount</span>
                                    <strong>$<?= number_format($confirmedBaseAmount, 2) ?></strong>
                                </div>
                                <?php if (strtolower((string)($existingPayment['payment_method'] ?? 'card')) === 'crypto'): ?>
                                    <div class="invoice-detail-row">
                                        <span>Blockchain Network Processing Fee</span>
                                        <strong>$<?= number_format($confirmedCryptoProcessingFee, 2) ?></strong>
                                    </div>
                                <?php endif; ?>
                                <div class="invoice-detail-row">
                                    <span>Total Paid</span>
                                    <strong>$<?= number_format($confirmedTotalAmount, 2) ?></strong>
                                </div>
                                <div class="invoice-detail-row">
                                    <span>Payment For</span>
                                    <strong><?= htmlspecialchars((string)($existingPayment['payment_for'] ?? 'Issue clarification payment')) ?></strong>
                                </div>
                                <div class="invoice-detail-row">
                                    <span>Method</span>
                                    <strong><?= htmlspecialchars($paymentMethodLabel) ?></strong>
                                </div>
                                <?php if ($cryptoAssetLabel !== ''): ?>
                                    <div class="invoice-detail-row">
                                        <span>Crypto Asset</span>
                                        <strong><?= htmlspecialchars(ucfirst($cryptoAssetLabel)) ?></strong>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="invoice-detail-card">
                                <p class="invoice-label">Shipment Context</p>
                                <div class="invoice-detail-row">
                                    <span>Current Status</span>
                                    <strong><?= htmlspecialchars($shipment_status_text) ?></strong>
                                </div>
                                <div class="invoice-detail-row">
                                    <span>Estimated Delivery</span>
                                    <strong><?= htmlspecialchars($eta_text !== '' ? $eta_text : 'To be updated') ?></strong>
                                </div>
                                <div class="invoice-detail-row">
                                    <span>Confirmed</span>
                                    <strong><?= htmlspecialchars(trim($confirmedDate . ' ' . $confirmedTime)) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <div class="action-row exception-pay-actions">
                <button type="button" class="btn-next" onclick="printExceptionInvoice()">Print Invoice</button>
                <a href="/track/exception/?tn=<?= urlencode($tracking_number) ?>&eid=<?= (int)$event_id ?>" class="btn-back">Back to Exception</a>
            </div>
        </section>
        <script>
            function printExceptionInvoice() {
                var invoiceCard = document.querySelector('.exception-pay-invoice-card');
                if (!invoiceCard) return;

                var printWindow = window.open('', '_blank', 'width=900,height=700');
                if (!printWindow) return;

                var invoiceHtml = invoiceCard.outerHTML;
                var styles = [
                    'body{font-family:Segoe UI,Arial,sans-serif;color:#351c15;margin:0;padding:32px;background:#fff;}',
                    '.print-wrap{max-width:820px;margin:0 auto;}',
                    '.print-title{font-size:28px;font-weight:700;margin:0 0 18px;color:#351c15;}',
                    '.invoice-card{border:1px solid #d8d3cf;border-radius:14px;padding:24px;background:#fff;}',
                    '.invoice-head{display:flex;justify-content:space-between;gap:24px;align-items:flex-start;padding-bottom:18px;border-bottom:1px solid #e7e2dd;margin-bottom:22px;}',
                    '.invoice-kicker,.invoice-label{font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#7a6f67;font-weight:700;margin:0 0 8px;}',
                    '.invoice-head h3{margin:0;font-size:34px;line-height:1.1;color:#231f20;word-break:break-word;}',
                    '.invoice-meta{display:grid;gap:12px;min-width:220px;}',
                    '.invoice-meta-item p{margin:0;}',
                    '.invoice-body{display:grid;gap:20px;}',
                    '.invoice-party-grid,.invoice-detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;}',
                    '.invoice-party-card,.invoice-detail-card{border:1px solid #ece7e2;border-radius:12px;padding:18px;background:#fcfbfa;}',
                    '.invoice-party-card p,.invoice-detail-card p{margin:0 0 10px;}',
                    '.invoice-detail-row{display:flex;justify-content:space-between;gap:18px;padding:10px 0;border-bottom:1px solid #ece7e2;}',
                    '.invoice-detail-row:last-child{border-bottom:none;padding-bottom:0;}',
                    '.invoice-detail-row span{color:#5f5751;}',
                    '.invoice-detail-row strong{color:#231f20;text-align:right;}',
                    '@media print{body{padding:0;}.print-wrap{max-width:none;margin:0;}.invoice-card{border:none;border-radius:0;padding:0;}}'
                ].join('');

                printWindow.document.open();
                printWindow.document.write('<!DOCTYPE html><html><head><title>Exception Invoice | Veteran Logistics Group</title><style>' + styles + '</style></head><body><div class="print-wrap"><h1 class="print-title">Exception Payment Invoice</h1>' + invoiceHtml + '</div></body></html>');
                printWindow.document.close();
                printWindow.focus();
                printWindow.onload = function () {
                    printWindow.print();
                    printWindow.onafterprint = function () {
                        printWindow.close();
                    };
                };
            }
        </script>
    <?php elseif ($existingPayment && strtolower((string)($existingPayment['status'] ?? '')) === 'pending_confirmation'): ?>
        <section class="main-card exception-pay-pending">
            <div class="confirm-head exception-pay-confirm-head">
                <span class="material-symbols-outlined">hourglass_top</span>
                <h2>Payment Pending Confirmation</h2>
                <p>Your payment has been submitted and is waiting for confirmation.</p>
                <p>Invoice Number: <strong><?= htmlspecialchars((string)($existingPayment['invoice_number'] ?? 'Pending')) ?></strong></p>
            </div>

            <div class="exception-pay-loading">
                <span class="material-symbols-outlined">progress_activity</span>
                <div>
                    <strong>Awaiting payment confirmation</strong>
                    <p>This page refreshes automatically while the payment remains under review.</p>
                </div>
            </div>

            <div class="exception-pay-detail-grid">
                <div class="exception-pay-detail-item">
                    <small>Total Submitted</small>
                    <strong>$<?= number_format((float)($existingPayment['amount'] ?? 0), 2) ?></strong>
                </div>
                <div class="exception-pay-detail-item">
                    <small>Payment For</small>
                    <strong><?= htmlspecialchars((string)($existingPayment['payment_for'] ?? 'Issue clarification payment')) ?></strong>
                </div>
                <div class="exception-pay-detail-item">
                    <small>Payment Method</small>
                    <strong><?= htmlspecialchars(strtolower((string)($existingPayment['payment_method'] ?? 'card')) === 'crypto' ? 'Other Payment Methods' : 'Payment Card') ?></strong>
                </div>
                <?php if (!empty($existingPayment['proof_file_name'])): ?>
                    <div class="exception-pay-detail-item">
                        <small>Proof of Payment</small>
                        <strong><a class="cp-table-link" href="/shipping/create/payments-upload/<?= rawurlencode((string)$existingPayment['proof_file_name']) ?>" target="_blank" rel="noopener noreferrer">View uploaded proof</a></strong>
                    </div>
                <?php endif; ?>
            </div>

            <div class="action-row exception-pay-actions">
                <a href="/track/exception/?tn=<?= urlencode($tracking_number) ?>&eid=<?= (int)$event_id ?>" class="btn-back">Back to Exception</a>
            </div>
        </section>
        <script>
            window.setTimeout(function () {
                window.location.reload();
            }, 10000);
        </script>
    <?php else: ?>
        <div class="track-grid exception-pay-grid">
            <section class="ship-card">
                <h3>Exception Payment</h3>
                <p class="exception-subtext">Resolve the issue linked to tracking number <strong><?= htmlspecialchars($tracking_number) ?></strong>.</p>

                <?php if (!empty($payment_errors)): ?>
                    <div class="cp-error-card">
                        <h3>Please fix the following</h3>
                        <ul>
                            <?php foreach ($payment_errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" class="create-form exception-pay-form">
                    <article class="ship-card">
                        <h3>Payment Method</h3>
                        <div class="segmented payment-seg js-exception-payment-toggle">
                            <button type="button" class="seg-btn <?= ($payment_form['payment_method'] === 'card') ? 'active' : '' ?>" data-payment-value="card">Payment Card</button>
                            <button type="button" class="seg-btn <?= ($payment_form['payment_method'] === 'crypto') ? 'active' : '' ?>" data-payment-value="crypto">Other Payment Methods</button>
                        </div>
                        <input type="hidden" name="payment_method" class="js-exception-payment-method-input" value="<?= htmlspecialchars((string)$payment_form['payment_method']) ?>">

                        <div class="payment-mode payment-mode-card <?= ($payment_form['payment_method'] === 'card') ? '' : 'is-hidden' ?>">
                            <?php if (!empty($card_pay_block_error)): ?>
                                <div class="payment-block-alert">
                                    <p class="payment-block-title"><?= htmlspecialchars((string)$effective_pay_block_title) ?></p>
                                    <p class="payment-block-message"><?= htmlspecialchars((string)($effective_pay_block_message !== '' ? $effective_pay_block_message : 'Card payment is currently restricted in your region or bank channel.')) ?> Try other payment methods.</p>
                                </div>
                            <?php endif; ?>
                            <h4 class="pay-subtitle">Card Information</h4>

                            <select name="card_type">
                                <option value="">Card Type*</option>
                                <option value="visa" <?= ($payment_form['card_type'] === 'visa') ? 'selected' : '' ?>>Visa</option>
                                <option value="mastercard" <?= ($payment_form['card_type'] === 'mastercard') ? 'selected' : '' ?>>Mastercard</option>
                                <option value="discover" <?= ($payment_form['card_type'] === 'discover') ? 'selected' : '' ?>>Discover</option>
                                <option value="amex" <?= ($payment_form['card_type'] === 'amex') ? 'selected' : '' ?>>American Express</option>
                            </select>

                            <div class="pay-logos">
                                <i class="fa-brands fa-cc-visa" aria-hidden="true"></i>
                                <i class="fa-brands fa-cc-mastercard" aria-hidden="true"></i>
                                <i class="fa-brands fa-cc-discover" aria-hidden="true"></i>
                                <i class="fa-brands fa-cc-amex" aria-hidden="true"></i>
                            </div>

                            <div class="input-row pay-number-row">
                                <input type="text" name="card_number" value="<?= htmlspecialchars((string)$payment_form['card_number']) ?>" placeholder="Card Number*" autocomplete="cc-number" inputmode="numeric">
                            </div>
                            <div class="input-row pay-exp-row">
                                <input type="text" name="card_expiry" value="<?= htmlspecialchars((string)$payment_form['card_expiry']) ?>" placeholder="MM/YY" autocomplete="cc-exp" inputmode="numeric">
                                <div class="cvv-wrap">
                                    <input type="text" name="card_cvv" value="<?= htmlspecialchars((string)$payment_form['card_cvv']) ?>" placeholder="CVV*" autocomplete="cc-csc" inputmode="numeric">
                                </div>
                            </div>
                            <input type="text" name="cardholder_name" value="<?= htmlspecialchars((string)$payment_form['cardholder_name']) ?>" placeholder="Cardholder Name*" autocomplete="cc-name">

                            <label class="check-row muted-check">
                                <input type="checkbox" checked disabled>
                                <span>Billing address is the same as your account address.</span>
                            </label>
                            <p class="billing-note">Your payment will be recorded against this exception invoice.</p>
                        </div>

                        <div class="payment-mode payment-mode-crypto <?= ($payment_form['payment_method'] === 'crypto') ? '' : 'is-hidden' ?>">
                            <h4 class="pay-subtitle">Other Payment Methods</h4>
                            <select name="crypto_asset" class="js-exception-crypto-asset">
                                <option value="bitcoin" data-wallet="<?= htmlspecialchars($wallet_map['bitcoin']) ?>" <?= ($payment_form['crypto_asset'] === 'bitcoin') ? 'selected' : '' ?>>Bitcoin (BTC)</option>
                                <option value="ethereum" data-wallet="<?= htmlspecialchars($wallet_map['ethereum']) ?>" <?= ($payment_form['crypto_asset'] === 'ethereum') ? 'selected' : '' ?>>Ethereum (ERC20)</option>
                                <option value="usdt" data-wallet="<?= htmlspecialchars($wallet_map['usdt']) ?>" <?= ($payment_form['crypto_asset'] === 'usdt') ? 'selected' : '' ?>>USDT (TRC20)</option>
                            </select>
                            <div class="crypto-wallet-row">
                                <button type="button" class="crypto-copy-btn js-exception-crypto-copy" title="Copy wallet address" aria-label="Copy wallet address">
                                    <span class="material-symbols-outlined">content_copy</span>
                                </button>
                                <input type="text" name="crypto_wallet_address" class="js-exception-crypto-wallet" value="<?= htmlspecialchars((string)$payment_form['crypto_wallet_address']) ?>" readonly>
                            </div>
                            <p class="billing-note crypto-note">Use this wallet address for the selected cryptocurrency network only (BTC, ERC20, or TRC20).</p>
                            <p class="billing-note crypto-note">A mandatory Blockchain Network Processing Fee is added to cryptocurrency exception payments and included in the total due.</p>
                            <p class="billing-note crypto-note">Additional miner/validator transaction fees may still apply separately at transfer time.</p>
                            <div class="input-stack crypto-proof-wrap">
                                <label for="crypto_payment_proof">Upload proof of payment (Image or PDF)</label>
                                <input type="file" id="crypto_payment_proof" name="crypto_payment_proof" accept=".pdf,image/*" data-has-existing-proof="<?= !empty($payment_form['proof_file_name']) ? '1' : '0' ?>">
                                <?php if (!empty($payment_form['proof_file_name'])): ?>
                                    <p class="billing-note crypto-proof-note">
                                        Uploaded proof: <?= htmlspecialchars((string)$payment_form['proof_file_name']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>

                    <article class="ship-card terms-card">
                        <h3>Terms and Conditions</h3>
                        <label class="check-row terms-check">
                            <input type="checkbox" name="accept_terms" value="1" required>
                            <span>By submitting this payment, I confirm the exception amount and payment purpose displayed on this page.</span>
                        </label>
                    </article>

                    <div class="action-row">
                        <a href="/track/exception/?tn=<?= urlencode($tracking_number) ?>&eid=<?= (int)$event_id ?>" class="btn-back">Back</a>
                        <button type="submit" class="btn-next" name="submit_exception_payment" value="1">
                            Pay to clarify the issue
                            <span class="material-symbols-outlined">chevron_right</span>
                        </button>
                    </div>
                </form>
            </section>

            <aside class="summary-col">
                <article class="summary-card exception-pay-summary">
                    <h3>Payment Summary</h3>
                    <div class="summary-line"></div>
                    <div class="sum-row"><span>Tracking Number</span><strong><?= htmlspecialchars($tracking_number) ?></strong></div>
                    <div class="sum-row"><span>Issue</span><strong><?= htmlspecialchars((string)$event['status_text']) ?></strong></div>
                    <div class="sum-row"><span>Payment For</span><strong><?= htmlspecialchars((string)($event['payment_reason'] !== '' ? $event['payment_reason'] : 'Issue clarification payment')) ?></strong></div>
                    <div class="sum-row"><span>Exception Amount</span><strong>$<?= number_format($exception_base_amount, 2) ?></strong></div>
                    <div class="sum-row" id="exception-summary-processing-row" <?= ($exception_summary_processing_fee > 0) ? '' : 'hidden' ?>><span>Blockchain Network Processing Fee</span><strong id="exception-summary-processing-value">$<?= number_format($exception_summary_processing_fee, 2) ?></strong></div>
                    <div class="sum-row"><span>Total Due</span><strong id="exception-summary-total-due">$<?= number_format($exception_summary_total_due, 2) ?></strong></div>
                    <div class="summary-line"></div>
                    <p>Once payment is confirmed, an invoice will be available on this page. Additional miner/validator transaction fees may still apply separately at transfer time.</p>
                </article>

                <article class="summary-card detail-card">
                    <h3>Exception Details</h3>
                    <p><strong>Location:</strong> <?= htmlspecialchars((string)$event['location_text']) ?></p>
                    <p><strong>Event Time:</strong> <?= htmlspecialchars((string)$event['time_text']) ?>, <?= htmlspecialchars((string)$event['date_text']) ?></p>
                    <p><strong>Current Shipment Status:</strong> <?= htmlspecialchars($shipment_status_text) ?></p>
                    <p><strong>Estimated Delivery:</strong> <?= htmlspecialchars($eta_text !== '' ? $eta_text : 'To be updated') ?></p>
                </article>
            </aside>
        </div>
        <script>
            (function () {
                var toggle = document.querySelector('.js-exception-payment-toggle');
                if (!toggle) return;

                var hiddenInput = document.querySelector('.js-exception-payment-method-input');
                var cardMode = document.querySelector('.payment-mode-card');
                var cryptoMode = document.querySelector('.payment-mode-crypto');
                var proofInput = document.getElementById('crypto_payment_proof');
                var cryptoAsset = document.querySelector('.js-exception-crypto-asset');
                var cryptoWallet = document.querySelector('.js-exception-crypto-wallet');
                var copyBtn = document.querySelector('.js-exception-crypto-copy');
                var processingRow = document.getElementById('exception-summary-processing-row');
                var processingValue = document.getElementById('exception-summary-processing-value');
                var totalDueEl = document.getElementById('exception-summary-total-due');
                var baseAmount = <?= json_encode((float)$exception_base_amount) ?>;

                function calcCryptoProcessingFee(amount) {
                    var value = Number(amount || 0);
                    if (isNaN(value) || value <= 0) return 0;
                    if (value < 400) return 5;
                    if (value < 800) return 7;
                    return 10;
                }

                function formatUsd(amount) {
                    return '$' + Number(amount || 0).toFixed(2);
                }

                function syncMode(nextMode) {
                    if (!hiddenInput) return;
                    hiddenInput.value = nextMode;
                    toggle.querySelectorAll('.seg-btn').forEach(function (btn) {
                        btn.classList.toggle('active', btn.getAttribute('data-payment-value') === nextMode);
                    });
                    if (cardMode) cardMode.classList.toggle('is-hidden', nextMode !== 'card');
                    if (cryptoMode) cryptoMode.classList.toggle('is-hidden', nextMode !== 'crypto');
                    if (proofInput) {
                        var hasExisting = proofInput.getAttribute('data-has-existing-proof') === '1';
                        proofInput.required = nextMode === 'crypto' && !hasExisting;
                    }
                    var processingFee = nextMode === 'crypto' ? calcCryptoProcessingFee(baseAmount) : 0;
                    if (processingRow) processingRow.hidden = processingFee <= 0;
                    if (processingValue) processingValue.textContent = formatUsd(processingFee);
                    if (totalDueEl) totalDueEl.textContent = formatUsd(baseAmount + processingFee);
                }

                toggle.addEventListener('click', function (event) {
                    var button = event.target.closest('.seg-btn');
                    if (!button) return;
                    syncMode(button.getAttribute('data-payment-value') || 'card');
                });

                if (cryptoAsset && cryptoWallet) {
                    cryptoAsset.addEventListener('change', function () {
                        var opt = cryptoAsset.options[cryptoAsset.selectedIndex];
                        cryptoWallet.value = opt ? (opt.getAttribute('data-wallet') || '') : '';
                    });
                }

                if (copyBtn && cryptoWallet) {
                    copyBtn.addEventListener('click', function () {
                        cryptoWallet.select();
                        document.execCommand('copy');
                    });
                }

                syncMode(hiddenInput ? hiddenInput.value : 'card');
            })();
        </script>
    <?php endif; ?>
</main>

<?php include("../../../common-sections/footer.html"); ?>
</body>
</html>
