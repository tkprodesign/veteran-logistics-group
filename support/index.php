<?php require_once __DIR__ . '/../common-sections/globals.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help and Support Center | Veteran Logistics Group</title>

    <link rel="stylesheet" href="/assets/stylesheets/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/stylesheets/support.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/stylesheets/ts/main.css?v=<?php echo time(); ?>" media="screen and (max-width: 1120px)">
    <link rel="stylesheet" href="/assets/stylesheets/ts/support.css?v=<?php echo time(); ?>" media="screen and (max-width: 1120px)">
    <link rel="stylesheet" href="/assets/stylesheets/ms/main.css?v=<?php echo time(); ?>" media="screen and (max-width: 760px)">
    <link rel="stylesheet" href="/assets/stylesheets/ms/support.css?v=<?php echo time(); ?>" media="screen and (max-width: 760px)">
    <link rel="shortcut icon" href="<?= htmlspecialchars(asset_url('/assets/images/branding/mark-only.png')); ?>" type="image/png">

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <script src="https://kit.fontawesome.com/79b279a6c9.js" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.9.3/dist/dotlottie-wc.js" type="module"></script>
</head>
<body>
<?php include("../common-sections/header.html"); ?>

<main class="support-page">
    <div class="container">
        <nav class="support-breadcrumb" aria-label="Breadcrumb">
            <a href="/">Home</a>
            <span>/</span>
            <span>Contact Us</span>
        </nav>

        <section class="support-hero">
            <h1>Help and Support Center</h1>
            <?php if (!isset($_COOKIE['user_email']) || empty($_COOKIE['user_email'])): ?>
            <div class="signin-strip">
                <p>Sign in to your shipper account to find the best support option for your needs. <a href="/login/">Log In &gt;</a></p>
            </div>
            <?php endif; ?>

            <div class="assistant-wrap" aria-hidden="true">
                <div class="assistant-bg">
                    <dotlottie-wc
                        class="support-lottie"
                        src="https://lottie.host/4883e397-3885-4589-87a1-8c9ad987e032/svqXIeapgv.lottie"
                        autoplay
                        loop
                    ></dotlottie-wc>
                </div>
            </div>
        </section>
    </div>

    <section class="support-alert">
        <div class="container">
            <div class="section-head">
                <h2 class="alert-title"><img src="<?= htmlspecialchars(asset_url('/assets/images/ups/3d-icons/alert.png')); ?>" alt="Alert icon"> Changes to Delivery May Impact Charges</h2>
            </div>
            <p>Service changes can impact delivery timing, routing and final charges. Review your shipment status details for the latest delivery updates.</p>
        </div>
    </section>

    <section class="support-categories">
        <div class="container">
            <div class="support-card-grid top-row">
                <article class="support-card">
                    <h3 class="card-title"><img src="<?= htmlspecialchars(asset_url('/assets/images/ups/3d-icons/location.png')); ?>" alt="Tracking icon"> Tracking</h3>
                    <a href="/track/">Understanding Tracking Status</a>
                    <a href="/track/">Delivery Notice</a>
                    <a href="/track/">Missed Package Delivery</a>
                    <a class="btn-gold js-open-support-chat" href="#" role="button">Get Tracking Help <span class="material-symbols-outlined">chevron_right</span></a>
                </article>

                <article class="support-card">
                    <h3 class="card-title"><img src="<?= htmlspecialchars(asset_url('/assets/images/ups/3d-icons/box.png')); ?>" alt="Shipping icon"> Shipping</h3>
                    <a href="/shipping/">Shipping Cost Estimator</a>
                    <a href="/services/">International Shipping</a>
                    <a href="/shipping/">Domestic Shipping</a>
                    <a href="/services/">Freight Shipping</a>
                    <a class="js-open-support-chat" href="#">Packaging and Shipping Supplies</a>
                    <a class="btn-gold js-open-support-chat" href="#" role="button">Get Shipping Help <span class="material-symbols-outlined">chevron_right</span></a>
                </article>

                <article class="support-card">
                    <h3 class="card-title"><img src="<?= htmlspecialchars(asset_url('/assets/images/ups/3d-icons/location.png')); ?>" alt="Delivery icon"> Delivery Changes</h3>
                    <a href="/track/">Change Delivery</a>
                    <a href="/track/">Deliver to New Address</a>
                    <a href="/shipping/create/">Schedule a Pickup</a>
                    <a href="/dashboard/">Delivery Preferences</a>
                    <a href="/track/exception/">Issue with my Delivery</a>
                    <a class="btn-gold js-open-support-chat" href="#" role="button">Get My Choice Help <span class="material-symbols-outlined">chevron_right</span></a>
                </article>
            </div>

            <div class="support-card-grid bottom-row">
                <article class="support-card">
                    <h3 class="card-title"><img src="<?= htmlspecialchars(asset_url('/assets/images/ups/3d-icons/claims.png')); ?>" alt="Claims icon"> Claims</h3>
                    <a class="js-open-support-chat" href="#">File a Claim</a>
                    <a class="js-open-support-chat" href="#">Check My Claims</a>
                    <a class="btn-gold js-open-support-chat" href="#" role="button">Get Claims Help <span class="material-symbols-outlined">chevron_right</span></a>
                </article>

                <article class="support-card">
                    <h3 class="card-title"><img src="<?= htmlspecialchars(asset_url('/assets/images/ups/3d-icons/user-arrow.png')); ?>" alt="Account icon"> My Account</h3>
                    <a href="/dashboard/">Manage Billing and Invoice</a>
                    <a href="/dashboard/?t=profile">Manage your profile</a>
                    <a href="/signup/">Sign up for My Choice</a>
                    <a class="btn-gold js-open-support-chat" href="#" role="button">Get Account Help <span class="material-symbols-outlined">chevron_right</span></a>
                </article>
            </div>
        </div>
    </section>

    <section class="support-faq" id="faq">
        <div class="container">
            <div class="section-head center">
                <h2>FAQ</h2>
            </div>

            <div class="faq-tabs">
                <button class="active" type="button" data-faq-tab="shipping" id="faq-tab-shipping">Shipping and Tracking</button>
                <button type="button" data-faq-tab="lost" id="faq-tab-lost">Lost or Damaged Packages</button>
                <button type="button" data-faq-tab="billing" id="faq-tab-billing">Billing</button>
                <button type="button" data-faq-tab="account" id="faq-tab-account">Account and Password</button>
                <button type="button" data-faq-tab="contacts" id="faq-tab-contacts">Additional Contacts</button>
            </div>

            <div class="faq-panels">
                <div class="faq-panel active" data-faq-panel="shipping">
                    <details><summary>Where's my package?</summary><div>Track your shipment in real time using your tracking number.</div></details>
                    <details><summary>My shipment says "Out for Delivery." What time will it be delivered?</summary><div>Most deliveries are completed by end of day depending on route conditions.</div></details>
                    <details><summary>Why hasn't my driver delivered my package yet?</summary><div>Delivery sequence changes during the day due to traffic and service commitments.</div></details>
                    <details><summary>How do I change a delivery I'm receiving?</summary><div>Use your tracking page and select available delivery change options.</div></details>
                    <details><summary>How do I change a delivery I've sent?</summary><div>Sign in to your shipping account and edit shipment preferences if available.</div></details>
                    <details><summary>I won't be home. Can I put a hold on my deliveries?</summary><div>Yes, request hold options from your tracking details page.</div></details>
                </div>

                <div class="faq-panel" data-faq-panel="lost">
                    <details><summary>How do I file a loss claim?</summary><div>Go to the claims section and submit your shipment details.</div></details>
                    <details><summary>How long does claims processing take?</summary><div>Claim timelines vary by shipment type and submitted documentation.</div></details>
                </div>

                <div class="faq-panel" data-faq-panel="billing">
                    <details><summary>Where can I view invoices?</summary><div>Use your dashboard billing section to review and download invoices.</div></details>
                    <details><summary>How do I update payment details?</summary><div>Open account settings and update your preferred payment method.</div></details>
                </div>

                <div class="faq-panel" data-faq-panel="account">
                    <details><summary>How do I reset my password?</summary><div>Use the forgot password option on the login page.</div></details>
                    <details><summary>How do I update profile details?</summary><div>Visit your dashboard profile tab and edit your information.</div></details>
                </div>

                <div class="faq-panel" data-faq-panel="contacts">
                    <details><summary>How do I contact support?</summary><div>Visit the support page and choose your preferred support category.</div></details>
                    <details><summary>How do I reach shipping support quickly?</summary><div>Use tracking or shipping help cards above for direct routes.</div></details>
                </div>
            </div>
        </div>
    </section>

