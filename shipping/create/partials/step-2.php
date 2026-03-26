                            <article class="ship-card pickup-card">
                                <h3>Postman Pickup Options</h3>
                                <label class="service-option pickup-opt <?= ($selected_pickup === 'dropoff') ? 'active' : '' ?>">
                                    <input type="radio" name="pickup_option" value="dropoff" <?= ($selected_pickup === 'dropoff') ? 'checked' : '' ?>>
                                    <div class="service-info">
                                        <strong>Send postman on next available route.</strong>
                                        <span>We assign pickup time automatically based on your area and route availability.</span>
                                    </div>
                                    <div class="service-price">Free</div>
                                </label>
                                <label class="service-option pickup-opt <?= ($selected_pickup === 'pickup') ? 'active' : '' ?>">
                                    <input type="radio" name="pickup_option" value="pickup" <?= ($selected_pickup === 'pickup') ? 'checked' : '' ?>>
                                    <div class="service-info">
                                        <strong>Choose my own pickup time.</strong>
                                        <span>A dedicated pickup postman is assigned to your selected time window.</span>
                                    </div>
                                    <div class="service-price">$15.75</div>
                                </label>
                            </article>

                            <article class="ship-card">
                                <p class="dropoff-copy" id="pickup-mode-copy"><?= htmlspecialchars($pickup_mode_copy_initial) ?></p>
                                <div class="date-box <?= ($selected_pickup === 'pickup') ? 'is-selectable' : '' ?>" id="pickup-date-box">
                                    <small id="pickup-date-title"><?= htmlspecialchars($pickup_date_title_initial) ?></small>
                                    <strong id="pickup-date-label"><?= date('M j, Y', strtotime($display_pickup_ymd)) ?></strong>
                                    <span class="material-symbols-outlined">calendar_month</span>
                                    <input type="date" name="pickup_date" id="pickup-date-input" value="<?= htmlspecialchars($display_pickup_ymd) ?>" min="<?= htmlspecialchars($today_ymd) ?>" <?= ($selected_pickup === 'pickup') ? '' : 'disabled' ?>>
                                </div>
                                <p id="pickup-help-copy"><?= $pickup_help_copy_initial ?></p>
                                <a href="#" class="inline-link" id="pickup-help-link"><?= ($selected_pickup === 'pickup') ? 'Set Pickup Instructions' : 'View Pickup Instructions' ?></a>
                                <div class="pickup-instructions-panel <?= ($pickup_instructions_value !== '') ? 'is-open' : '' ?>" id="pickup-instructions-panel">
                                    <label for="pickup-instructions-input" class="pickup-instructions-label">Pickup instructions</label>
                                    <textarea
                                        id="pickup-instructions-input"
                                        name="pickup_instructions"
                                        maxlength="<?= (int)$pickup_instruction_limit ?>"
                                        placeholder="Example: Ring bell at gate 2, ask for front desk, pickup box is labeled and ready."
                                    ><?= htmlspecialchars($pickup_instructions_value) ?></textarea>
                                    <p class="pickup-instructions-meta">
                                        Keep it brief and specific for faster pickup coordination.
                                        <span id="pickup-instructions-count">0/<?= (int)$pickup_instruction_limit ?></span>
                                    </p>
                                </div>
                            </article>

                            <article class="ship-card">
                                <aside id="service-debug-panel" style="position:fixed;left:10px;top:120px;width:320px;max-height:65vh;overflow:auto;background:#0f172a;color:#e2e8f0;border:1px solid #334155;border-radius:10px;padding:10px 12px;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,.35);font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;">
                                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                                        <strong style="font-size:12px;letter-spacing:.3px;">SERVICE DEBUG</strong>
                                        <button type="button" id="service-debug-clear" style="font-size:11px;padding:2px 6px;border:1px solid #64748b;background:#1e293b;color:#e2e8f0;border-radius:4px;cursor:pointer;">Clear</button>
                                    </div>
                                    <pre id="service-debug-log" style="white-space:pre-wrap;word-break:break-word;font-size:11px;line-height:1.4;margin:0;color:#93c5fd;">waiting for service actions...</pre>
                                </aside>
                                <h3>When do you want it to get there?</h3>
                                <h4 class="subhead service-level-title">Service Level</h4>
                                <p>
                                    <a href="#" id="service-quote-hint" class="service-quote-hint">
                                        Select the service tier that best matches your shipment urgency and handling priority.
                                    </a>
                                </p>
                                <label class="service-option service-level-item" data-service-level="priority">
                                    <input type="radio" name="service_type" value="priority">
                                    <div class="service-info">
                                        <strong>Priority <span class="service-chip service-chip-fast">HIGHEST TIER</span></strong>
                                        <span>Fastest operational treatment with first-priority movement across hubs and clearance points.</span>
                                        <span>Best when timing is critical and business, legal, or financial consequences are significant.</span>
                                    </div>
                                    <div class="service-runtime-meta" id="service-meta-priority"></div>
                                    <div class="service-cta">
                                        <button type="button" class="quote-btn quote-btn-secondary service-generate-btn" data-service-level="priority" onclick="return window.startServiceQuote ? window.startServiceQuote(this) : false;">Get Quote</button>
                                    </div>
                                </label>
                                <label class="service-option service-level-item" data-service-level="express">
                                    <input type="radio" name="service_type" value="express">
                                    <div class="service-info">
                                        <strong>Express <span class="service-chip service-chip-rec">MID TIER</span></strong>
                                        <span>Accelerated service with strong transit speed and predictable handling at lower cost than Priority.</span>
                                        <span>Best balance when timing matters but top-tier urgency is not required.</span>
                                    </div>
                                    <div class="service-runtime-meta" id="service-meta-express"></div>
                                    <div class="service-cta">
                                        <button type="button" class="quote-btn quote-btn-secondary service-generate-btn" data-service-level="express" onclick="return window.startServiceQuote ? window.startServiceQuote(this) : false;">Get Quote</button>
                                    </div>
                                </label>
                                <label class="service-option service-level-item" data-service-level="economy">
                                    <input type="radio" name="service_type" value="economy">
                                    <div class="service-info">
                                        <strong>Economy <span class="service-chip service-chip-low">BEST VALUE</span></strong>
                                        <span>Most cost-efficient service with flexible routing and consolidated movement.</span>
                                        <span>Best when minimizing shipping spend is the primary goal over speed.</span>
                                    </div>
                                    <div class="service-runtime-meta" id="service-meta-economy"></div>
                                    <div class="service-cta">
                                        <button type="button" class="quote-btn quote-btn-secondary service-generate-btn" data-service-level="economy" onclick="return window.startServiceQuote ? window.startServiceQuote(this) : false;">Get Quote</button>
                                    </div>
                                </label>
                                <input type="hidden" name="quote_request_id" id="quote-request-id" value="<?= (int)($shipment_form['quote_request_id'] ?? 0) ?>">
                                <input type="hidden" name="quote_service_level" id="quote-service-level" value="<?= htmlspecialchars((string)($shipment_form['quote_service_level'] ?? '')) ?>">
                                <div class="service-process-actions">
                                    <p id="service-process-status">Only a selected service with a generated quote can continue to the next step.</p>
                                </div>
                                <div class="service-processing-overlay" id="service-processing-overlay" aria-hidden="true">
                                    <dotlottie-wc
                                        class="service-processing-lottie"
                                        src="https://lottie.host/ac0927e0-074a-4a7e-a1c1-28b874073eb6/ASb2VUE7W2.lottie"
                                        autoplay
                                        loop
                                    ></dotlottie-wc>
                    <h4>Generating Secure Quote</h4>
                    <p>Encrypting shipment data and requesting price, duration, and service notes...</p>
                </div>
                            </article>
