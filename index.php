<?php
include('./app.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veteran Logistics Group | Ship and Track Online</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css" />
    
    
    <link rel="stylesheet" href="/assets/stylesheets/main.css?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/stylesheets/main.css'); ?>">
    <link rel="stylesheet" href="/assets/stylesheets/home.css?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/stylesheets/home.css'); ?>">
    <link rel="stylesheet" href="/assets/stylesheets/ts/main.css?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/stylesheets/ts/main.css'); ?>" media="screen and (max-width: 1120px)">
    <link rel="stylesheet" href="/assets/stylesheets/ts/home.css?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/stylesheets/ts/home.css'); ?>" media="screen and (max-width: 1120px)">
    <link rel="stylesheet" href="/assets/stylesheets/ms/main.css?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/stylesheets/ms/main.css'); ?>" media="screen and (max-width: 760px)">
    <link rel="stylesheet" href="/assets/stylesheets/ms/home.css?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/stylesheets/ms/home.css'); ?>" media="screen and (max-width: 760px)">

    <link rel="shortcut icon" href="<?= htmlspecialchars(asset_url('/assets/images/branding/mark-only.png')); ?>" type="image/png">

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://kit.fontawesome.com/79b279a6c9.js" crossorigin="anonymous"></script>

</head>
<body>
<?php include("common-sections/header.html"); ?>
<section class="hero">
    <!-- DESKTOP CURVE -->
    <div class="custom-shape-divider-bottom-1771138429">
        <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
            <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="shape-fill"></path>
        </svg>
    </div>
    <!-- TAB CURVE -->
    <div class="custom-shape-divider-bottom-1771153755">
        <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
            <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="shape-fill"></path>
        </svg>
    </div>
    <!-- MOBILE CURVE -->
    <div class="custom-shape-divider-bottom-1771153943">
        <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
            <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" class="shape-fill"></path>
        </svg>
    </div>
    <div class="swiper">
        <div class="swiper-wrapper">
            <div class="swiper-slide hero-1">
                <div class="dark-bg"></div>
                <div class="container">
                    <div class="heading">
                        <!-- <p class="pre-heading">Courier & Logistics Solution</p> -->
                        <h1 class="main-heading">Reliable Logistics. Trusted Delivery. Serving Those Who <span class="accent">Served.</span> </h1>
                        <p class="sub-heading">Veteran Logistics Group provides secure, fast, and reliable delivery solutions for veterans, their families, and government agencies, while operating within a broader carrier support network that includes UPS.</p>
                    </div>
                    <form class="c-t-a" action="/track/" method="get">
                        <div class="input-box">
                            <input type="text" name="id" placeholder="Tracking Number or InfoNoticeÂ®">
                        </div>
                        <button type="submit" class="pri">Track <span class="material-symbols-outlined">chevron_right</span></button>
                    </form>
                </div>
            </div>
            <div class="swiper-slide hero-2">
                <div class="dark-bg"></div>
                <div class="container">
                    <div class="heading">
                        <h1 class="main-heading">Engineered for Precision. <span class="accent">Built for Duty.</span></h1>
                        <p class="sub-heading">A specialized courier division delivering disciplined logistics for official operations, secure transport, and mission-critical movements.</p>
                    </div>
                    <form class="c-t-a" action="/track/" method="get">
                        <div class="input-box">
                            <input type="text" name="id" placeholder="Tracking Number or InfoNoticeÂ®">
                        </div>
                        <button type="submit" class="pri">Track<span class="material-symbols-outlined">chevron_right</span></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>





<section class="ups-branch-context">
    <div class="container">
        <div class="ups-branch-card">
            <div class="content">
                <p class="eyebrow">UPS Network Relationship</p>
                <h2>Veteran Logistics Group Operates as a UPS Branch Service Partner</h2>
                <p>
                    Veteran Logistics Group serves as a UPS branch service partner, providing day-to-day customer support, shipment processing, and delivery coordination under UPS-aligned standards.
                </p>
                <p>
                    This gives our customers branch-level help from a dedicated team while benefiting from UPS-compatible routing, tracking visibility, and dependable delivery coverage across domestic and international lanes.
                </p>
                <ul>
                    <li>Official branch-style customer assistance for veterans, families, and agencies.</li>
                    <li>UPS-aligned handling, tracking, and transit workflow standards.</li>
                    <li>Reliable escalation paths through broader UPS support channels when required.</li>
                </ul>
            </div>
            <div class="visual">
                <img src="https://www.ups.com/assets/resources/webcontent/images/ups-logo.svg" alt="Official UPS logo">
            </div>
        </div>
    </div>
</section>


<section class="why-choose-us editing">
    <div class="container">
        <div class="heading .heading-1">
            <h2>Precision in Motion. Deliveries You Can Count On</h2>
            <p>From urgent parcels to secure government deliveries, Veteran Logistics Group handles every shipment with <b>care, speed, and reliability</b>. Our team turns complex logistics into <b>smooth, dependable solutions</b>, so you can focus on what matters most.</p>
        </div>
        <div class="content">
            <div class="col">
                <h4>Operational Excellence</h4>
                <p>Reliability. Every package is delivered efficiently, safely, and on schedule.</p>
            </div>
            <div class="col">
                <h4>Time-Definite Logistics</h4>
                <p>Punctuality. Every shipment arrives when expected, no exceptions.</p>
            </div>
            <div class="col">
                <h4>Chain of Custody</h4>
                <p>Security. Sensitive documents and valuable parcels are protected throughout every step.</p>
            </div>
            <div class="col">
                <h4>Standardized Reliability</h4>
                <p>Consistency. Every delivery follows a structured process, guaranteeing dependable results.</p>
            </div>
            <div class="col">
                <h4>Network Optimization</h4>
                <p>Precision. Deliveries follow a controlled, efficient process from pickup to drop-off.</p>
            </div>
            <div class="col">
                <h4>Core Operational Mandate</h4>
                <p>Professionalism. Discipline. Dedication. Safety and efficiency define every operation.</p>
            </div>

        </div>
    </div>
</section>





<section class="tools">
    <div class="container">
        <div class="left"></div>
        <div class="right">
            <div class="heading">
                <h2>Tools for Every Step of The Shipping Process</h2>
                <img src="<?= htmlspecialchars(asset_url('/assets/images/home/mc5.jpg')); ?>" alt="shipping tools">
                <p>Explore pricing, send off a batch shipment or change a delivery with our easy-to-use shipping tools.</p>
                <a href="/shipping">See Shipping Tools<span class="material-symbols-outlined">chevron_right</span></a>
            </div>
        </div>
    </div>
</section>





<section class="services-alt">
    <div class="container">
        <div class="heading">
            <h2>Logistics Solutions for Veterans and Government Services</h2>
            <p>From urgent parcels to critical government documents, Veteran Logistics Group delivers with precision, security, and discipline, with network support that includes UPS services where needed. Going the extra mile for those who served.</p>
            <div class="toggle">
                <button href="#" class="btn1 active">Business</button>
                <button href="#" class="btn2">Personal</button>
            </div>
        </div>
        <div class="content">
            <!-- Business / Government Services -->
            <div class="g1 active">
                <div class="col">
                    <h3>Government Logistics</h3>
                    <p>Compliance & Security. Time-sensitive and confidential deliveries for government agencies & personnel.</p>
                    <a href="/shipping">Start Order <span class="material-symbols-outlined">chevron_right</span></a>
                </div>
                <div class="col">
                    <h3>Bulk & Scheduled Deliveries</h3>
                    <p>Efficiency. Large shipments and recurring deliveries for organizations are executed seamlessly.</p>
                    <a href="/shipping">Book Bulk <span class="material-symbols-outlined">chevron_right</span></a>
                </div>
                <div class="col">
                    <h3>Inter-City & Regional Delivery</h3>
                    <p>Coverage. Shipments reach cities and regions on schedule, supporting official operations.</p>
                    <a href="/shipping">Ship Route <span class="material-symbols-outlined">chevron_right</span></a>
                </div>
                <div class="col">
                    <h3>Document & Official Parcel Delivery</h3>
                    <p>Confidentiality. Critical documents and packages are transported securely at every stage.</p>
                    <a href="/shipping">Send Docs <span class="material-symbols-outlined">chevron_right</span></a>
                </div>
            </div>

            <!-- Personal / Veteran & Family Services -->
            <div class="g2">
                <div class="col">
                    <h3>Same-Day Delivery</h3>
                    <p>Urgency. Critical packages for veterans and families are picked up and delivered the same day.</p>
                    <a href="/shipping">Ship Today <span class="material-symbols-outlined">chevron_right</span></a>
                </div>
                <div class="col">
                    <h3>Family Parcel Delivery</h3>
                    <p>Care. Personal packages and parcels are handled safely, ensuring timely arrival to loved ones.</p>
                    <a href="/shipping">Send Parcel <span class="material-symbols-outlined">chevron_right</span></a>
                </div>
                <div class="col">
                    <h3>Assisted Pickup & Delivery</h3>
                    <p>Convenience. Pickups and deliveries for elderly or mobility-challenged veterans are supported efficiently.</p>
                    <a href="/shipping">Book Pickup <span class="material-symbols-outlined">chevron_right</span></a>
                </div>
                <div class="col">
                    <h3>Event & Specialty Deliveries</h3>
                    <p>Flexibility. Packages for personal events, celebrations, or special requests are managed with attention to detail.</p>
                    <a href="/shipping">Ship Event <span class="material-symbols-outlined">chevron_right</span></a>
                </div>
            </div>
        </div>

    </div>
</section>




<section class="banner-1">
    <div class="container">
        <div class="left">
            <h3>Save up to 65%* When You Ship International</h3>
            <p>Start your discounted international shipment with this link to automatically apply discount code â€œGoIntL2026.â€</p>
            <a href="#">*
                <span class="txt">Terms and Conditions apply</span>
                <span class="material-symbols-outlined icon">open_in_new</span>
            </a>
        </div>
        <div class="right">
            <a href="/shipping?coupon=GoIntL2026">Ship Here to Save<span class="material-symbols-outlined">chevron_right</span></a>
        </div>
    </div>
</section>




<section class="cards-container">
    <div class="container">
        <div class="heading heading-1-1-1">
            <h2>World-Class Services You Can Count On</h2>
            <p>Customer first, people led, innovation driven.</p>
        </div>
        <div class="content">
            <div class="col">
                <div class="img-wrapper">
                    <img src="<?= htmlspecialchars(asset_url('/assets/images/home/cd1.jpg')); ?>" alt="Ship and Scale With High Standards">
                </div>
                <div class="card-content">
                    <h4>Ship and Scale With High Standards</h3>
                    <p>When demand grows, you need the right shipping partner. Luxury brand Anima Iris turned to a major carrier network to deliver around the globe.</p>
                    <a target="_blank" href="https://about.ups.com/us/en/our-stories/customer-first/see-how-ups-delivers-for-one-luxury-bag-brand-crafted-by-african.html">
                        <span class="text">See the Success Story</span>
                        <span class="material-symbols-outlined icon">open_in_new</span>
                    </a>
                </div>
            </div>
            <div class="col">
                <div class="img-wrapper">
                    <img src="<?= htmlspecialchars(asset_url('/assets/images/home/cd2.jpg')); ?>" alt="5 Things Every Business Should Know About Returns">
                </div>
                <div class="card-content">
                    <h4>5 Things Every Business Should Know About Returns</h4>
                    <p>Yes, shoppers do read your return policy, and having a good one can make or break customer loyalty.</p>
                    <a target="_blank" href="https://about.ups.com/us/en/our-stories/customer-first/5-things-every-business-should-know-about-returns-in-2025-and-be.html">
                        <span class="text">Learn How</span>
                        <span class="material-symbols-outlined icon">open_in_new</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>




<section class="important-updates">
    <div class="container">
        <div class="heading heading-1-1">
            <h2>Important Updates</h2>
        </div>
        <div class="content">
            <details open>
                <summary>
                    Fuel Surcharge
                    <span class="material-symbols-outlined accordion-icon">keyboard_arrow_down</span>
                </summary>
                <div class="inner-content">
                    <p>Effective March 2, 2026, the U.S. International Ground Export Import Fuel Surcharge will change.</p>
                    <a href="https://assets.ups.com/adobe/assets/urn:aaid:aem:13ea22a9-bd18-49ee-a87c-c3b6f761a002/original/as/us-domestic-fuel-flyer.pdf" target="_blank" rel="noopener noreferrer">Read More About the Rate Change <span class="material-symbols-outlined">open_in_new</span></a>
                </div>
            </details>

            <details>
                <summary>
                    Information about Flight 2976 Accident
                    <span class="material-symbols-outlined accordion-icon">chevron_right</span>
                </summary>
                <div class="inner-content">
                    <p>Read more on the <a href="https://about.ups.com/us/en/newsroom/ups-aircraft-accident.html" target="_blank" rel="noopener noreferrer">UPS aircraft accident <span class="material-symbols-outlined">open_in_new</span></a></p>
                </div>
            </details>

            <details>
                <summary>
                    Updated Tariff and Rate and Service Guides
                    <span class="material-symbols-outlined accordion-icon">chevron_right</span>
                </summary>
                <div class="inner-content">
                    <p>Carrier tariff, service guide, and network terms were updated effective January 26, 2026.</p>
                    <a href="https://www.ups.com/us/en/support/shipping-support/shipping-costs-rates" target="_blank" rel="noopener noreferrer">View Guides <span class="material-symbols-outlined">open_in_new</span></a>
                </div>
            </details>

            <details>
                <summary>
                    Domestic Fuel Surcharge
                    <span class="material-symbols-outlined accordion-icon">chevron_right</span>
                </summary>
                <div class="inner-content">
                    <p>Effective January 5, 2026, the U.S. Ground Domestic and the UPS Ground SaverÂ® Fuel Surcharge will change. <a href="https://assets.ups.com/adobe/assets/urn:aaid:aem:13ea22a9-bd18-49ee-a87c-c3b6f761a002/original/as/us-domestic-fuel-flyer.pdf" target="_blank" rel="noopener noreferrer">Read More About the Rate Change <span class="material-symbols-outlined">open_in_new</span></a></p>
                </div>
            </details>

            <details>
                <summary>
                    Effective January 26, 2026: Large Package and Additional Handling Changes
                    <span class="material-symbols-outlined accordion-icon">chevron_right</span>
                </summary>
                <div class="inner-content">
                    <p>Changes previously scheduled for December 22, 2025, have been delayed until January 26, 2026...</p>
                </div>
            </details>

            <details>
                <summary>
                    Demand Surcharge Update
                    <span class="material-symbols-outlined accordion-icon">chevron_right</span>
                </summary>
                <div class="inner-content">
                    <p>Updated Demand Surcharge information is now available for review... effective September 28, 2025.</p>
                    <a href="https://assets.ups.com/adobe/assets/urn:aaid:aem:2c542692-de10-4fa3-b507-3b4f181e0953/original/as/demand-surcharges-us-en.pdf" target="_blank" rel="noopener noreferrer">Read more on applicable Demand Surcharges <span class="material-symbols-outlined">open_in_new</span></a>
                </div>
            </details>

            <details>
                <summary>
                    Learn More About Recent Trade Policy and Tariff Changes
                    <span class="material-symbols-outlined accordion-icon">chevron_right</span>
                </summary>
                <div class="inner-content">
                    <p>Weâ€™ll help you stay informed about the <a href="https://www.ups.com/us/en/shipping/international-shipping/tariffs" target="_blank" rel="noopener noreferrer">impacts of the new tariffs <span class="material-symbols-outlined">open_in_new</span></a></p>
                </div>
            </details>
        </div>
    </div>
</section>





<?php include("common-sections/footer.html"); ?>





<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
<script src="/assets/scripts/home.js?v=<?php echo filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/scripts/home.js'); ?>"></script>
</body>
</html>
