<?php
include('app.php');

$create_page_heading = 'Create a Shipment';

$step_labels = [
    1 => "Shipping Details",
    2 => "Service Selection",
    3 => "Additional Details",
    4 => "Payment",
    5 => "Confirmation"
];

$next_button_text = [
    1 => "Select a Service",
    2 => "Additional Details",
    3 => "Payment",
    4 => "Pay and Get Label"
];

$selected_service = (string)($shipment_form['service_type'] ?? 'standard');
$selected_service_ui = ($selected_service === 'overnight') ? 'express' : (($selected_service === 'express') ? 'priority' : 'economy');
$selected_pickup = (string)($shipment_form['pickup_option'] ?? 'dropoff');
$selected_shipment_class = (string)($shipment_form['shipment_class'] ?? 'parcel');
$shipment_class_labels = [
    'parcel' => 'Parcel',
    'heavy_parcel' => 'Heavy Parcel',
    'freight_pallet' => 'Freight / Pallet'
];
$selected_shipment_class_label = $shipment_class_labels[$selected_shipment_class] ?? 'Parcel';
$base_prices = [
    'standard' => 17.48,
    'express' => 49.50,
    'overnight' => 108.77
];
$pickup_fee = ($selected_pickup === 'pickup') ? 15.75 : 0.00;
$option_carbon_fee = !empty($shipment_form['opt_carbon']) ? 0.05 : 0.00;
$option_signature_fee = !empty($shipment_form['opt_signature']) ? 7.70 : 0.00;
$option_adult_fee = !empty($shipment_form['opt_adult_signature']) ? 9.35 : 0.00;
$shipment_options_total = $option_signature_fee + $option_adult_fee;
$options_total = $option_carbon_fee + $shipment_options_total;
$selected_service_for_calc = ($selected_service_ui === 'express') ? 'overnight' : (($selected_service_ui === 'priority') ? 'express' : 'standard');
$service_total = $base_prices[$selected_service_for_calc] ?? 17.48;
$service_quote_ready = false;

// If a secure quote was generated on step 2, use its price in summary calculations.
$quote_request_id_current = (int)($shipment_form['quote_request_id'] ?? 0);
$quote_service_level_current = strtolower(trim((string)($shipment_form['quote_service_level'] ?? '')));
$selected_service_level_ui = strtolower(trim((string)$selected_service_ui)); // priority|express|economy
if (
    $quote_request_id_current > 0 &&
    !empty($user_id) &&
    isset($dbconn) &&
    $dbconn instanceof mysqli
) {
    $stmtQuote = $dbconn->prepare(
        "SELECT price, service_level, processing_status
         FROM shipment_service_quotes
         WHERE id = ? AND user_id = ?
         LIMIT 1"
    );
    if ($stmtQuote) {
        $stmtQuote->bind_param("ii", $quote_request_id_current, $user_id);
        $stmtQuote->execute();
        $quoteRes = $stmtQuote->get_result();
        $quoteRow = $quoteRes ? $quoteRes->fetch_assoc() : null;
        $stmtQuote->close();

        if ($quoteRow) {
            $quotedPrice = isset($quoteRow['price']) ? (float)$quoteRow['price'] : 0.0;
            $quotedLevel = strtolower(trim((string)($quoteRow['service_level'] ?? '')));
            $quotedStatus = strtolower(trim((string)($quoteRow['processing_status'] ?? '')));
            $statusLooksReady = in_array($quotedStatus, ['ready', 'completed', 'done', 'processed'], true) || $quotedPrice > 0;
            $levelMatches = ($quotedLevel !== '' && $quotedLevel === $selected_service_level_ui && $quotedLevel === $quote_service_level_current);

            if ($statusLooksReady && $levelMatches && $quotedPrice > 0) {
                $service_total = $quotedPrice;
                $service_quote_ready = true;
            }
        }
    }
}

