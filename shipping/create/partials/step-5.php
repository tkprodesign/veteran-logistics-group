<?php
$invoice_service_label = htmlspecialchars((string)($created_shipment['service_label'] ?? (($createdServiceLabel ?? 'Economy'))));
$invoice_tracking_number = htmlspecialchars((string)($created_shipment['tracking_number'] ?? 'Unavailable'));
$invoice_sender_name = htmlspecialchars((string)($created_shipment['sender_name'] ?? ($shipment_form['sender_name'] ?? '-')));
$invoice_sender_email = htmlspecialchars((string)($created_shipment['sender_email'] ?? ($shipment_form['sender_email'] ?? '')));
$invoice_sender_phone = htmlspecialchars((string)($created_shipment['sender_phone'] ?? ($shipment_form['sender_phone'] ?? '')));
$invoice_sender_address = htmlspecialchars((string)($created_shipment['origin_address'] ?? ($shipment_form['origin_address'] ?? '-')));
$invoice_receiver_name = htmlspecialchars((string)($created_shipment['receiver_name'] ?? ($shipment_form['receiver_name'] ?? '-')));
$invoice_receiver_email = htmlspecialchars((string)($created_shipment['receiver_email'] ?? ($shipment_form['receiver_email'] ?? '')));
$invoice_receiver_phone = htmlspecialchars((string)($created_shipment['receiver_phone'] ?? ($shipment_form['receiver_phone'] ?? '')));
$invoice_receiver_address = htmlspecialchars((string)($created_shipment['destination_address'] ?? ($shipment_form['destination_address'] ?? '-')));
$invoice_pickup_fee = (float)($created_shipment['pickup_total'] ?? (((string)($shipment_form['pickup_option'] ?? 'dropoff') === 'pickup') ? 15.75 : 0.00));
$invoice_carbon_fee = (float)($created_shipment['carbon_total'] ?? (!empty($shipment_form['opt_carbon']) ? 0.05 : 0.00));
$invoice_signature_fee = (float)($created_shipment['signature_total'] ?? (!empty($shipment_form['opt_signature']) ? 7.70 : 0.00));
$invoice_adult_signature_fee = (float)($created_shipment['adult_signature_total'] ?? (!empty($shipment_form['opt_adult_signature']) ? 9.35 : 0.00));
$invoice_options_total = $invoice_signature_fee + $invoice_adult_signature_fee;
$invoice_total = (float)($created_shipment['total_charges'] ?? 0);
$invoice_service_total = (float)($created_shipment['service_total'] ?? 0);
$invoice_tax_total = (float)($created_shipment['tax_total'] ?? 0);
$invoice_crypto_processing_fee = (float)($created_shipment['crypto_processing_fee'] ?? 0);
$invoice_promo_discount = (float)($created_shipment['promo_discount_total'] ?? max(0, ($invoice_service_total + $invoice_pickup_fee + $invoice_carbon_fee + $invoice_options_total + $invoice_crypto_processing_fee + $invoice_tax_total) - $invoice_total));
$invoice_created_date = date('M j, Y');
$invoice_payment_method_key = strtolower(trim((string)($created_shipment['payment_method'] ?? ($shipment_form['payment_method'] ?? 'card'))));
$invoice_payment_method = ($invoice_payment_method_key === 'crypto') ? 'Other Payment Methods' : 'Payment Card';
$invoice_crypto_processing_fee = ($invoice_payment_method_key === 'crypto') ? $invoice_crypto_processing_fee : 0.00;
$invoice_crypto_asset = htmlspecialchars((string)($created_shipment['crypto_asset'] ?? ($shipment_form['crypto_asset'] ?? '')));
$invoice_number = 'INV-' . preg_replace('/[^A-Z0-9]/', '', strtoupper((string)($created_shipment['tracking_number'] ?? '000000')));
$invoice_weight = htmlspecialchars((string)($created_shipment['weight'] ?? ($shipment_form['weight'] ?? '0')));
$invoice_length = htmlspecialchars((string)($created_shipment['length'] ?? ($shipment_form['length'] ?? '0')));
$invoice_width = htmlspecialchars((string)($created_shipment['width'] ?? ($shipment_form['width'] ?? '0')));
$invoice_height = htmlspecialchars((string)($created_shipment['height'] ?? ($shipment_form['height'] ?? '0')));
$invoice_pickup_label = (
    ((string)($created_shipment['pickup_option'] ?? ($shipment_form['pickup_option'] ?? 'dropoff')) === 'pickup')
        ? 'Selected Pickup Date'
        : 'Earliest Pickup Date'
) . ': ' . htmlspecialchars((string)($estimated_dropoff_label ?? ''));
$invoice_package_contents_raw = trim((string)($created_shipment['package_contents'] ?? ($shipment_form['package_contents'] ?? '')));
$invoice_package_contents = htmlspecialchars($invoice_package_contents_raw !== '' ? $invoice_package_contents_raw : 'Not provided');
$invoice_reference_number = htmlspecialchars((string)($created_shipment['reference_number'] ?? ($shipment_form['reference_number'] ?? '')));
$invoice_declared_value = (float)($created_shipment['parcel_value'] ?? ($shipment_form['parcel_value'] ?? 0));
$invoice_shipment_type = htmlspecialchars((string)($created_shipment['shipment_class_label'] ?? ($selected_shipment_class_label ?? 'Parcel')));
$invoice_rows = [
    ['label' => 'Service', 'amount' => $invoice_service_total],
    [
        'label' => ($invoice_pickup_fee > 0 ? 'Custom Pickup Fee' : 'Postman Pickup'),
        'amount' => $invoice_pickup_fee
    ],
];
if ($invoice_carbon_fee > 0) {
    $invoice_rows[] = ['label' => 'Carbon Neutral Charges', 'amount' => $invoice_carbon_fee];
}
if ($invoice_signature_fee > 0) {
    $invoice_rows[] = ['label' => 'Signature Required', 'amount' => $invoice_signature_fee];
}
if ($invoice_adult_signature_fee > 0) {
    $invoice_rows[] = ['label' => 'Adult Signature Required', 'amount' => $invoice_adult_signature_fee];
}
if ($invoice_payment_method_key === 'crypto' && $invoice_crypto_processing_fee > 0) {
    $invoice_rows[] = ['label' => 'Blockchain Network Processing Fee', 'amount' => $invoice_crypto_processing_fee];
}
$invoice_rows[] = ['label' => 'Taxes and Duties', 'amount' => $invoice_tax_total];
?>
<article class="ship-card confirm-card">
    <div class="confirm-head">
        <span class="material-symbols-outlined">check_circle</span>
        <h2>Label Ready</h2>
        <p>Your shipment has been created successfully.</p>
        <p>Tracking Number: <strong><?= htmlspecialchars((string)($created_shipment['tracking_number'] ?? 'Unavailable')) ?></strong></p>
    </div>

    <div class="confirm-grid">
        <div class="shipping-label" id="printableLabel">
            <div class="label-header">
                <span class="ups-text"><?php
                    $createdService = (string)($created_shipment['shipment_type'] ?? 'standard');
                    $createdServiceLabel = ($createdService === 'overnight') ? 'Express' : (($createdService === 'express') ? 'Priority' : 'Economy');
                    echo strtoupper($createdServiceLabel);
                ?></span>
                <strong><?= htmlspecialchars((string)($created_shipment['weight'] ?? '0')) ?> LBS</strong>
            </div>
            <hr class="thick-line">
            <div class="address-section">
                <p><strong>FROM:</strong> <?= htmlspecialchars((string)($created_shipment['sender_name'] ?? '-')) ?></p>
                <p><?= htmlspecialchars((string)($created_shipment['origin_address'] ?? '-')) ?></p>
                <p><strong>TO:</strong> <?= htmlspecialchars((string)($created_shipment['receiver_name'] ?? '-')) ?></p>
                <p><?= htmlspecialchars((string)($created_shipment['destination_address'] ?? '-')) ?></p>
            </div>
            <hr class="thick-line">
            <div class="tracking-section">
                <svg id="barcode"></svg>
                <p>TRACKING #: <?= htmlspecialchars((string)($created_shipment['tracking_number'] ?? 'Unavailable')) ?></p>
            </div>
        </div>

        <div class="confirm-side-stack">
            <div class="next-steps-card">
                <h3>What happens next?</h3>
                <ol>
                    <li>Print this label and attach it to your package.</li>
                    <li>Drop off your package or schedule pickup.</li>
                    <li>Track progress live from your dashboard.</li>
                </ol>
                <div class="confirm-mini">
                    <p><strong>Service:</strong> <?php
                        $createdService2 = (string)($created_shipment['shipment_type'] ?? 'standard');
                        echo htmlspecialchars(($createdService2 === 'overnight') ? 'Express' : (($createdService2 === 'express') ? 'Priority' : 'Economy'));
                    ?></p>
                    <p><strong>Estimated Delivery:</strong> <?= date('M j, Y', time() + 3 * 86400) ?></p>
                </div>
                <a href="/track/?id=<?= urlencode((string)($created_shipment['tracking_number'] ?? '')) ?>" class="btn-back">Track Shipment</a>
            </div>
        </div>
    </div>

    <div class="invoice-card" id="printableInvoice">
        <div class="invoice-head">
            <div class="invoice-head-primary">
                <p class="invoice-kicker">Invoice</p>
                <h3><?= $invoice_number ?></h3>
            </div>
            <div class="invoice-meta">
                <div class="invoice-meta-item">
                    <span class="invoice-label">Date</span>
                    <p><?= htmlspecialchars($invoice_created_date) ?></p>
                </div>
                <div class="invoice-meta-item">
                    <span class="invoice-label">Tracking</span>
                    <p><?= $invoice_tracking_number ?></p>
                </div>
            </div>
        </div>
        <div class="invoice-body">
            <div class="invoice-party-grid">
                <div class="invoice-party-card">
                    <p class="invoice-label">Sender</p>
                    <p><?= $invoice_sender_name ?></p>
                    <?php if ($invoice_sender_email !== ''): ?><p><?= $invoice_sender_email ?></p><?php endif; ?>
                    <?php if ($invoice_sender_phone !== ''): ?><p><?= $invoice_sender_phone ?></p><?php endif; ?>
                    <p><?= $invoice_sender_address ?></p>
                </div>
                <div class="invoice-party-card">
                    <p class="invoice-label">Recipient</p>
                    <p><?= $invoice_receiver_name ?></p>
                    <?php if ($invoice_receiver_email !== ''): ?><p><?= $invoice_receiver_email ?></p><?php endif; ?>
                    <?php if ($invoice_receiver_phone !== ''): ?><p><?= $invoice_receiver_phone ?></p><?php endif; ?>
                    <p><?= $invoice_receiver_address ?></p>
                </div>
            </div>
            <div class="invoice-detail-grid">
                <div class="invoice-detail-card">
                    <p class="invoice-label">Shipment Details</p>
                    <div class="invoice-detail-row"><span>Shipment Type</span><strong><?= $invoice_shipment_type ?></strong></div>
                    <div class="invoice-detail-row"><span>Weight</span><strong><?= $invoice_weight ?> lbs</strong></div>
                    <div class="invoice-detail-row"><span>Dimensions</span><strong><?= $invoice_length ?> x <?= $invoice_width ?> x <?= $invoice_height ?> in</strong></div>
                    <div class="invoice-detail-row"><span>Pickup Schedule</span><strong><?= $invoice_pickup_label ?></strong></div>
                </div>
                <div class="invoice-detail-card">
                    <p class="invoice-label">Package Details</p>
                    <div class="invoice-detail-row"><span>Package Contents</span><strong><?= $invoice_package_contents ?></strong></div>
                    <?php if ($invoice_reference_number !== ''): ?>
                        <div class="invoice-detail-row"><span>Reference Number</span><strong><?= $invoice_reference_number ?></strong></div>
                    <?php endif; ?>
                    <?php if ($invoice_declared_value > 0): ?>
                        <div class="invoice-detail-row"><span>Declared Value</span><strong>$<?= number_format($invoice_declared_value, 2) ?></strong></div>
                    <?php endif; ?>
                    <div class="invoice-detail-row"><span>Payment Method</span><strong><?= htmlspecialchars($invoice_payment_method) ?></strong></div>
                    <?php if ($invoice_payment_method_key === 'crypto' && $invoice_crypto_asset !== ''): ?>
                        <div class="invoice-detail-row"><span>Crypto Asset</span><strong><?= $invoice_crypto_asset ?></strong></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="invoice-summary-list">
                <p class="invoice-label">Charges</p>
                <?php foreach ($invoice_rows as $invoice_row): ?>
                    <div class="invoice-row">
                        <span><?= htmlspecialchars($invoice_row['label']) ?></span>
                        <strong>$<?= number_format((float)$invoice_row['amount'], 2) ?></strong>
                    </div>
                <?php endforeach; ?>
                <?php if ($invoice_promo_discount > 0): ?>
                    <div class="invoice-row invoice-row-discount">
                        <span>Promo Discount</span>
                        <strong>-$<?= number_format((float)$invoice_promo_discount, 2) ?></strong>
                    </div>
                <?php endif; ?>
                <div class="invoice-row invoice-row-total">
                    <span>Total</span>
                    <strong>$<?= number_format($invoice_total, 2) ?></strong>
                </div>
            </div>
            <div class="invoice-mini">
                <div class="invoice-mini-item">
                    <span class="invoice-label">Service</span>
                    <p><?= $invoice_service_label ?></p>
                </div>
                <div class="invoice-mini-item">
                    <span class="invoice-label">Payment Method</span>
                    <p><?= htmlspecialchars($invoice_payment_method) ?></p>
                </div>
            </div>
            <?php if ($invoice_payment_method_key === 'crypto'): ?>
                <p class="billing-note">Additional miner/validator transaction fees may still apply separately at transfer time.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="action-row">
        <button type="button" class="btn-next" onclick="window.printShipmentLabel()">Print Label</button>
        <button type="button" class="btn-back" onclick="window.printShipmentInvoice()">Print Invoice</button>
        <a href="/shipping/create/?s=1&reset=1" class="btn-back">Create New</a>
    </div>
