<?php
include('app.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Veteran Logistics Group</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css" />
    <link rel="stylesheet" href="/assets/stylesheets/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/stylesheets/dashboard.css?v=<?php echo time(); ?>">

    <link rel="stylesheet" href="/assets/stylesheets/ts/main.css?v=<?php echo time(); ?>" media="screen and (max-width: 1120px)">
    <link rel="stylesheet" href="/assets/stylesheets/ts/dashboard.css?v=<?php echo time(); ?>" media="screen and (max-width: 1120px)">
    <link rel="stylesheet" href="/assets/stylesheets/ms/main.css?v=<?php echo time(); ?>" media="screen and (max-width: 760px)">
    <link rel="stylesheet" href="/assets/stylesheets/ms/dashboard.css?v=<?php echo time(); ?>" media="screen and (max-width: 760px)">

    <link rel="shortcut icon" href="<?= htmlspecialchars(asset_url('/assets/images/branding/mark-only.png')); ?>" type="image/png">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://kit.fontawesome.com/79b279a6c9.js" crossorigin="anonymous"></script>

</head>

<body>

    <?php include("../common-sections/header.html"); ?>

    <section class="dashboard-hero">
        <div class="hero-container">
            <div class="user-profile">
                <h1>Hi, <?= htmlspecialchars($user_name) ?>! <span class="badge-choice">DELIVERY PREFERENCES</span></h1>
                <div class="user-stats">
                    <span><i class="material-symbols-outlined">person</i> Username: <?= htmlspecialchars($user_username) ?></span>
                    <span><i class="material-symbols-outlined">calendar_month</i> Joined: <?= htmlspecialchars($joined_display) ?></span>
                    <span><i class="material-symbols-outlined">login</i> Last Login: <?= htmlspecialchars($last_login_display) ?></span>
                </div>
            </div>

            <nav class="dashboard-tabs">
                <a href="?t=overview" class="tab <?= $page == 'overview' ? 'active' : '' ?>">Overview</a>
                <a href="?t=profile" class="tab <?= $page == 'profile' ? 'active' : '' ?>">Profile</a>
                <a href="?t=upsmc" class="tab <?= $page == 'upsmc' ? 'active' : '' ?>">Delivery Preferences</a>
                <a href="?t=wallet" class="tab <?= $page == 'wallet' ? 'active' : '' ?>">Wallet</a>
            </nav>
        </div>
    </section>

    <main class="dashboard-body">

        <?php if ($page == 'overview'): ?>
            <div class="grid-main">
                <div class="col-primary">

                    <section class="card">
                        <h3>Track a Package</h3>
                        <form class="track-form" method="GET" action="/track">
                            <input type="text" name="id" placeholder="Tracking Number or Delivery Notice" required>
                            <button class="btn-gold" type="submit">Track <i class="material-symbols-outlined">chevron_right</i></button>
                            <a href="#" class="link-help js-open-live-chat">Help <i class="material-symbols-outlined">help</i></a>
                        </form>
                    </section>

                    <section class="card" id="shipment-activity">
                        <h3>Shipment Activity</h3>
                        <div class="activity-tabs">
                            <a href="?t=overview&a=incoming#shipment-activity" class="<?= $active_activity == 'incoming' ? 'active' : '' ?>">Incoming</a>
                            <a href="?t=overview&a=outgoing#shipment-activity" class="<?= $active_activity == 'outgoing' ? 'active' : '' ?>">Outgoing</a>
                            <a href="?t=overview&a=delivered#shipment-activity" class="<?= $active_activity == 'delivered' ? 'active' : '' ?>">Delivered</a>
                            <a href="?t=overview&a=pickups#shipment-activity" class="<?= $active_activity == 'pickups' ? 'active' : '' ?>">Pickups</a>
                            <a href="?t=overview&a=instore#shipment-activity" class="<?= $active_activity == 'instore' ? 'active' : '' ?>">In-Store</a>
                        </div>

                        <div class="activity-content">
                            <?php if (!empty($current_shipments)): ?>
                                <div class="shipment-list">
                                    <?php foreach ($current_shipments as $ship): ?>
                                        <!-- Whole row is clickable -->
                                        <a class="shipment-item" href="<?= htmlspecialchars($ship['url']) ?>" style="display:block; text-decoration:none; color:inherit;">
                                            <div class="ship-details">
                                                <span class="ship-id"><?= htmlspecialchars($ship['id']) ?></span>
                                                <span class="ship-date">Scheduled: <?= htmlspecialchars($ship['date']) ?></span>
                                            </div>
                                            <div class="ship-meta">
                                                <span class="status-badge-outline"><?= htmlspecialchars($ship['status']) ?></span>
                                                <i class="material-symbols-outlined">chevron_right</i>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                        <img class="art-icon-img shipment-empty-icon" src="<?= htmlspecialchars(asset_url('/assets/images/ups/3d-icons/ups-inbound-package-gray.png')); ?>" alt="Shipment activity icon">
                                    <p>You have no <?= htmlspecialchars($active_activity) ?> shipments to show</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <section class="card support-card">
                        <h3>Support & Case Management</h3>
                        <div class="empty-state">
                        <img class="art-icon-img support-icon" src="<?= htmlspecialchars(asset_url('/assets/images/ups/3d-icons/ups-questions-support.png')); ?>" alt="Support icon">
                            <h4>How can we help you today?</h4>
                            <a href="/support/" class="btn-outline">Get Support <i class="material-symbols-outlined">chevron_right</i></a>
                        </div>
                    </section>

                </div>

                <div class="col-secondary">
                    <section class="card">
                        <h3>Create a Shipment</h3>
                        <a href="/shipping/create/" class="btn-outline">Ship Now <i class="material-symbols-outlined">chevron_right</i></a>
                    </section>

                    <section class="card center-text">
                        <h3>My Bills</h3>
                        <img class="art-icon-img bills-icon" src="<?= htmlspecialchars(asset_url('/assets/images/ups/3d-icons/ups-pay-bill.png')); ?>" alt="Billing icon">
                        <p><strong>You are not enrolled!</strong></p>
                        <p>You can view past invoices and manage payments.</p>
                        <a href="/dashboard/?t=wallet#payment-methods" class="link-blue">Visit the Billing Center <i class="material-symbols-outlined">open_in_new</i></a>
                    </section>

                    <section class="card center-text">
                        <h3>Accounts & Saved Payment Methods</h3>
                        <img class="art-icon-img account-icon" src="<?= htmlspecialchars(asset_url('/assets/images/ups/3d-icons/gray-payment.png')); ?>" alt="Saved payment methods icon">
                        <p>You have no saved accounts & payment methods</p>
                        <a href="?t=wallet" class="link-blue">View Wallet <i class="material-symbols-outlined">chevron_right</i></a>
                    </section>

                    <section class="card">
                        <h3>Quick Actions</h3>
                        <div class="quick-grid">
                    <a href="/shipping/create/" class="q-item"><img src="<?= htmlspecialchars(asset_url('/assets/images/ups/3d-icons/ups-create-shipment.png')); ?>" alt="Start a Shipment icon">Start a Shipment</a>
                    <a href="/shipping/create/?service=pickup" class="q-item"><img src="<?= htmlspecialchars(asset_url('/assets/images/ups/3d-icons/ups-seasonal-variable-volume.png')); ?>" alt="Schedule a Pickup icon">Schedule a Pickup</a>
                    <a href="https://www.ups.com/osa/orderSupplies?loc=en_US" target="_blank" rel="noopener noreferrer" class="q-item"><img src="<?= htmlspecialchars(asset_url('/assets/images/ups/3d-icons/ups-free-supplies.png')); ?>" alt="Order Supplies icon">Order Supplies</a>
                    <a href="/dashboard/?t=upsmc" class="q-item"><img src="<?= htmlspecialchars(asset_url('/assets/images/ups/3d-icons/ups-account.png')); ?>" alt="Preferences icon">Preferences</a>
                        </div>
                    </section>
                </div>
            </div>

        <?php elseif ($page == 'profile'): ?>
            <section class="profile-layout">
                <div class="profile-left">
                    <article id="my-information-card" class="card profile-card">
                        <div class="card-head">
                            <h3>My Information</h3>
                            <a href="?t=profile&amp;edit=phone#my-information-card" class="link-blue profile-edit-trigger">Edit <i class="material-symbols-outlined">edit</i></a>
                        </div>
                        <?php if (!empty($profile_notice)): ?>
                            <p class="profile-success-msg"><?= htmlspecialchars($profile_notice) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($profile_error)): ?>
                            <p class="profile-error-msg"><?= htmlspecialchars($profile_error) ?></p>
                        <?php endif; ?>
                        <form class="profile-phone-form <?= $profile_edit_open ? '' : 'is-hidden' ?>" method="POST" action="/dashboard/?t=profile#my-information-card" novalidate>
                            <input type="hidden" name="profile_action" value="update_phone">
                            <div class="profile-phone-row">
                                <div class="profile-country-col">
                                    <label for="profile-country-code">Country Code</label>
                                    <select id="profile-country-code" name="country_code" class="js-country-code-select" data-selected="<?= htmlspecialchars((string)$user_country) ?>" required>
                                        <option value="<?= htmlspecialchars((string)$user_country) ?>" selected>
                                            <?= htmlspecialchars(!empty($user_country) ? $user_country : '+1') ?>
                                        </option>
                                    </select>
                                </div>
                                <div class="profile-phone-col">
                                    <label for="profile-phone-number">Phone Number</label>
                                    <input id="profile-phone-number" type="tel" name="phone_number" inputmode="numeric" pattern="[0-9]{7,15}" minlength="7" maxlength="15" value="<?= htmlspecialchars((string)$user_phone) ?>" required>
                                </div>
                                <div class="profile-phone-actions">
                                    <button type="submit" class="btn-outline-small">Save</button>
                                </div>
                            </div>
                            <p class="profile-inline-error" aria-live="polite"></p>
                        </form>
                        <div class="info-grid profile-info-grid">
                            <div class="info-group">
                                <label>First and Last Name</label>
                                <p><?= htmlspecialchars($user_name) ?></p>
                            </div>
                            <div class="info-group">
                                <label>Phone</label>
                                <p><?= !empty($user_phone) ? htmlspecialchars(trim(($user_country ? $user_country . ' ' : '') . $user_phone)) : 'No phone number' ?></p>
                            </div>
                            <div class="info-group full-width">
                                <label>Email Address</label>
                                <p class="email-row">
                                    <span><?= htmlspecialchars($user_email) ?></span>
                                    <span class="status-badge">VERIFIED</span>
                                </p>
                            </div>
                            <div class="info-group full-width">
                                <label>Default Shipping Account</label>
                                <p><a href="/dashboard/add-payment-method/" class="link-blue">Add a Payment Method</a></p>
                            </div>
                        </div>
                    </article>

                    <article class="card profile-card">
                        <h3>Account Settings</h3>
                        <div class="info-grid profile-info-grid">
                            <div class="info-group">
                                <label>User ID</label>
                                <p><?= htmlspecialchars($user_username) ?></p>
                            </div>
                            <div class="info-group">
                                <label>Password</label>
                                <p>••••••••••••</p>
                                <a href="/login/forgot-password/" class="link-blue">Change Password</a>
                            </div>
                            <div class="info-group full-width">
                                <label>Default Address</label>
                                <p>No Default Address</p>
                            </div>
                        </div>
                    </article>

                    <article class="card profile-card">
                        <h3>Address Book</h3>
                        <p class="card-copy">Ship directly to your favorite people with saved addresses. You can also save time by importing or exporting your address book.</p>
                        <div class="profile-links-row">
                            <a href="#" class="link-blue">Manage Addresses <i class="material-symbols-outlined">chevron_right</i></a>
                            <a href="#" class="link-blue">Manage Contacts <i class="material-symbols-outlined">chevron_right</i></a>
                            <a href="#" class="link-blue">Distribution Lists <i class="material-symbols-outlined">chevron_right</i></a>
                        </div>
                    </article>

                    <article class="card profile-card">
                        <h3>Multi-Factor Authentication</h3>
                        <p class="card-copy">Keep your profile secure by setting up Multi-Factor Authentication (MFA) so you will be prompted to enter an MFA code on every login.</p>
                        <div class="profile-links-row">
                            <a href="#" class="link-blue">Set up MFA <i class="material-symbols-outlined">chevron_right</i></a>
                            <a href="/support/#faq-account" class="link-blue">Visit FAQ <i class="material-symbols-outlined">open_in_new</i></a>
                        </div>
                    </article>
                </div>

                <div class="profile-right">
                    <article class="card profile-card delete-profile-card">
                        <h3>Delete Profile</h3>
                        <p class="card-copy">If you delete your profile, we will delete saved addresses, contacts, payment options, and shipping preferences. Transaction records may still be retained for operational and legal reasons.</p>
                        <a href="#" class="link-blue">Delete My Profile <i class="material-symbols-outlined">chevron_right</i></a>
                    </article>
                </div>
            </section>

        <?php elseif ($page == 'upsmc'): ?>
            <section class="upsmc-layout">
                <div class="upsmc-col">
                    <article class="card upsmc-card">
                        <div class="upsmc-title-row">
                            <h3>Delivery Preferences<sup>®</sup> Membership Details</h3>
                            <span class="status-badge">ACTIVE</span>
                        </div>
                        <p class="upsmc-email"><strong><?= htmlspecialchars($user_email) ?></strong></p>
                        <div class="upsmc-details-list">
                            <p>Name: <?= htmlspecialchars($user_name) ?></p>
                            <p>Email : <?= htmlspecialchars($user_email) ?></p>
                            <p>Address: None Saved</p>
                            <p>Membership Since: <?= htmlspecialchars($joined_display) ?></p>
                        </div>
                        <div class="upsmc-link-row">
                            <a href="#" class="link-blue">Edit Membership <i class="material-symbols-outlined">chevron_right</i></a>
                            <a href="#" class="link-blue">Add New Membership <i class="material-symbols-outlined">chevron_right</i></a>
                        </div>
                    </article>

                    <article class="card upsmc-card">
                        <h3>Delivery Options</h3>
                        <p class="card-copy">Add an address to unlock benefits like driver instructions, gate code instructions, alternate delivery locations, and more.</p>
                        <a href="#" class="link-blue">Add Address <i class="material-symbols-outlined">chevron_right</i></a>
                    </article>
                </div>

                <div class="upsmc-col">
                    <article class="card upsmc-card">
                        <h3>Delivery Alerts</h3>
                        <p class="card-copy">Alerts set to: Email</p>
                        <p class="upsmc-subhead">Email Addresses:</p>
                        <ul class="upsmc-bullets">
                            <li><?= htmlspecialchars($user_email) ?></li>
                        </ul>
                        <div class="upsmc-link-row">
                            <a href="#" class="link-blue">Manage Alerts <i class="material-symbols-outlined">chevron_right</i></a>
                            <a href="#" class="link-blue">Package Matching <i class="material-symbols-outlined">chevron_right</i></a>
                        </div>
                    </article>

                    <article class="card upsmc-card">
                        <h3>Hold Packages</h3>
                        <p class="card-copy">We can hold your packages for up to seven days if you send them to a UPS Access Point<sup>®</sup>. You can pick them up when you return. For hold requests up to 14 days, we will hold on to your packages and deliver them to your home on your new selected delivery date.</p>
                        <p class="upsmc-subhead">Delivery Hold Dates:</p>
                        <p class="card-copy">There are no current requests.</p>
                        <a href="#" class="link-blue">Request a Hold <i class="material-symbols-outlined">chevron_right</i></a>
                    </article>
                </div>
            </section>

            <div class="upsmc-actions">
                <a href="#" class="btn-outline">Put Membership on Hold <i class="material-symbols-outlined">chevron_right</i></a>
                <a href="#" class="btn-outline">Delete This Membership <i class="material-symbols-outlined">chevron_right</i></a>
            </div>
        <?php elseif ($page == 'wallet'): ?>
            <section class="wallet-layout">
                <div class="wallet-left">
                    <article id="payment-methods" class="card wallet-card payment-methods-card">
                        <h3>My Payment Methods</h3>
                        <div class="wallet-alert-box">
                            <div class="wallet-alert-main">
                                <i class="material-symbols-outlined">info</i>
                                <p>
                                    <strong>If you pay invoices through the billing center, remember to update your payment cards there.</strong>
                                    <br>
                                    <a href="#" class="link-blue">Visit Billing Center <i class="material-symbols-outlined">open_in_new</i></a>
                                </p>
                            </div>
                            <i class="material-symbols-outlined wallet-alert-toggle">keyboard_arrow_up</i>
                        </div>
                        <?php if (!empty($wallet_notice)): ?>
                            <p class="wallet-success-msg"><?= htmlspecialchars($wallet_notice) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($wallet_error)): ?>
                            <p class="wallet-error-msg"><?= htmlspecialchars($wallet_error) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($wallet_payment_methods)): ?>
                            <div class="wallet-method-list">
                                <?php foreach ($wallet_payment_methods as $method): ?>
                                    <div class="wallet-method-item">
                                        <p class="method-label"><?= htmlspecialchars($method['label']) ?></p>
                                        <p class="method-meta"><?= htmlspecialchars($method['meta']) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="card-copy">You have not added any payment methods to your profile yet.</p>
                        <?php endif; ?>
                        <a href="/dashboard/add-payment-method/" class="btn-outline">Add New Payment Method <i class="material-symbols-outlined">chevron_right</i></a>
                    </article>
                </div>

                <div class="wallet-right">
                    <article class="card wallet-card">
                        <h3>Shipping Account Benefits:</h3>
                        <ul class="wallet-benefits-list">
                            <li>Ongoing discounts</li>
                            <li>Weekly billing</li>
                            <li>Flexible pickup options</li>
                            <li><a href="#" class="link-blue">View all benefits <i class="material-symbols-outlined">open_in_new</i></a></li>
                        </ul>
                    </article>

                    <article class="card wallet-card">
                        <h3>Save Up to 83%*</h3>
                        <p class="card-copy">Find personalized savings</p>
                        <a href="#" class="link-blue">Explore Discounts <i class="material-symbols-outlined">open_in_new</i></a>
                        <p class="wallet-note">*Terms and Conditions Apply</p>
                    </article>

                    <article class="card wallet-card">
                        <h3>My Discounts</h3>
                        <p class="card-copy">You have not added any discounts to your profile yet.</p>
                    </article>
                </div>
            </section>
        <?php endif; ?>

