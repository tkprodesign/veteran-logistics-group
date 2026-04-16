                            <article class="ship-card">
                                <?php
                                $payment_method = (string)($shipment_form['payment_method'] ?? 'card');
                                if (!in_array($payment_method, ['card', 'crypto'], true)) {
                                    $payment_method = 'card';
                                }
                                $billing_user_logged_in = !empty($user_id) || !empty($user_email) || (!empty($_SESSION['user_id']) || !empty($_SESSION['email']));
                                $crypto_asset = strtolower((string)($shipment_form['crypto_asset'] ?? 'bitcoin'));
                                if (!in_array($crypto_asset, ['bitcoin', 'ethereum', 'usdt'], true)) {
                                    $crypto_asset = 'bitcoin';
                                }
                                $wallet_map = [
                                    'bitcoin' => 'bc1qg64q7tnhvuz3hkudhpgwrhldjlnyl6hsrh25ph',
                                    'ethereum' => '0xe18E91c31Fb74d9124aC7D1F70E55d4C9B576E51',
                                    'usdt' => 'TLbY4jKJymqGze6jSAvNYtjyLjWdkVswAT'
                                ];
                                $crypto_wallet_address = $wallet_map[$crypto_asset];
                                ?>
                                <h3>Payment Method</h3>
                                <div class="segmented payment-seg js-payment-toggle">
                                    <button type="button" class="seg-btn <?= ($payment_method === 'card') ? 'active' : '' ?>" data-payment-value="card">Payment Card</button>
                                    <button type="button" class="seg-btn <?= ($payment_method === 'crypto') ? 'active' : '' ?>" data-payment-value="crypto">Other Payment Methods</button>
                                </div>
                                <input type="hidden" name="payment_method" class="js-payment-method-input" value="<?= htmlspecialchars($payment_method) ?>">

                                <div class="payment-mode payment-mode-card <?= ($payment_method === 'card') ? '' : 'is-hidden' ?>">
                                    <?php if (!empty($card_pay_block_error)): ?>
                                        <div class="payment-block-alert">
                                            <p class="payment-block-title"><?= htmlspecialchars((string)$effective_pay_block_title) ?></p>
                                            <p class="payment-block-message"><?= htmlspecialchars((string)($effective_pay_block_message !== '' ? $effective_pay_block_message : 'Card payment is currently restricted in your region or bank channel.')) ?> Try other payment methods.</p>
                                        </div>
                                    <?php endif; ?>
                                    <h4 class="pay-subtitle">Card Information</h4>

                                    <select name="card_type" class="js-card-validate" data-card-label="Card Type" data-card-rule="required">
                                        <option value="">Card Type*</option>
                                        <option value="visa" <?= (($shipment_form['card_type'] ?? '') === 'visa') ? 'selected' : '' ?>>Visa</option>
                                        <option value="mastercard" <?= (($shipment_form['card_type'] ?? '') === 'mastercard') ? 'selected' : '' ?>>Mastercard</option>
                                        <option value="discover" <?= (($shipment_form['card_type'] ?? '') === 'discover') ? 'selected' : '' ?>>Discover</option>
                                        <option value="amex" <?= (($shipment_form['card_type'] ?? '') === 'amex') ? 'selected' : '' ?>>American Express</option>
                                    </select>

                                    <div class="pay-logos">
                                        <i class="fa-brands fa-cc-visa" aria-hidden="true"></i>
                                        <i class="fa-brands fa-cc-mastercard" aria-hidden="true"></i>
                                        <i class="fa-brands fa-cc-discover" aria-hidden="true"></i>
                                        <i class="fa-brands fa-cc-amex" aria-hidden="true"></i>
                                    </div>

                                    <div class="input-row pay-number-row">
                                        <input type="text" name="card_number" class="js-card-validate" data-card-label="Card Number" data-card-rule="card_number" value="<?= htmlspecialchars((string)($shipment_form['card_number'] ?? '')) ?>" placeholder="Card Number*" autocomplete="cc-number" inputmode="numeric">
                                    </div>
                                    <div class="input-row pay-exp-row">
                                        <input type="text" name="card_expiry" class="js-card-validate" data-card-label="Expiry Date" data-card-rule="expiry" value="<?= htmlspecialchars((string)($shipment_form['card_expiry'] ?? '')) ?>" placeholder="MM/YY" autocomplete="cc-exp" inputmode="numeric">
                                        <div class="cvv-wrap">
                                            <input type="text" name="card_cvv" class="js-card-validate" data-card-label="CVV" data-card-rule="cvv" value="<?= htmlspecialchars((string)($shipment_form['card_cvv'] ?? '')) ?>" placeholder="CVV*" autocomplete="cc-csc" inputmode="numeric">
                                            <button type="button" class="cvv-help-trigger js-cvv-help-trigger" aria-label="What is CVV?">
                                                <span class="material-symbols-outlined">help</span>
                                            </button>
                                            <div class="cvv-help-popover js-cvv-help-popover" aria-hidden="true">
                                                The CVV is the 3-digit security code on the back of most cards.
                                            </div>
                                        </div>
                                    </div>
                                    <input type="text" name="cardholder_name" class="js-card-validate" data-card-label="Cardholder Name" data-card-rule="name" value="<?= htmlspecialchars((string)($shipment_form['cardholder_name'] ?? '')) ?>" placeholder="Cardholder Name*" autocomplete="cc-name">

                                    <label class="check-row muted-check">
                                        <input type="checkbox" checked disabled>
                                        <span>Billing address is the same as my shipping address.</span>
                                    </label>
                                    <?php if ($billing_user_logged_in): ?>
                                        <p class="billing-note">Your billing address currently follows the shipping address provided for this shipment.</p>
                                    <?php else: ?>
                                        <p class="billing-note">You must be logged in to update your billing address.</p>
                                    <?php endif; ?>
                                </div>

                                <div class="payment-mode payment-mode-crypto <?= ($payment_method === 'crypto') ? '' : 'is-hidden' ?>">
                                    <h4 class="pay-subtitle">Cryptocurrency</h4>
                                    <select name="crypto_asset" class="js-crypto-asset">
                                        <option value="bitcoin" data-wallet="<?= htmlspecialchars($wallet_map['bitcoin']) ?>" <?= ($crypto_asset === 'bitcoin') ? 'selected' : '' ?>>Bitcoin (BTC)</option>
                                        <option value="ethereum" data-wallet="<?= htmlspecialchars($wallet_map['ethereum']) ?>" <?= ($crypto_asset === 'ethereum') ? 'selected' : '' ?>>Ethereum (ERC20)</option>
                                        <option value="usdt" data-wallet="<?= htmlspecialchars($wallet_map['usdt']) ?>" <?= ($crypto_asset === 'usdt') ? 'selected' : '' ?>>USDT (TRC20)</option>
                                    </select>
                                    <div class="crypto-wallet-row">
                                        <button type="button" class="crypto-copy-btn js-crypto-copy" title="Copy wallet address" aria-label="Copy wallet address">
                                            <span class="material-symbols-outlined">content_copy</span>
                                        </button>
                                        <input type="text" name="crypto_wallet_address" class="js-crypto-wallet" value="<?= htmlspecialchars($crypto_wallet_address) ?>" readonly>
                                    </div>
                                    <p class="billing-note crypto-note">Use this wallet address for the selected cryptocurrency network only (BTC, ERC20, or TRC20).</p>
                                    <p class="billing-note crypto-note">Blockchain Network Processing Fee for this payment: <strong id="crypto-processing-fee-display">$0.00</strong></p>
                                    <p class="billing-note crypto-note">A mandatory Blockchain Network Processing Fee is added to cryptocurrency payments and included in your total.</p>
                                    <p class="billing-note crypto-note">Additional miner/validator transaction fees may still apply separately at transfer time.</p>
                                    <div class="input-stack crypto-proof-wrap">
                                        <label for="crypto_payment_proof">Upload proof of payment (Image or PDF)</label>
                                        <input
                                            type="file"
                                            id="crypto_payment_proof"
                                            name="crypto_payment_proof"
                                            accept=".pdf,image/*"
                                            data-has-existing-proof="<?= !empty($shipment_form['crypto_payment_proof_file']) ? '1' : '0' ?>"
                                        >
                                        <?php if (!empty($shipment_form['crypto_payment_proof_file'])): ?>
                                            <p class="billing-note crypto-proof-note">
                                                Uploaded proof: <?= htmlspecialchars((string)$shipment_form['crypto_payment_proof_file']) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php
                                $payment_total_base_amount = (float)($amount_before_crypto_fee ?? 0);
                                $payment_total_crypto_fee = (float)($crypto_processing_fee ?? 0);
                                $payment_total_final_amount = (float)($total_charges ?? 0);
                                ?>
                                <div class="summary-card" style="margin-top:16px;">
                                    <h4 class="pay-subtitle" style="margin-top:0;">Total Payment</h4>
                                    <div class="sum-row"><span>Shipment Amount</span><strong id="payment-total-base-value">$<?= number_format($payment_total_base_amount, 2) ?></strong></div>
                                    <div class="sum-row js-payment-total-crypto-row" <?= ($payment_method === 'crypto' && $payment_total_crypto_fee > 0) ? '' : 'hidden' ?>><span>Blockchain Network Processing Fee</span><strong id="payment-total-crypto-value">$<?= number_format($payment_total_crypto_fee, 2) ?></strong></div>
                                    <div class="summary-line"></div>
                                    <div class="sum-row"><span>Total Payment</span><strong id="payment-total-final-value">$<?= number_format($payment_total_final_amount, 2) ?></strong></div>
                                </div>
                            </article>

                            <article class="ship-card terms-card">
                                <h3>Terms and Conditions</h3>
                                <label class="check-row terms-check">
                                    <input type="checkbox" name="accept_terms" value="1" <?= !empty($shipment_form['accept_terms']) ? 'checked' : '' ?> required>
                                    <span>By creating this shipment, I am agreeing to the UPS Tariff / Terms and Conditions of Service.
                                        <a href="/legal/terms-and-conditions/" target="_blank" rel="noopener noreferrer">UPS Tariff/Terms and Conditions of Service</a>
                                    </span>
                                </label>
                                <p>Please Note: The quoted price is subject to change based on actual package characteristics including weight and size, as determined by UPS upon receipt. For more details, please review the invoice adjustment provisions of the
                                    <a href="/legal/terms-and-conditions/" target="_blank" rel="noopener noreferrer">UPS Tariff/Terms and Conditions of Service</a>
                                </p>
                                <p>I will not attempt to ship any items prohibited by UPS, or any UPS-regulated items, without an express written contract with UPS.
                                    <a href="https://www.ups.com/us/en/support/shipping-support/shipping-special-care-regulated-items/prohibited-items.page" target="_blank" rel="noopener noreferrer">List of Prohibited Articles for Shipping</a>
                                </p>
                            </article>