$subtotal_charges = $service_total + $pickup_fee + $options_total;
$promo_code_applied = trim((string)($shipment_form['promo_code'] ?? ''));
$promo_discount_amount = (float)($shipment_form['promo_discount_amount'] ?? 0);
if ($promo_discount_amount < 0) $promo_discount_amount = 0;
if ($promo_discount_amount > $subtotal_charges) $promo_discount_amount = $subtotal_charges;
$amount_before_crypto_fee = $subtotal_charges - $promo_discount_amount;
$payment_method_current = strtolower(trim((string)($shipment_form['payment_method'] ?? 'card')));
$crypto_processing_fee = ($payment_method_current === 'crypto')
    ? shipping_crypto_processing_fee((float)$amount_before_crypto_fee)
    : 0.00;
$total_charges = $amount_before_crypto_fee + $crypto_processing_fee;
$selected_service_label = ($selected_service_ui === 'economy')
    ? 'Economy'
    : (($selected_service_ui === 'priority') ? 'Priority' : 'Express');
$ship_from_country_value = (string)($shipment_form['ship_from_country'] ?? 'United States');
$ship_from_country_code = (string)($shipment_form['ship_from_country_code'] ?? 'US');
$today_ymd = shipping_country_local_date_ymd($ship_from_country_code);
$standard_pickup_ymd = shipping_default_pickup_date_ymd($selected_shipment_class, $ship_from_country_code);
$pickup_date_ymd = (string)($shipment_form['pickup_date'] ?? $standard_pickup_ymd);
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $pickup_date_ymd) !== 1) {
    $pickup_date_ymd = $standard_pickup_ymd;
}
$display_pickup_ymd = ($selected_pickup === 'pickup') ? $pickup_date_ymd : $standard_pickup_ymd;
$estimated_dropoff_label = date('F j, Y', strtotime($display_pickup_ymd));
$pickup_instructions_value = (string)($shipment_form['pickup_instructions'] ?? '');
$pickup_instruction_limit = 240;
$quote_batch_id = (int)($shipment_form['quote_batch_id'] ?? 0);
$free_pickup_mode_copy = 'Standard pickup is active. A postman on the next available route will collect this package from your address.';
$free_pickup_help_copy = '<strong>Postman Pickup:</strong> Please keep your package labeled and accessible at your pickup address.';
if ($selected_shipment_class === 'heavy_parcel') {
    $free_pickup_mode_copy = 'Standard pickup is active. Heavy Parcel shipments may require additional handling time before dispatch.';
    $free_pickup_help_copy = '<strong>Postman Pickup:</strong> Please keep handling access clear. Heavy Parcel pickup may require extra loading time.';
} elseif ($selected_shipment_class === 'freight_pallet') {
    $free_pickup_mode_copy = 'Standard pickup request is active. Freight / Pallet collection depends on route and loading equipment availability.';
    $free_pickup_help_copy = '<strong>Postman Pickup:</strong> Ensure pallet access and loading space are ready before pickup.';
}
$custom_pickup_mode_copy = 'Custom pickup is active. Select your preferred pickup window and a dedicated postman will be dispatched.';
$custom_pickup_help_copy = '<strong>Postman Pickup:</strong> Please keep your package labeled and accessible at your pickup address.';
$pickup_date_title_initial = ($selected_pickup === 'pickup') ? 'Selected Pickup Date*' : 'Earliest Pickup Date*';
$pickup_mode_copy_initial = ($selected_pickup === 'pickup') ? $custom_pickup_mode_copy : $free_pickup_mode_copy;
$pickup_help_copy_initial = ($selected_pickup === 'pickup') ? $custom_pickup_help_copy : $free_pickup_help_copy;
$show_sender_address_extra = trim((string)($shipment_form['origin_address'] ?? '')) !== '';
$show_receiver_address_extra = trim((string)($shipment_form['destination_address'] ?? '')) !== '';
$promo_message_initial = ($promo_code_applied !== '' && $promo_discount_amount > 0)
    ? 'Promo code applied.'
    : '';
$promo_message_class_initial = ($promo_message_initial !== '') ? 'success' : '';

