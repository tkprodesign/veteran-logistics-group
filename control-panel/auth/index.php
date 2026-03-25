<?php include('./app.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Panel Login | Veteran Logistics Group</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="/assets/stylesheets/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/stylesheets/forms.css?v=<?php echo time(); ?>">
    <link rel="shortcut icon" href="/assets/images/branding/mark-only.png?v=<?php echo time(); ?>" type="image/png">
</head>
<body class="login-page">
<section class="form-section">
    <form method="post" action="">
        <div class="container">
            <div class="heading">
                <img src="/assets/images/branding/logo-stacked-light.png" alt="Veteran Logistics Group Logo" class="logo">
                <h2>Control Panel Access</h2>
            </div>
            <?php if (!empty($error)): ?>
                <div class="form-errors">
                    <p><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>
            <div class="content">
                <div class="input-box">
                    <input type="text" name="username" placeholder="Email or Username*" required>
                </div>
                <div class="input-box">
                    <input type="password" name="password" placeholder="Password*" required>
                </div>
                <div class="action-box">
                    <button type="submit" class="btn-primary">
                        Continue
                        <span class="material-symbols-outlined">chevron_right</span>
                    </button>
                    <p class="signup-text"><a href="/dashboard/">Back to Dashboard</a></p>
                </div>
            </div>
        </div>
    </form>
</section>
</body>
</html>