</article>
<script>
    JsBarcode("#barcode", "<?= htmlspecialchars((string)($created_shipment['tracking_number'] ?? '1Z0000000000000000')) ?>", { format: "CODE128", width: 2, height: 60, displayValue: false });

    window.printShipmentLabel = function () {
        var label = document.getElementById('printableLabel');
        if (!label) {
            window.print();
            return;
        }

        var printWindow = window.open('', '_blank', 'width=900,height=700');
        if (!printWindow) {
            window.print();
            return;
        }

        var styles = [
            'html,body{margin:0;padding:0;background:#fff;font-family:Arial,sans-serif;color:#111;}',
            'body{padding:24px;}',
            '.shipping-label{width:420px;max-width:100%;border:2px solid #111;padding:16px 16px 14px;box-sizing:border-box;}',
            '.shipping-label .label-header{display:flex;justify-content:space-between;align-items:center;font-weight:700;font-size:16px;letter-spacing:.03em;margin-bottom:10px;}',
            '.shipping-label .ups-text{font-size:18px;font-weight:800;}',
            '.shipping-label .thick-line{border:none;border-top:4px solid #111;margin:12px 0;}',
            '.shipping-label .address-section p{margin:0 0 8px;font-size:14px;line-height:1.35;}',
            '.shipping-label .tracking-section{text-align:center;}',
            '.shipping-label .tracking-section p{margin:14px 0 0;font-size:14px;letter-spacing:.04em;}',
            '.shipping-label svg{display:block;width:100%;height:auto;}'
        ].join('');

        printWindow.document.open();
        printWindow.document.write(
            '<!doctype html>' +
            '<html><head><title>Print Shipment Label</title><meta charset="utf-8">' +
            '<style>' + styles + '</style>' +
            '</head><body>' +
            label.outerHTML +
            '<script>window.onload=function(){setTimeout(function(){window.print();window.close();},150);};<\/script>' +
            '</body></html>'
        );
        printWindow.document.close();
    };

    function buildInvoiceMarkup() {
        var invoice = document.getElementById('printableInvoice');
        return invoice ? invoice.outerHTML : '';
    }

    function invoicePrintDocument() {
        return [
            '<!doctype html><html><head><title>Shipment Invoice</title><meta charset="utf-8"><style>',
            'html,body{margin:0;padding:0;background:#fff;font-family:Arial,sans-serif;color:#111;}',
            'body{padding:28px;}',
            '.invoice-card{width:760px;max-width:100%;border:1px solid #d8d4cf;padding:24px;box-sizing:border-box;background:#fff;}',
            '.invoice-head{display:flex;justify-content:space-between;gap:20px;align-items:flex-start;border-bottom:2px solid #111;padding-bottom:14px;margin-bottom:18px;}',
            '.invoice-kicker{margin:0 0 4px;font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:#6a6660;}',
            '.invoice-head h3{margin:0;font-size:28px;line-height:1.1;}',
            '.invoice-meta p,.invoice-party-grid p,.invoice-mini p{margin:0 0 8px;font-size:14px;line-height:1.4;}',
            '.invoice-party-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:18px;}',
            '.invoice-label{font-weight:700;text-transform:uppercase;font-size:12px;letter-spacing:.08em;color:#6a6660;}',
            '.invoice-summary-list{border-top:1px solid #d8d4cf;border-bottom:1px solid #d8d4cf;padding:12px 0;margin-bottom:16px;}',
            '.invoice-row{display:flex;justify-content:space-between;gap:16px;padding:8px 0;font-size:14px;}',
            '.invoice-row-total{border-top:1px solid #d8d4cf;margin-top:8px;padding-top:12px;font-size:16px;font-weight:700;}',
            '.invoice-row-discount strong{color:#0b8a87;}',
            '</style></head><body>',
            buildInvoiceMarkup(),
            '<script>window.onload=function(){setTimeout(function(){window.print();window.close();},150);};<\/script>',
            '</body></html>'
        ].join('');
    }

    window.printShipmentInvoice = function () {
        var markup = buildInvoiceMarkup();
        if (!markup) return;
        var printWindow = window.open('', '_blank', 'width=960,height=760');
        if (!printWindow) return;
        printWindow.document.open();
        printWindow.document.write(invoicePrintDocument());
        printWindow.document.close();
    };

    window.downloadShipmentInvoice = function () {
        var markup = buildInvoiceMarkup();
        if (!markup) return;
        var html = [
            '<!doctype html><html><head><meta charset="utf-8"><title>Shipment Invoice</title></head><body>',
            markup,
            '</body></html>'
        ].join('');
        var blob = new Blob([html], { type: 'text/html;charset=utf-8' });
        var url = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = url;
        link.download = 'shipment-invoice-<?= htmlspecialchars((string)($created_shipment['tracking_number'] ?? 'shipment')) ?>.html';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
    };
</script>
