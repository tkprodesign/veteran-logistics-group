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
<header class="cp-topbar">
    <div class="container cp-topbar-inner">
        <a href="/control-panel/page/" class="cp-topbar-brand">
<img src="/assets/images/branding/logo-horizontal-light.png?v=<?php echo time(); ?>" alt="Veteran Logistics Group Logo">
            <div class="cp-topbar-brand-copy">
                <strong>Control Panel</strong>
<span>Veteran Logistics Group</span>
            </div>
        </a>
        <nav class="cp-topbar-nav" aria-label="Control Panel Navigation">
            <ul>
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
</header>