</main>

<?php include("../common-sections/footer.html"); ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var tabButtons = document.querySelectorAll('.faq-tabs button[data-faq-tab]');
    var panels = document.querySelectorAll('.faq-panel[data-faq-panel]');
    var chatCtas = document.querySelectorAll('.js-open-support-chat');

    function openSupportChat() {
        if (typeof window.smartsupp === 'function') {
            window.smartsupp('chat:open');
            return true;
        }
        if (window._smartsupp && window._smartsupp.api && typeof window._smartsupp.api.open === 'function') {
            window._smartsupp.api.open();
            return true;
        }
        return false;
    }

    function activateFaqTab(key) {
        tabButtons.forEach(function (b) { b.classList.remove('active'); });
        panels.forEach(function (p) { p.classList.remove('active'); });
        var targetButton = document.querySelector('.faq-tabs button[data-faq-tab="' + key + '"]');
        var targetPanel = document.querySelector('.faq-panel[data-faq-panel="' + key + '"]');
        if (targetButton) targetButton.classList.add('active');
        if (targetPanel) targetPanel.classList.add('active');
    }

    tabButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var key = button.getAttribute('data-faq-tab');
            activateFaqTab(key);
        });
    });

    chatCtas.forEach(function (cta) {
        cta.addEventListener('click', function (event) {
            event.preventDefault();
            if (!openSupportChat()) {
                window.location.href = '/support/';
            }
        });
    });

    if (window.location.hash === '#faq-account') {
        activateFaqTab('account');
        var faqSection = document.getElementById('faq');
        if (faqSection) {
            setTimeout(function () {
                faqSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 60);
        }
    }
});
</script>
</body>
</html>