$summary_service_label_display = $selected_service_label;
$summary_total_display = (float)$total_charges;
if ($step === 5 && !empty($created_shipment) && is_array($created_shipment)) {
    if (!empty($created_shipment['service_label'])) {
        $summary_service_label_display = (string)$created_shipment['service_label'];
    } elseif (!empty($created_shipment['shipment_type'])) {
        $stype = (string)$created_shipment['shipment_type'];
        $summary_service_label_display = ($stype === 'overnight') ? 'Express' : (($stype === 'express') ? 'Priority' : 'Economy');
    }
    if (isset($created_shipment['total_charges']) && is_numeric($created_shipment['total_charges'])) {
        $summary_total_display = (float)$created_shipment['total_charges'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($create_page_heading) ?> | Veteran Logistics Group</title>
    <link rel="stylesheet" href="/assets/stylesheets/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/stylesheets/shipping.css?v=<?php echo time(); ?>">
    <link rel="shortcut icon" href="/assets/images/branding/mark-only.png?v=<?php echo time(); ?>" type="image/png">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.9.3/dist/dotlottie-wc.js" type="module"></script>
    <script src="https://kit.fontawesome.com/79b279a6c9.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
</head>
<body>
<?php include("../../common-sections/header.html"); ?>

<main class="create-main">
    <div class="create-container">
        <div class="create-head">
            <div class="head-left">
                <h1><?= htmlspecialchars($create_page_heading) ?></h1>
                <span class="head-line"></span>
                <p>Fields marked * are required</p>
            </div>
            <div class="head-right">
                <a href="/shipping/" class="prev-exp-link">Go to Previous Experience</a>
                <?php if ($step === 2): ?>
                    <a href="/dashboard/?t=overview#a=outgoing" class="manage-ship-link"><span class="material-symbols-outlined">more_vert</span>Manage Shipments</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="create-layout">
            <aside class="steps-col">
                <ol class="steps-list">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <li class="step-item <?= ($i < $step) ? 'done' : (($i === $step) ? 'active' : '') ?>">
                            <span class="dot"></span>
                            <span class="txt"><?= htmlspecialchars($step_labels[$i]) ?></span>
                        </li>
                    <?php endfor; ?>
                </ol>
            </aside>

            <section class="form-col">
                <div class="steps-mobile">
                    <div class="mobile-bar">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="m-dot <?= ($i <= $step) ? 'active' : '' ?>"></span>
                        <?php endfor; ?>
                    </div>
                    <p class="mobile-step">Step <?= (int)$step ?> of 5</p>
                    <h2><?= htmlspecialchars($step_labels[$step]) ?></h2>
                </div>

                <?php if (!empty($shipping_errors)): ?>
                    <div class="cp-error-card">
                        <h3>Please fix the following</h3>
                        <ul>
                            <?php foreach ($shipping_errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($step < 5): ?>
                    <form method="POST" action="" enctype="multipart/form-data" class="create-form" <?= !empty($relaxed_mode) ? 'novalidate' : '' ?>>
                        <input type="hidden" name="current_step" value="<?= (int)$step ?>">

                        <?php if ($step === 2 || $step === 3 || $step === 4): ?>
                            <div class="mobile-summary-bar">
                                <a href="#shipping-summary">View Shipping Summary</a>
                                <p>
                                    Total Charges:
                                    <span id="mobile-summary-total">
                                        <?php if ($step === 2 && !$service_quote_ready): ?>
                                            Generate quote
                                        <?php else: ?>
                                            $<?= number_format($total_charges, 2) ?>
                                        <?php endif; ?>
                                    </span>
                                </p>
                            </div>
                        <?php endif; ?>

                        <?php
                        $stepPartial = __DIR__ . '/partials/step-' . (int)$step . '.php';
                        if (is_file($stepPartial)) {
                            include $stepPartial;
                        }
                        ?>

                        <div class="action-row">
                            <?php if ($step > 1): ?>
                                <a href="/shipping/create/?s=<?= $step - 1 ?>" class="btn-back">Back</a>
                            <?php endif; ?>
                            <button type="submit" class="btn-next">
                                <?= htmlspecialchars($next_button_text[$step] ?? 'Next Step') ?>
                                <span class="material-symbols-outlined">chevron_right</span>
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <?php include __DIR__ . '/partials/step-5.php'; ?>
                <?php endif; ?>
            </section>

            <aside class="summary-col" id="shipping-summary">
                <article class="summary-card">
                    <h3><?= ($step >= 2 && $step <= 4) ? 'Total Charges' : 'Shipping Summary' ?></h3>
                    <div class="summary-line"></div>
                    <?php if ($step === 2 || $step === 3 || $step === 4): ?>
                        <?php if ($step === 2 && !$service_quote_ready): ?>
                            <p id="summary-step2-note">Generate a quote for a service level to see full total charges. Pickup option charges update below.</p>
                            <div class="sum-row"><span>Total Charges</span><strong id="summary-total-charge">$<?= number_format(max(0, $pickup_fee - $promo_discount_amount), 2) ?></strong></div>
                            <div class="sum-row" id="summary-service-row" hidden><span id="summary-service-label">Service</span><strong id="summary-service-charge">$0.00</strong></div>
                        <?php else: ?>
                            <div class="sum-row"><span>Total Charges</span><strong id="summary-total-charge">$<?= number_format($total_charges, 2) ?></strong></div>
                            <div class="sum-row" id="summary-service-row"><span id="summary-service-label"><?= htmlspecialchars($selected_service_label) ?></span><strong id="summary-service-charge">$<?= number_format($service_total, 2) ?></strong></div>
                        <?php endif; ?>
                        <div class="sum-row" id="summary-pickup-row">
                            <span id="summary-pickup-label"><?= ($selected_pickup === 'pickup') ? 'Custom Pickup Fee' : 'Postman Pickup' ?></span>
                            <strong id="summary-pickup-value"><?= ($selected_pickup === 'pickup') ? '$' . number_format($pickup_fee, 2) : 'Free' ?></strong>
                        </div>
                        <?php if ($step === 3 || $step === 4): ?>
                            <div class="sum-row" id="summary-carbon-row" <?= ($option_carbon_fee > 0) ? '' : 'hidden' ?>><span>Carbon Neutral Charges</span><strong id="summary-carbon-value">$<?= number_format($option_carbon_fee, 2) ?></strong></div>
                            <div class="sum-row" id="summary-options-row" <?= ($shipment_options_total > 0) ? '' : 'hidden' ?>><span>Shipment Options</span><strong id="summary-options-value">$<?= number_format($shipment_options_total, 2) ?></strong></div>
                            <div class="sum-row" id="summary-crypto-processing-row" <?= ($step === 4 && $payment_method_current === 'crypto' && $crypto_processing_fee > 0) ? '' : 'hidden' ?>><span>Blockchain Network Processing Fee</span><strong id="summary-crypto-processing-value">$<?= number_format($crypto_processing_fee, 2) ?></strong></div>
                        <?php endif; ?>
                        <?php if ($promo_discount_amount > 0): ?>
                            <div class="sum-row promo-row" id="summary-promo-row"><span>Promo Discount (<span id="summary-promo-code"><?= htmlspecialchars($promo_code_applied) ?></span>)</span><strong id="summary-promo-value">-$<?= number_format($promo_discount_amount, 2) ?></strong></div>
                        <?php else: ?>
                            <div class="sum-row promo-row" id="summary-promo-row" hidden><span>Promo Discount (<span id="summary-promo-code"></span>)</span><strong id="summary-promo-value">-$0.00</strong></div>
                        <?php endif; ?>
                        <div class="summary-line"></div>
                        <p>Rate includes a Fuel Surcharge, but excludes taxes, duties and other charges that may apply to the shipment.</p>
                        <div class="promo-apply-wrap">
                            <input type="text" id="promo-code-input" placeholder="Promo Code" value="<?= htmlspecialchars($promo_code_applied) ?>">
                            <button type="button" class="promo-btn promo-btn-apply" id="promo-apply-btn">Apply</button>
                            <button type="button" class="promo-btn promo-btn-remove" id="promo-clear-btn" <?= ($promo_code_applied !== '' && $promo_discount_amount > 0) ? '' : 'hidden' ?>>Remove</button>
                        </div>
                        <p id="promo-feedback" class="promo-feedback <?= htmlspecialchars($promo_message_class_initial) ?>"><?= htmlspecialchars($promo_message_initial) ?></p>
                    <?php elseif ($step < 4): ?>
                        <p>Check back here after entering more information</p>
                    <?php else: ?>
                        <div class="sum-row"><span>Service:</span><strong><?= htmlspecialchars($summary_service_label_display) ?></strong></div>
                        <div class="sum-row"><span>Total:</span><strong>$<?= number_format($summary_total_display, 2) ?></strong></div>
                    <?php endif; ?>
                </article>

                <?php if ($step === 2 || $step === 3 || $step === 4): ?>
                    <article class="summary-card detail-card">
                        <h3>Sender Details</h3>
                        <p><?= htmlspecialchars((string)($shipment_form['sender_name'] ?? '')) ?></p>
                        <p><?= htmlspecialchars((string)($shipment_form['sender_email'] ?? '')) ?></p>
                        <p><?= htmlspecialchars((string)($shipment_form['sender_phone'] ?? '')) ?></p>
                        <p><?= htmlspecialchars((string)($shipment_form['origin_address'] ?? '')) ?></p>

                        <h3>Recipient Details</h3>
                        <p><?= htmlspecialchars((string)($shipment_form['receiver_name'] ?? '')) ?></p>
                        <p><?= htmlspecialchars((string)($shipment_form['receiver_email'] ?? '')) ?></p>
                        <p><?= htmlspecialchars((string)($shipment_form['receiver_phone'] ?? '')) ?></p>
                        <p><?= htmlspecialchars((string)($shipment_form['destination_address'] ?? '')) ?></p>

                        <h3>Packaging</h3>
                        <p><?= htmlspecialchars((string)($shipment_form['weight'] ?? '0')) ?> lbs</p>
                        <p><?= htmlspecialchars((string)($shipment_form['length'] ?? '0')) ?> in * <?= htmlspecialchars((string)($shipment_form['width'] ?? '0')) ?> in * <?= htmlspecialchars((string)($shipment_form['height'] ?? '0')) ?> in</p>
                        <h3>Shipment Type</h3>
                        <p><?= htmlspecialchars($selected_shipment_class_label) ?></p>

                        <h3>Pickup Schedule</h3>
                        <p><?= ($selected_pickup === 'pickup') ? 'Selected Pickup Date' : 'Earliest Pickup Date' ?>: <?= htmlspecialchars($estimated_dropoff_label) ?></p>

                        <?php if ($step >= 3): ?>
                            <h3>Additional Details</h3>
                            <p>Package Contents : <?= htmlspecialchars((string)($shipment_form['package_contents'] ?? '-')) ?></p>
                        <?php endif; ?>
                    </article>
                <?php endif; ?>
            </aside>
        </div>
    </div>
</main>

<?php include("../../common-sections/footer.html"); ?>

<script src="/assets/scripts/countries-array.js?v=<?php echo time(); ?>"></script>
<script>
window.shippingCreateConfig = {
    standardPickupDate: <?= json_encode($standard_pickup_ymd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    pickupInstructionLimit: <?= (int)$pickup_instruction_limit ?>,
    freePickupModeCopy: <?= json_encode($free_pickup_mode_copy, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    freePickupHelpCopy: <?= json_encode(strip_tags($free_pickup_help_copy), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    customPickupModeCopy: <?= json_encode($custom_pickup_mode_copy, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    customPickupHelpCopy: <?= json_encode(strip_tags($custom_pickup_help_copy), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
    currentStep: <?= (int)$step ?>,
    serviceQuoteReady: <?= $service_quote_ready ? 'true' : 'false' ?>,
    serviceTotal: <?= json_encode((float)$service_total) ?>,
    pickupFeeCustom: 15.75,
    optionsTotal: <?= json_encode((float)$shipment_options_total) ?>,
    carbonFeeAmount: <?= json_encode((float)$option_carbon_fee) ?>,
    promoDiscountAmount: <?= json_encode((float)$promo_discount_amount) ?>,
    cryptoProcessingFee: <?= json_encode((float)$crypto_processing_fee) ?>,
    paymentMethod: <?= json_encode((string)$payment_method_current) ?>,
    selectedServiceLevel: <?= json_encode((string)$selected_service_ui) ?>,
    selectedPickupOption: <?= json_encode((string)$selected_pickup) ?>
};
</script>
<script src="/assets/scripts/shipments.js?v=<?php echo time(); ?>"></script>
 

</body>
</html>
