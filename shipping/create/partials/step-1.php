                            <article class="ship-card js-address-group">
                                <h3>Ship From</h3>
                                <div class="input-stack">
                                    <label>Country or Territory</label>
                                    <select name="ship_from_country">
                                        <option value="United States" <?= (($shipment_form['ship_from_country'] ?? '') === 'United States') ? 'selected' : '' ?>>United States</option>
                                    </select>
                                    <input type="hidden" name="ship_from_country_code" id="ship-from-country-code" value="<?= htmlspecialchars($ship_from_country_code) ?>">
                                </div>
                                <div class="input-row two">
                                    <div class="field-wrap js-validate-required" data-field-label="Full Name or Company Name">
                                        <input type="text" name="sender_name" value="<?= htmlspecialchars((string)($shipment_form['sender_name'] ?? '')) ?>" placeholder="Full Name or Company Name*" required>
                                        <p class="field-error" aria-live="polite"></p>
                                    </div>
                                    <input type="text" name="sender_contact" value="<?= htmlspecialchars((string)($shipment_form['sender_contact'] ?? '')) ?>" placeholder="Contact Name">
                                </div>
                                <div class="input-row two">
                                    <div class="field-wrap js-validate-required" data-field-label="Email" data-field-type="email">
                                        <input type="email" name="sender_email" value="<?= htmlspecialchars((string)($shipment_form['sender_email'] ?? '')) ?>" placeholder="Email*" required>
                                        <p class="field-error" aria-live="polite"></p>
                                    </div>
                                    <input type="tel" name="sender_phone" value="<?= htmlspecialchars((string)($shipment_form['sender_phone'] ?? '')) ?>" placeholder="Phone">
                                </div>
                                <label class="check-row notify-check">
                                    <input type="checkbox" name="sender_notify" value="1" <?= !empty($shipment_form['sender_notify']) ? 'checked' : '' ?>>
                                    <span>Send me an email whenever my package status changes</span>
                                </label>
                                <div class="field-wrap js-validate-required" data-field-label="Street Address">
                                    <input type="text" class="js-street-trigger" name="origin_address" value="<?= htmlspecialchars((string)($shipment_form['origin_address'] ?? '')) ?>" placeholder="Street Address*" required>
                                    <p class="field-error" aria-live="polite"></p>
                                </div>
                                <div class="address-extra js-address-extra <?= $show_sender_address_extra ? 'is-visible' : '' ?>">
                                    <a href="#" class="address-extra-link"><span class="material-symbols-outlined">add_circle</span> Add apartment, suite, unit, building, floor, etc.</a>
                                    <div class="input-row three">
                                        <input type="text" name="sender_city" value="<?= htmlspecialchars((string)($shipment_form['sender_city'] ?? '')) ?>" placeholder="City*">
                                        <input type="text" name="sender_state" value="<?= htmlspecialchars((string)($shipment_form['sender_state'] ?? '')) ?>" placeholder="State*">
                                        <input type="text" name="sender_zip" value="<?= htmlspecialchars((string)($shipment_form['sender_zip'] ?? '')) ?>" placeholder="ZIP Code*" inputmode="numeric">
                                    </div>
                                    <label class="check-row notify-check">
                                        <input type="checkbox" name="sender_save_address" value="1" <?= !empty($shipment_form['sender_save_address']) ? 'checked' : '' ?>>
                                        <span>Save this address.</span>
                                    </label>
                                </div>
                            </article>

                            <article class="ship-card js-address-group">
                                <h3>Ship To</h3>
                                <div class="input-stack">
                                    <label>Country or Territory</label>
                                    <select name="ship_to_country">
                                        <option value="United States">United States</option>
                                    </select>
                                </div>
                                <div class="input-row two">
                                    <div class="field-wrap js-validate-required" data-field-label="Full Name or Company Name">
                                        <input type="text" name="receiver_name" value="<?= htmlspecialchars((string)($shipment_form['receiver_name'] ?? '')) ?>" placeholder="Full Name or Company Name*" required>
                                        <p class="field-error" aria-live="polite"></p>
                                    </div>
                                    <input type="text" name="receiver_contact" value="<?= htmlspecialchars((string)($shipment_form['receiver_contact'] ?? '')) ?>" placeholder="Contact Name">
                                </div>
                                <div class="input-row two">
                                    <input type="email" name="receiver_email" value="<?= htmlspecialchars((string)($shipment_form['receiver_email'] ?? '')) ?>" placeholder="Email" required>
                                    <input type="tel" name="receiver_phone" value="<?= htmlspecialchars((string)($shipment_form['receiver_phone'] ?? '')) ?>" placeholder="Phone">
                                </div>
                                <label class="check-row notify-check">
                                    <input type="checkbox" name="receiver_notify" value="1" <?= !empty($shipment_form['receiver_notify']) ? 'checked' : '' ?>>
                                    <span>Send shipping notifications to this email</span>
                                </label>
                                <div class="field-wrap js-validate-required" data-field-label="Street Address">
                                    <input type="text" class="js-street-trigger" name="destination_address" value="<?= htmlspecialchars((string)($shipment_form['destination_address'] ?? '')) ?>" placeholder="Street Address*" required>
                                    <p class="field-error" aria-live="polite"></p>
                                </div>
                                <div class="address-extra js-address-extra <?= $show_receiver_address_extra ? 'is-visible' : '' ?>">
                                    <a href="#" class="address-extra-link"><span class="material-symbols-outlined">add_circle</span> Add apartment, suite, unit, building, floor, etc.</a>
                                    <div class="input-row three">
                                        <input type="text" name="receiver_city" value="<?= htmlspecialchars((string)($shipment_form['receiver_city'] ?? '')) ?>" placeholder="City*">
                                        <input type="text" name="receiver_state" value="<?= htmlspecialchars((string)($shipment_form['receiver_state'] ?? '')) ?>" placeholder="State*">
                                        <input type="text" name="receiver_zip" value="<?= htmlspecialchars((string)($shipment_form['receiver_zip'] ?? '')) ?>" placeholder="ZIP Code*" inputmode="numeric">
                                    </div>
                                    <label class="check-row notify-check">
                                        <input type="checkbox" name="receiver_save_address" value="1" <?= !empty($shipment_form['receiver_save_address']) ? 'checked' : '' ?>>
                                        <span>Save this address.</span>
                                    </label>
                                </div>
                                <label class="check-row">
                                    <input type="checkbox" name="is_residential" <?= !empty($shipment_form['is_residential']) ? 'checked' : '' ?>>
                                    <span>This is a residential address</span>
                                </label>
                            </article>

                            <article class="ship-card service-level-card" id="service-level-card">
                                <h3>Packaging</h3>
                                <h4 class="subhead service-level-title">Shipment Type</h4>
                                <div class="shipment-class-radios">
                                    <label class="shipment-class-item">
                                        <input type="radio" name="shipment_class" value="parcel" <?= ($selected_shipment_class === 'parcel') ? 'checked' : '' ?>>
                                        <span><strong>Parcel</strong><em>0 - 70 kg</em></span>
                                    </label>
                                    <label class="shipment-class-item">
                                        <input type="radio" name="shipment_class" value="heavy_parcel" <?= ($selected_shipment_class === 'heavy_parcel') ? 'checked' : '' ?>>
                                        <span><strong>Heavy Parcel</strong><em>70 - 150 kg</em></span>
                                    </label>
                                    <label class="shipment-class-item">
                                        <input type="radio" name="shipment_class" value="freight_pallet" <?= ($selected_shipment_class === 'freight_pallet') ? 'checked' : '' ?>>
                                        <span><strong>Freight / Pallet</strong><em>150 kg - several tons</em></span>
                                    </label>
                                </div>
                                <?php $packaging_value = (string)($shipment_form['packaging_type'] ?? 'standard'); ?>
                                <div class="segmented js-packaging-toggle">
                                    <input type="hidden" name="packaging_type" class="js-packaging-input" value="<?= htmlspecialchars($packaging_value) ?>">
                                    <button type="button" class="seg-btn <?= ($packaging_value === 'standard') ? 'active' : '' ?>" data-packaging-value="standard">Standard</button>
                                    <button type="button" class="seg-btn <?= ($packaging_value === 'ups_packaging') ? 'active' : '' ?>" data-packaging-value="ups_packaging">UPS Packaging</button>
                                </div>
                                <p class="pack-note">For most accurate pricing and service selection variety provide all 4 measurements.</p>
                                <label class="check-row">
                                    <input type="checkbox" name="unpackaged" value="1">
                                    <span>My shipment is unpackaged or crated.</span>
                                </label>
                                <p class="tips-line">Measurement Tips <span class="material-symbols-outlined">info</span></p>
                                <div class="box-art-wrap">
                                    <svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 280 280">
                                      <defs>
                                        <style>
                                          .cls-1 { fill: #333; } .cls-1, .cls-2, .cls-3, .cls-4, .cls-5, .cls-6, .cls-7 { stroke-width: 0px; }
                                          .cls-2 { fill: #c7b29e; } .cls-3 { fill: #8c5d25; } .cls-4 { fill: url(#linear-gradient); }
                                          .cls-5 { fill: #dda053; } .cls-8 { stroke: silver; stroke-dasharray: 0 0 4 4; }
                                          .cls-8, .cls-9 { fill: none; } .cls-9 { stroke: #b9b9b9; stroke-linecap: round; }
                                          .cls-6 { fill: #fff; } .cls-7 { fill: #aa782e; }
                                        </style>
                                        <linearGradient id="linear-gradient" x1="141.6" y1="160.8" x2="349.4" y2="14.9" gradientTransform="translate(0 282) scale(1 -1)" gradientUnits="userSpaceOnUse">
                                          <stop offset="0" stop-color="#351c14" stop-opacity="0"/>
                                          <stop offset="1" stop-color="#351c14"/>
                                        </linearGradient>
                                      </defs>
                                      <path class="cls-7" d="M117.7,112.8L27.3,76.4l-.6,82.8,90.6,36.4.6-82.8h-.2Z"/><path class="cls-3" d="M117.7,112.8l90.6-36.4-.6,82.8-90.6,36.4.6-82.8Z"/><path class="cls-4" d="M117.7,112.8l90.6-36.4-.6,82.8-90.6,36.4.6-82.8Z"/><path class="cls-5" d="M27.3,76.4l90.6,36.4,90.6-36.4-90.8-36.4L27.3,76.4Z"/><path class="cls-2" d="M80.6,97.8l-17.2-6.9v16.9l17.2,6.9v-16.9Z"/><path class="cls-6" d="M154.1,54.4l-90.6,36.4,17.2,6.9,90.4-36.4-17-6.9Z"/><path class="cls-9" d="M213.9,201.3l-3.2-1.3"/><path class="cls-9" d="M213.9,201.3l-1.7,3"/><path class="cls-9" d="M143.4,228.1l3.5.4"/><path class="cls-9" d="M143.4,228.1l.8-3.4"/><line class="cls-8" x1="151.2" y1="224.7" x2="207.3" y2="203.1"/><path class="cls-1" d="M185.2,236.5l2.3-8.1h1.1l-.6,3.1-2.5,8.4h-1.1l.8-3.5ZM182.8,228.4l1.8,7.9.5,3.7h-1.1l-2.8-11.6h1.5ZM191.5,236.3l1.8-7.9h1.5l-2.8,11.6h-1.1l.6-3.7ZM188.7,228.4l2.2,8.1.8,3.5h-1.1l-2.4-8.4-.6-3.1h1.1Z"/><path class="cls-9" d="M238,69.8l-2.1,2.8"/><path class="cls-9" d="M238,69.8l2.5,2.5"/><path class="cls-9" d="M238.2,161l2.1-2.8"/><path class="cls-9" d="M238.2,161.1l-2.5-2.5"/><line class="cls-8" x1="238.1" y1="75.7" x2="238.1" y2="153.1"/><path class="cls-1" d="M273.7,126.1v1.2h-6.2v-1.2h6.2ZM267.7,121.1v11.6h-1.5v-11.6h1.5ZM275,121.1v11.6h-1.5v-11.6h1.5Z"/><path class="cls-8" d="M11.2,197.1l65.2,26.3"/><path class="cls-9" d="M5,193.1l1.4,3.2"/><path class="cls-9" d="M5,193.1l3.3-1"/><path class="cls-9" d="M82.7,226.5l-1.5-3.1"/><path class="cls-9" d="M82.7,226.6l-3.3,1.1"/><path class="cls-1" d="M38,232.4v1.2h-5.7v-1.2h5.7ZM32.6,222.1v11.6h-1.5v-11.6h1.5Z"/>
                                    </svg>
                                </div>
                                <div class="input-row four">
                                    <div class="unit-input"><input type="number" step="0.01" min="0.01" name="weight" value="<?= htmlspecialchars((string)($shipment_form['weight'] ?? '')) ?>" placeholder="Weight*"><span>lbs</span></div>
                                    <div class="unit-input"><input type="number" step="0.01" min="0.01" name="length" value="<?= htmlspecialchars((string)($shipment_form['length'] ?? '')) ?>" placeholder="Length*"><span>in</span></div>
                                    <div class="unit-input"><input type="number" step="0.01" min="0.01" name="width" value="<?= htmlspecialchars((string)($shipment_form['width'] ?? '')) ?>" placeholder="Width*"><span>in</span></div>
                                    <div class="unit-input"><input type="number" step="0.01" min="0.01" name="height" value="<?= htmlspecialchars((string)($shipment_form['height'] ?? '')) ?>" placeholder="Height*"><span>in</span></div>
                                </div>
                            </article>

