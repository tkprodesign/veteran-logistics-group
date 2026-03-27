<?php require_once __DIR__ . '/../common-sections/globals.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Legal Center | Veteran Logistics Group</title>
    <link rel="stylesheet" href="/assets/stylesheets/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/stylesheets/legal.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
<link rel="shortcut icon" href="<?= htmlspecialchars(asset_url('/assets/images/branding/mark-only.png')); ?>" type="image/png">
</head>
<body>
<?php include('../common-sections/header.html'); ?>
<main class="legal-page">
    <div class="container">
        <section class="legal-hero">
            <h1>Legal Center</h1>
            <p>Find our legal notices, privacy information, and user rights pages in one place.</p>
            <p class="legal-meta">Effective date: March 27, 2026</p>
        </section>

        <section class="legal-section">
            <h2>Legal Pages</h2>
            <div class="legal-link-grid">
                <a href="/legal/protect-against-fraud/">Protect Against Fraud</a>
                <a href="/legal/terms-and-conditions/">Terms and Conditions</a>
                <a href="/legal/website-terms-of-use/">Website Terms of Use</a>
                <a href="/legal/california-privacy-rights/">Your California Privacy Rights</a>
                <a href="/legal/privacy-notice/">Privacy Notice</a>
                <a href="/legal/cookie-settings/">Cookie Settings</a>
                <a href="/legal/do-not-sell/">Do Not Sell or Share My Personal Information</a>
            </div>
        </section>
    </div>
</main>
<?php include('../common-sections/footer.html'); ?>
</body>
</html>
