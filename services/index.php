<?php
require_once __DIR__ . '/../common-sections/globals.php';
include("app.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Services | Veteran Logistics Group</title>

    <link rel="stylesheet" href="/assets/stylesheets/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/stylesheets/services.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/stylesheets/ts/main.css?v=<?php echo time(); ?>" media="screen and (max-width: 1120px)">
    <link rel="stylesheet" href="/assets/stylesheets/ts/services.css?v=<?php echo time(); ?>" media="screen and (max-width: 1120px)">
    <link rel="stylesheet" href="/assets/stylesheets/ms/main.css?v=<?php echo time(); ?>" media="screen and (max-width: 760px)">
    <link rel="stylesheet" href="/assets/stylesheets/ms/services.css?v=<?php echo time(); ?>" media="screen and (max-width: 760px)">
    <link rel="shortcut icon" href="<?= htmlspecialchars(asset_url('/assets/images/branding/mark-only.png')); ?>" type="image/png">

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

    <script src="https://kit.fontawesome.com/79b279a6c9.js" crossorigin="anonymous"></script>
</head>
<body>
<?php include("../common-sections/header.html"); ?>

<main class="services-page">
    <div class="container">
        <nav class="services-breadcrumb" aria-label="Breadcrumb">
            <a href="/">Home</a>
            <span>/</span>
            <a href="/shipping/">Shipping</a>
            <span>/</span>
            <span>Shipping Services</span>
        </nav>

        <section class="services-hero">
            <div class="hero-copy">
                <h1>Veteran Logistics Group Shipping Services</h1>
                <p>Whether you are shipping across the street or across the world, we have several service options to help you find the right balance of speed and cost.</p>
                <div class="hero-cta-row">
                    <a href="/shipping/" class="btn-gold">Ship Now <span class="material-symbols-outlined">chevron_right</span></a>
                    <a href="/support/" class="btn-outline">Get a Quote <span class="material-symbols-outlined">chevron_right</span></a>
                </div>
            </div>
            <div class="hero-media">
                <img src="<?= htmlspecialchars(asset_url('/assets/images/home/mc2.jpg')); ?>" alt="UPS delivery specialist standing in front of delivery vehicle">
            </div>
        </section>
    </div>

    <section class="service-tabs-block">
        <div class="container">
            <div class="service-tabs">
                <a class="active" href="#panel-domestic" data-service-panel="panel-domestic">Domestic Shipping Services</a>
                <a href="#panel-international" data-service-panel="panel-international">International Shipping Services</a>
                    <a href="#panel-additional" data-service-panel="panel-additional">Additional Veteran Logistics Group Services</a>
            </div>
        </div>
    </section>

    <section class="section shipping-services-switch">
        <div class="container">
            <div class="service-panel active" id="panel-domestic">
                <div class="section-heading">
                    <h2>Domestic Shipping Services</h2>
                    <p>We offer flexible <a href="/shipping/">Domestic Shipping Services</a> ready to meet your business needs.</p>
                </div>
                <div class="cards-grid three-up">
                    <article class="service-card"><h3>Budget-Friendly Shipping</h3><p>For shipments looking for reliable delivery without breaking the bank, choose UPS Ground or UPS SurePost options.</p></article>
                    <article class="service-card"><h3>Accelerated Shipping</h3><p>For important, but not critical shipments. Meet your deadlines with UPS 2nd Day Air or UPS 3 Day Select.</p></article>
                    <article class="service-card"><h3>Overnight Shipping</h3><p>Get guaranteed, time-definite next-day delivery to urgent shipments with UPS Next Day Air.</p></article>
                    <article class="service-card"><h3>Same-Day Delivery</h3><p>Get seamless same day delivery for local and shipping with Roadie and same day freight shipping with UPS Express Critical.</p></article>
                    <article class="service-card"><h3>Freight Shipping</h3><p><a href="/shipping/">UPS Supply Chain Solutions</a> offers a variety of services to simplify freight shipping and maximize your supply chain.</p></article>
                    <article class="service-card"><h3>Bulk Shipping</h3><p><a href="/shipping/">UPS Ground with Freight Pricing</a> offers savings on 150+ lb shipments being sent to facilities not designed to handle pallets.</p></article>
                </div>
            </div>

            <div class="service-panel" id="panel-international">
                <div class="section-heading">
                    <h2>International Shipping Services</h2>
                    <p>See our variety of <a href="/shipping/">International Shipping Services</a> to help you reach new markets.</p>
                </div>
                <div class="cards-grid three-up">
                    <article class="service-card"><h3>Economical Shipping</h3><p><a href="/shipping/">UPS Worldwide Economy</a> is our most economical cross-border option, with delivery in 5-12 days.</p></article>
                    <article class="service-card"><h3>Standard Shipping</h3><p>UPS Standard offers day-definite delivery in 2-7 days with Saturday delivery available for most regions.</p></article>
                    <article class="service-card"><h3>Expedited Shipping</h3><p>For important but not critical shipments, UPS Worldwide Expedited delivers in 2-5 business days.</p></article>
                    <article class="service-card"><h3>Express Shipping</h3><p>1-3 day delivery is available with Worldwide Express and Worldwide Saver by end of day.</p></article>
                    <article class="service-card"><h3>Same Day Shipping</h3><p>For urgent freight shipments, <a href="/shipping/">UPS Express Critical</a> is available for next flight out.</p></article>
                </div>
            </div>

            <div class="service-panel" id="panel-additional">
                <div class="section-heading">
                    <h2>Additional Veteran Logistics Group Services</h2>
                    <p>Explore how UPS can help your business beyond delivery.</p>
                </div>
                <div class="cards-grid three-up additional-grid">
                    <article class="service-card with-icon"><i class="material-symbols-outlined">storefront</i><h3>The UPS Store</h3><p>Pack, ship, print, rent mailboxes and more at neighborhood locations.</p><a href="/shipping/">Learn About The UPS Store <span class="material-symbols-outlined">chevron_right</span></a></article>
                    <article class="service-card with-icon"><i class="material-symbols-outlined">health_and_safety</i><h3>UPS Healthcare</h3><p>Access responsive and agile logistics that help patients get critical support when needed.</p><a href="/shipping/">Learn About UPS Healthcare <span class="material-symbols-outlined">open_in_new</span></a></article>
                    <article class="service-card with-icon"><i class="material-symbols-outlined">local_shipping</i><h3>UPS Supply Chain Solutions</h3><p>Manage global supply chain operations including logistics, distribution and transportation.</p><a href="/shipping/">Learn About UPS Supply Chain Solutions <span class="material-symbols-outlined">open_in_new</span></a></article>
                    <article class="service-card with-icon"><i class="material-symbols-outlined">check_box</i><h3>Roadie Same-Day Delivery</h3><p>A crowdsourced platform for same-day and local next-day delivery of nearly anything.</p><a href="/shipping/">Learn About Roadie <span class="material-symbols-outlined">open_in_new</span></a></article>
                    <article class="service-card with-icon"><i class="material-symbols-outlined">package_2</i><h3>Happy Returns</h3><p>With no box required and thousands of return locations, shoppers can return quickly.</p><a href="/shipping/">Learn About Happy Returns <span class="material-symbols-outlined">open_in_new</span></a></article>
                    <article class="service-card with-icon"><i class="material-symbols-outlined">warehouse</i><h3>Industry-Specific Logistics Solutions</h3><p>Optimize every facet of your business with specialized logistics programs.</p><a href="/shipping/">See Logistics Solutions <span class="material-symbols-outlined">chevron_right</span></a></article>
                </div>
            </div>
        </div>
    </section>

    <section id="value-added-services" class="section value-added">
        <div class="container">
            <div class="section-heading">
                <h2>Value-Added Services</h2>
                <p>Need more info about value-added services and their fees? Check our <a href="/support/">Daily Rate Guide</a>.</p>
            </div>

            <div class="value-layout">
                <aside class="value-nav" role="tablist" aria-label="Value Added Tabs">
                    <button class="active" type="button" role="tab" aria-selected="true" data-tab="delivery-options">Delivery Options</button>
                    <button type="button" role="tab" aria-selected="false" data-tab="additional-services-tab">Additional Services</button>
                </aside>

                <div class="value-content">
                    <div class="value-panel active" id="delivery-options" role="tabpanel">
                        <h3>Delivery Options</h3>
                        <ul>
                            <li><strong>Signature Required</strong><span>We capture a signature at delivery so you can have the peace of mind it was delivered successfully.</span></li>
                            <li><strong>Adult Signature Required</strong><span>21+ recipient signature to release shipment when needed.</span></li>
                            <li><strong>UPS Collect on Delivery (C.O.D.)</strong><span>Give your customers more flexibility by allowing them to make payments at delivery.</span></li>
                            <li><strong>Ship to a UPS Access Point</strong><span>Allow your customers to pick up their package at a secure and convenient location near them.</span></li>
                            <li><strong>Direct Delivery Only</strong><span>Avoid package reroutes and send to the exact address provided.</span></li>
                            <li><strong>Hold for Pickup</strong><span>Customers can pick up packages at a secure UPS location center at a time convenient for them.</span></li>
                        </ul>
                    </div>

                    <div class="value-panel" id="additional-services-tab" role="tabpanel">
                        <h3>Additional Services</h3>
                        <ul>
                            <li><strong>Declared Value</strong><span>Increase shipment protection on higher-value packages.</span></li>
                            <li><strong>Address Validation</strong><span>Reduce failed delivery attempts with more accurate destination data.</span></li>
                            <li><strong>Delivery Change Requests</strong><span>Allow modifications for address, date, and instructions while in transit.</span></li>
                            <li><strong>Customs Documentation</strong><span>Support for forms and processing on international shipments.</span></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="international-promo" class="section promo-strip">
        <div class="container">
            <div class="promo-inner">
                <div>
                    <h3>Save up to 65%* When You Ship International</h3>
                    <p>Start your discounted international shipment with this link and apply available savings.</p>
                    <a href="/shipping/">*Terms and Conditions</a>
                </div>
                <a href="/shipping/" class="btn-gold">Ship Here to Save <span class="material-symbols-outlined">chevron_right</span></a>
            </div>
        </div>
    </section>

    <section class="section protection">
        <div class="container">
            <div class="section-heading">
                <h2>Get Shipping Protection &amp; Insurance</h2>
            </div>
            <div class="cards-grid two-up">
                <article class="service-card"><h3>InsureShield Shipping Protection &amp; Risk Mitigation</h3><p>Reduce the stress of claims with specific policy protection and customizable coverage options.</p><a href="/support/">InsureShield</a></article>
                <article class="service-card"><h3>Insured High-Value Shipping with ParcelPro</h3><p>From high-value to high-density, protect your shipments with secure and compliant insurance options.</p><a href="/support/">ParcelPro</a></article>
            </div>
        </div>
    </section>

    <section class="section resources">
        <div class="container">
            <div class="section-heading">
                <h2>Additional Shipping Resources</h2>
                <p>Explore these resources to stay prepared and keep shipping simple.</p>
            </div>
            <div class="resource-links">
                <a href="/support/">Get a Quote <span class="material-symbols-outlined">chevron_right</span></a>
                <a href="/support/">UPS Rate Guide <span class="material-symbols-outlined">chevron_right</span></a>
                <a href="/shipping/">Order Supplies <span class="material-symbols-outlined">chevron_right</span></a>
                <a href="/support/">Find a Location <span class="material-symbols-outlined">chevron_right</span></a>
                <a href="/dashboard/?t=overview&a=outgoing#shipment-activity">View Shipping History <span class="material-symbols-outlined">chevron_right</span></a>
                <a href="/shipping/">Schedule a Pickup <span class="material-symbols-outlined">chevron_right</span></a>
            </div>
        </div>
    </section>

    <section class="section smarter-tools">
        <div class="container">
            <div class="section-heading">
                <h2>Tools for Smarter Shipping</h2>
            </div>
            <div class="tool-grid">
                <article class="tool-card">
                    <i class="material-symbols-outlined">inventory_2</i>
                    <h3>Shipping Guides</h3>
                    <p>From choosing the right package to printing labels, we break down how to master every step of the shipping process.</p>
                    <a href="/support/" class="btn-outline">How-To Guides &amp; Resources <span class="material-symbols-outlined">chevron_right</span></a>
                </article>
                <article class="tool-card">
                    <i class="material-symbols-outlined">deployed_code</i>
                    <h3>Shipping Tools</h3>
                    <p>From calculating costs to finding your nearest drop-off location, we have the tools you need to ship successfully.</p>
                    <a href="/shipping/" class="btn-outline">Explore Tools <span class="material-symbols-outlined">chevron_right</span></a>
                </article>
            </div>
        </div>
    </section>

    <section class="section experts">
        <div class="container">
            <div class="section-heading">
                <h2>Talk to an Expert</h2>
            </div>
            <div class="cards-grid two-up">
                <article class="service-card"><h3>Free Virtual Consultation</h3><p>Request this cost, 15 minute consultation to discuss how UPS can help improve your business with smarter shipping decisions.</p><a href="/support/">Request Free Consultation</a></article>
                <article class="service-card"><h3>Expert Supply Chain Consulting</h3><p>From evolving warehouse operations to endpoint visibility systems, get in touch with an expert consultant team.</p><a href="/support/">Let's Dive Deeper</a></article>
            </div>
        </div>
    </section>
</main>

<?php include("../common-sections/footer.html"); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var serviceTabs = document.querySelectorAll('.service-tabs a[data-service-panel]');
    var servicePanels = document.querySelectorAll('.service-panel');
    var valueButtons = document.querySelectorAll('.value-nav button[data-tab]');
    var valuePanels = document.querySelectorAll('.value-panel');

    function activateServicePanel(panelId) {
        serviceTabs.forEach(function (t) { t.classList.remove('active'); });
        servicePanels.forEach(function (p) { p.classList.remove('active'); });

        var tab = document.querySelector('.service-tabs a[data-service-panel=\"' + panelId + '\"]');
        var panel = document.getElementById(panelId);
        if (tab && panel) {
            tab.classList.add('active');
            panel.classList.add('active');
        }
    }

    if (serviceTabs.length && servicePanels.length) {
        serviceTabs.forEach(function (tab) {
            tab.addEventListener('click', function (e) {
                e.preventDefault();
                var panelId = tab.getAttribute('data-service-panel');
                activateServicePanel(panelId);
            });
        });

        var current = document.querySelector('.service-tabs a.active[data-service-panel]');
        activateServicePanel(current ? current.getAttribute('data-service-panel') : 'panel-domestic');
    }

    if (valueButtons.length && valuePanels.length) {
        valueButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var tab = button.getAttribute('data-tab');
                valueButtons.forEach(function (b) {
                    b.classList.remove('active');
                    b.setAttribute('aria-selected', 'false');
                });
                valuePanels.forEach(function (p) { p.classList.remove('active'); });
                button.classList.add('active');
                button.setAttribute('aria-selected', 'true');
                var panel = document.getElementById(tab);
                if (panel) panel.classList.add('active');
            });
        });
    }
});
</script>
</body>
</html>
