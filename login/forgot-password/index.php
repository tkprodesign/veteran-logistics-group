<?php
include('./app.php'); // This contains the handler logic
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Veteran Logistics Group</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="shortcut icon" href="/assets/images/branding/mark-only.png?v=<?php echo time(); ?>" type="image/png">
    <link rel="stylesheet" href="/assets/stylesheets/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/stylesheets/forms.css?v=<?php echo time(); ?>">
</head>
<body class="login-page">

<section class="form">
    <form method="post" action="">
        <div class="container">

            <div class="heading">
                <span class="auth-logo-wrap">
                    <img src="/assets/images/branding/logo-stacked-light.png" alt="Veteran Logistics Group Logo" class="logo">
                </span>
                <h2>Forgot Password</h2>
                <p>Enter your email address or username to receive reset instructions.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="form-errors">
                    <p><?= htmlspecialchars($error) ?></p>
                </div>
            <?php elseif (!empty($success)): ?>
                <div class="form-success">
                    <p><?= $success ?></p>
                </div>
            <?php endif; ?>

            <?php if (empty($success)): ?>
            <div class="content">
                <div class="input-box">
                    <input type="text" name="email" placeholder="Enter your email or username*" required>
                </div>

                <div class="action-box">
                    <button type="submit" class="btn-primary">
                        Reset Password
                        <span class="btn-chevron" aria-hidden="true">›</span>
                    </button>
                    <p class="signup-text">
                        Remember your password? <a href="/login/">Login</a>
                    </p>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </form>
</section>

</body>
</html>