</main>
<script src="/assets/scripts/index.js?v=<?php echo time(); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var editTrigger = document.querySelector('.profile-edit-trigger');
    var phoneForm = document.querySelector('.profile-phone-form');
    if (editTrigger && phoneForm) {
        editTrigger.addEventListener('click', function (e) {
            e.preventDefault();
            phoneForm.classList.toggle('is-hidden');
            if (!phoneForm.classList.contains('is-hidden')) {
                var phoneInput = phoneForm.querySelector('input[name="phone_number"]');
                if (phoneInput) phoneInput.focus();
            }
        });

        var inlineError = phoneForm.querySelector('.profile-inline-error');
        var profilePhone = phoneForm.querySelector('input[name="phone_number"]');
        phoneForm.addEventListener('submit', function (e) {
            if (!profilePhone || !inlineError) return;
            var value = profilePhone.value.trim();
            var message = '';
            if (value.length === 0) {
                message = 'Phone number is required.';
            } else if (/\s/.test(value)) {
                message = 'Phone number cannot contain spaces.';
            } else if (!/^[0-9]+$/.test(value)) {
                message = 'Phone number must contain digits only.';
            } else if (value.length < 7) {
                message = 'Phone number is too short.';
            }

            inlineError.textContent = message;
            if (message) {
                e.preventDefault();
            }
        });
    }

    var countrySelects = document.querySelectorAll('.js-country-code-select');
    if (countrySelects.length) {
        fetch('/assets/scripts/country-codes.json')
            .then(function (response) { return response.json(); })
            .then(function (data) {
                var countries = Object.values(data);
                countrySelects.forEach(function (select) {
                    var selectedValue = select.getAttribute('data-selected') || '';
                    select.innerHTML = '';
                    countries.forEach(function (country) {
                        if (!country.phone || !country.phone.length) return;
                        var option = document.createElement('option');
                        option.value = country.phone[0];
                        option.textContent = country.emoji + ' ' + country.name + ' (' + country.phone[0] + ')';
                        if (selectedValue && option.value === selectedValue) {
                            option.selected = true;
                        }
                        select.appendChild(option);
                    });
                    if (!select.value && selectedValue) {
                        var fallback = document.createElement('option');
                        fallback.value = selectedValue;
                        fallback.textContent = selectedValue;
                        fallback.selected = true;
                        select.insertBefore(fallback, select.firstChild);
                    }
                });
            })
            .catch(function () {
                // Keep server-rendered fallback option when fetch is unavailable.
            });
    }
});
</script>
</body>
</html>

