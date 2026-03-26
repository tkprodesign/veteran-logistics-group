<?php
$templates = [
    ['file' => 'signup-verification.html', 'title' => 'Signup Verification Code'],
    ['file' => 'verification-code-resend.html', 'title' => 'Verification Code Resend'],
    ['file' => 'forgot-password-reset.html', 'title' => 'Forgot Password Reset Link'],
    ['file' => 'shipment-status-sender.html', 'title' => 'Shipment Status Update (Sender)'],
    ['file' => 'shipment-status-receiver.html', 'title' => 'Shipment Status Update (Receiver)'],
    ['file' => 'service-quote-internal-alert.html', 'title' => 'Service Quote Internal Alert'],
    ['file' => 'service-quote-customer-confirmation.html', 'title' => 'Service Quote Customer Confirmation'],
    ['file' => 'free-quote-admin-alert.html', 'title' => 'Free Quote Admin Alert'],
    ['file' => 'support-message.html', 'title' => 'Support Message'],
    ['file' => 'newsletter-admin-alert.html', 'title' => 'Newsletter Admin Alert'],
    ['file' => 'newsletter-thank-you.html', 'title' => 'Newsletter Thank You'],
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VLG Email Template Previews</title>
  <style>
    body{font-family:Arial,sans-serif;margin:0;background:#f3f5f9;color:#111827}
    .wrap{max-width:980px;margin:0 auto;padding:24px}
    h1{margin:0 0 10px;font-size:28px}
    p{margin:0 0 18px;color:#4b5563}
    ul{list-style:none;padding:0;margin:0;display:grid;gap:10px}
    a{display:block;padding:12px 14px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;text-decoration:none;color:#0f172a}
    a:hover{border-color:#94a3b8;background:#f8fafc}
    .hint{margin-top:18px;font-size:13px;color:#6b7280}
  </style>
</head>
<body>
  <div class="wrap">
    <h1>VLG Email Template Previews</h1>
    <p>Open each template in a browser tab to review rendering quickly.</p>
    <ul>
      <?php foreach ($templates as $template): ?>
        <li><a href="<?= htmlspecialchars($template['file']) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($template['title']) ?> → <?= htmlspecialchars($template['file']) ?></a></li>
      <?php endforeach; ?>
    </ul>
    <p class="hint">These are static preview files with sample values and email-safe inline CSS.</p>
  </div>
</body>
</html>
