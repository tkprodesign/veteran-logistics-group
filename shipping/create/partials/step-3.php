                            <article class="ship-card">
                                <h3>Package Details</h3>
                                <p>Specific description of shipment contents for label; max 35 characters</p>
                                <input type="text" name="package_contents" maxlength="35" value="<?= htmlspecialchars((string)($shipment_form['package_contents'] ?? '')) ?>" placeholder="Package Contents*" required>

                                <h4 class="subhead">Want to give your package a nickname or reference number?</h4>
                                <p>This will be printed on your label.</p>
                                <input type="text" name="reference_number" value="<?= htmlspecialchars((string)($shipment_form['reference_number'] ?? '')) ?>" placeholder="Phrase / Number">

                                <h4 class="subhead">Loss and Damage Protection</h4>
                                <p>We've got you covered up to $100 at no charge. Enter the value of this shipment below if you'd like to purchase additional protection.</p>
                                <div class="value-row">
                                    <input type="number" step="0.01" min="0" name="parcel_value" value="<?= htmlspecialchars((string)($shipment_form['parcel_value'] ?? '')) ?>" placeholder="Parcel Value">
                                    <span>USD</span>
                                </div>
                                <p class="tips-line">Protect Your Parcel Value <span class="material-symbols-outlined">info</span></p>
                            </article>

                            <article class="ship-card">
                                <h3>Shipment Options</h3>
                                <label class="option-card">
                                    <input type="checkbox" name="opt_carbon" class="js-shipment-option" data-option-fee="0.05" data-summary-group="carbon" <?= !empty($shipment_form['opt_carbon']) ? 'checked' : '' ?>>
                                    <div class="opt-mid">
                                        <strong>Offset My Carbon Footprint</strong>
                                        <p>Your contribution will be used to support verified carbon offsetting projects around the world.</p>
                                    </div>
                                    <span class="opt-price">$0.05</span>
                                </label>
                                <label class="option-card">
                                    <input type="checkbox" name="opt_signature" class="js-shipment-option" data-option-fee="7.70" data-summary-group="shipment_option" <?= !empty($shipment_form['opt_signature']) ? 'checked' : '' ?>>
                                    <div class="opt-mid">
                                        <strong>Require a Signature</strong>
                                        <p>We'll collect a signature before delivering your package.</p>
                                    </div>
                                    <span class="opt-price">$7.70</span>
                                </label>
                                <label class="option-card">
                                    <input type="checkbox" name="opt_adult_signature" class="js-shipment-option" data-option-fee="9.35" data-summary-group="shipment_option" <?= !empty($shipment_form['opt_adult_signature']) ? 'checked' : '' ?>>
                                    <div class="opt-mid">
                                        <strong>Require an Adult Signature</strong>
                                        <p>We'll verify that the recipient is an adult before collecting a signature and delivering your package.</p>
                                    </div>
                                    <span class="opt-price">$9.35</span>
                                </label>
                            </article>

