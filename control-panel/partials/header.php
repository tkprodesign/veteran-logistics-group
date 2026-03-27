<?php
$cpCurrentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$cpNavItems = [
    '/control-panel/page/' => 'Overview',
    '/control-panel/site-users/' => 'Site Users',
    '/control-panel/shipments/' => 'Shipments',
    '/control-panel/service-quotes/' => 'Service Quotes',
    '/control-panel/payment-proofs/' => 'Payment Proofs',
    '/control-panel/exception-payments/' => 'Exception Payments',
    '/dashboard/' => 'Dashboard'
];
?>
<header>
    <div class="container">
        <div class="left">
            <a href="/control-panel/page/" id="logo">
                <img src="/assets/images/branding/logo-horizontal-light.png?v=<?php echo time(); ?>" alt="Veteran Logistics Group Logo">
            </a>
            <nav>
                <ul class="pri-nav">
                    <?php foreach ($cpNavItems as $cpHref => $cpLabel): ?>
                        <li>
                            <a href="<?= htmlspecialchars($cpHref) ?>" class="<?= ($cpCurrentPath === $cpHref) ? 'active' : '' ?>">
                                <?= htmlspecialchars($cpLabel) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </div>
        <div class="right">
            <div class="cta">
                <a href="/dashboard/" class="dtp">Dashboard</a>
                <a href="/logout/?next=home" class="dtp dtp-secondary">Logout</a>
                <a href="/dashboard/" class="mb" aria-label="Dashboard">
                    <span class="material-symbols-outlined">account_circle</span>
                </a>
            </div>
        </div>
    </div>
</header>
