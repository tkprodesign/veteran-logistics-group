<?php
include('./app.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Veteran Logistics Group</title>
    <link rel="shortcut icon" href="/assets/images/branding/mark-only.png?v=<?php echo time(); ?>" type="image/png">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="/assets/stylesheets/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/stylesheets/forms.css?v=<?php echo time(); ?>">
</head>
<body class="login-page">

<section class="form">
    <form method="post" action="">
        <?php if ($postLoginRedirect !== ''): ?>
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($postLoginRedirect) ?>">
        <?php endif; ?>
        <?php if ($requiredLogin): ?>
            <input type="hidden" name="required_login" value="1">
        <?php endif; ?>

        <div class="container">
            <div class="heading">
                <span class="auth-logo-wrap">
                    <img src="/assets/images/branding/logo-stacked-light.png" alt="Veteran Logistics Group Logo" class="logo">
                </span>
                <h2>Welcome</h2>
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
                    <div class="forgot-link">
                        <a href="/login/forgot-password/">Forgot Username/Password?</a>
                    </div>
                </div>

                <div class="terms-box">
                    <p>
                        By continuing, I agree to the
                        <a href="/legal/terms-and-conditions/">service terms and conditions</a>
                        and the
                        <a href="/legal/website-terms-of-use/">platform use agreement</a>.
                    </p>
                </div>

                <div class="action-box">
                    <button type="submit" class="btn-primary">
                        Continue
                        <span class="material-symbols-outlined">chevron_right</span>
                    </button>
                    <p class="signup-text">
                        Don't have a profile? <a href="/signup/">Sign Up</a>
                    </p>
                </div>
            </div>
        </div>
    </form>
</section>

</body>
</html>


