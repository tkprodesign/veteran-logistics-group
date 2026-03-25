<?php include('app.php');?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification | Veteran Logistics Group</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css" />
    
    
    <link rel="stylesheet" href="/assets/stylesheets/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/stylesheets/forms.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/stylesheets/ts/main.css?v=<?php echo time(); ?>" media="screen and (max-width: 1120px)">
    <link rel="stylesheet" href="/assets/stylesheets/ts/home.css?v=<?php echo time(); ?>" media="screen and (max-width: 1120px)">
    <link rel="stylesheet" href="/assets/stylesheets/ms/main.css?v=<?php echo time(); ?>" media="screen and (max-width: 760px)">
    <link rel="stylesheet" href="/assets/stylesheets/ms/home.css?v=<?php echo time(); ?>" media="screen and (max-width: 760px)">

    <link rel="shortcut icon" href="/assets/images/branding/mark-only.png?v=<?php echo time(); ?>" type="image/png">

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script src="https://kit.fontawesome.com/79b279a6c9.js" crossorigin="anonymous"></script>
</head>
<body>
<?php include("../common-sections/header.html"); ?>



<section class="form">
    <form action="" method="post">
        <div class="container">
            <div class="heading">
                <h2>Verify Your Email</h2>
                <p>Please enter the verification code that was sent to the email address below</p>
                <p><b>Email: </b><span><?php echo $email; ?></span></p>
            </div>
            <div class="content">
                <?php if (!empty($errors)): ?>
                    <div style="color:red; margin-bottom:15px;">
                        <?php foreach ($errors as $error): ?>
                            <p><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="input-box">
                    <input type="text" name="verification_code" placeholder="Verification Code">
                </div>
                <div class="input-box">
                    <button type="submit">Verify my Email<span class="material-symbols-outlined">chevron_right</span></button>
                </div>
            </div>
        </div>
    </form>
</section>





<?php include("../common-sections/footer.html"); ?>




<!-- Scripts -->
<!-- <script src="/assets/scripts/user.js?v=<?php echo time(); ?>"></script> -->
</body>
</html>
