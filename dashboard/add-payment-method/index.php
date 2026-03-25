<?php
include('../app.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Payment Method | Veteran Logistics Group</title>

    <link rel="stylesheet" href="/assets/stylesheets/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/stylesheets/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/stylesheets/add-payment-method.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/stylesheets/ts/main.css?v=<?php echo time(); ?>" media="screen and (max-width: 1120px)">
    <link rel="stylesheet" href="/assets/stylesheets/ms/main.css?v=<?php echo time(); ?>" media="screen and (max-width: 760px)">
    <link rel="shortcut icon" href="/assets/images/branding/mark-only.png?v=<?php echo time(); ?>" type="image/png">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
</head>
<body>
<?php include("../../common-sections/header.html"); ?>

<section class="dashboard-hero">
    <div class="hero-container">
        <div class="user-profile">
            <h1>Secure Payment Setup <span class="badge-choice">WALLET SECURITY</span></h1>
            <div class="user-stats">
                <span><i class="material-symbols-outlined">person</i> Username: <?= htmlspecialchars($user_username) ?></span>
                <span><i class="material-symbols-outlined">calendar_month</i> Joined: <?= htmlspecialchars($joined_display) ?></span>
                <span><i class="material-symbols-outlined">login</i> Last Login: <?= htmlspecialchars($last_login_display) ?></span>
            </div>
        </div>
    </div>
</section>

<main class="add-method-page">
    <div class="add-method-shell">
        <div class="add-method-head">
            <h2>Add New Payment Method</h2>
            <a href="/dashboard/?t=wallet" class="link-blue">Back to Wallet <i class="material-symbols-outlined">chevron_right</i></a>
        </div>

        <div class="security-banner">
            <i class="material-symbols-outlined">verified_user</i>
            <p>Security notice: never enter wallet seed phrases or private keys. Only use tokenized card data or public wallet addresses.</p>
        </div>

        <?php if (!empty($wallet_error)): ?>
            <p class="wallet-error-msg"><?= htmlspecialchars($wallet_error) ?></p>
        <?php endif; ?>

        <form method="post" id="secureAddMethodForm" class="step-form">
            <input type="hidden" name="wallet_action" value="add_payment_method">
            <input type="hidden" name="method_type" id="method_type" value="card">

            <div class="stepper" aria-label="Payment setup steps">
                <button type="button" class="step active" data-step-go="1"><span>1</span> Choose Method</button>
                <button type="button" class="step" data-step-go="2"><span>2</span> Enter Details</button>
                <button type="button" class="step" data-step-go="3"><span>3</span> Verify Ownership</button>
                <button type="button" class="step" data-step-go="4"><span>4</span> Review & Save</button>
            </div>

            <section class="step-panel active" data-step="1">
                <h3>Choose Payment Type</h3>
                <div class="method-choice-grid">
                    <button type="button" class="method-choice active" data-choice="card">
                        <i class="material-symbols-outlined">credit_card</i>
                        <strong>Card</strong>
                        <span>Use tokenized card details.</span>
                    </button>
                    <button type="button" class="method-choice" data-choice="crypto">
                        <i class="material-symbols-outlined">account_balance_wallet</i>
                        <strong>Crypto Wallet</strong>
                        <span>Use public address + signature proof.</span>
                    </button>
                </div>
            </section>

            <section class="step-panel" data-step="2">
                <div class="method-fields card-fields active">
                    <h3>Card Details</h3>
                    <div class="method-grid">
                        <label>Card Brand<input type="text" name="card_brand" placeholder="Visa, Mastercard"></label>
                        <label>Last 4 Digits<input type="text" name="card_last4" maxlength="4" placeholder="1234"></label>
                        <label>Expiry Month<input type="text" name="exp_month" maxlength="2" placeholder="MM"></label>
                        <label>Expiry Year<input type="text" name="exp_year" maxlength="4" placeholder="YYYY"></label>
                        <label class="full">Processor Token<input type="text" name="processor_token" placeholder="tok_..."></label>
                    </div>
                </div>
                <div class="method-fields crypto-fields">
                    <h3>Crypto Wallet Details</h3>
                    <div class="method-grid">
                        <label>Network<input type="text" name="wallet_network" placeholder="Bitcoin, Ethereum, USDT-TRC20"></label>
                        <label class="full">Public Wallet Address<input type="text" name="wallet_address" placeholder="0x... or bc1..."></label>
                    </div>
                </div>
            </section>

            <section class="step-panel" data-step="3">
                <div class="method-fields card-fields active">
                    <h3>Card Verification</h3>
                    <p>Confirm your token source and processor mapping before saving.</p>
                    <div class="method-grid">
                        <label class="full">Token Source Note<input type="text" name="card_token_note" placeholder="Stripe tokenization v3"></label>
                    </div>
                </div>
                <div class="method-fields crypto-fields">
                    <h3>Wallet Ownership Verification</h3>
                    <p>Provide signed message or transaction hash proving ownership.</p>
                    <div class="method-grid">
                        <label class="full">Ownership Proof<input type="text" name="ownership_proof" placeholder="Signed message / tx hash"></label>
                    </div>
                </div>
            </section>

            <section class="step-panel" data-step="4">
                <h3>Review & Save</h3>
                <p>Submit to add the method. You will return to Wallet and see the saved method summary.</p>
                <ul class="review-list">
                    <li>Method type selected and validated</li>
                    <li>No sensitive seed phrase/private key requested</li>
                    <li>Post-save destination: Wallet overview</li>
                </ul>
            </section>

            <div class="step-actions">
                <button type="button" class="btn-outline" id="prevStepBtn">Previous</button>
                <button type="button" class="btn-outline" id="nextStepBtn">Next</button>
                <button type="submit" class="btn-gold" id="saveMethodBtn" style="display:none;">Save Payment Method <i class="material-symbols-outlined">chevron_right</i></button>
            </div>
        </form>
    </div>
</main>

<script src="/assets/scripts/index.js?v=<?php echo time(); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var currentStep = 1;
    var maxStep = 4;
    var methodTypeInput = document.getElementById('method_type');
    var stepPanels = document.querySelectorAll('.step-panel');
    var stepButtons = document.querySelectorAll('.stepper .step');
    var prevBtn = document.getElementById('prevStepBtn');
    var nextBtn = document.getElementById('nextStepBtn');
    var saveBtn = document.getElementById('saveMethodBtn');
    var methodChoices = document.querySelectorAll('.method-choice');

    function applyMethodVisibility(choice) {
        document.querySelectorAll('.card-fields').forEach(function (el) {
            el.classList.toggle('active', choice === 'card');
        });
        document.querySelectorAll('.crypto-fields').forEach(function (el) {
            el.classList.toggle('active', choice === 'crypto');
        });
    }

    function renderStep() {
        stepPanels.forEach(function (panel) {
            panel.classList.toggle('active', Number(panel.getAttribute('data-step')) === currentStep);
        });
        stepButtons.forEach(function (btn) {
            btn.classList.toggle('active', Number(btn.getAttribute('data-step-go')) === currentStep);
        });
        prevBtn.style.visibility = currentStep === 1 ? 'hidden' : 'visible';
        nextBtn.style.display = currentStep === maxStep ? 'none' : 'inline-flex';
        saveBtn.style.display = currentStep === maxStep ? 'inline-flex' : 'none';
    }

    methodChoices.forEach(function (choiceBtn) {
        choiceBtn.addEventListener('click', function () {
            var choice = choiceBtn.getAttribute('data-choice');
            methodTypeInput.value = choice;
            methodChoices.forEach(function (btn) { btn.classList.remove('active'); });
            choiceBtn.classList.add('active');
            applyMethodVisibility(choice);
        });
    });

    stepButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            currentStep = Number(btn.getAttribute('data-step-go'));
            renderStep();
        });
    });

    prevBtn.addEventListener('click', function () {
        if (currentStep > 1) {
            currentStep--;
            renderStep();
        }
    });

    nextBtn.addEventListener('click', function () {
        if (currentStep < maxStep) {
            currentStep++;
            renderStep();
        }
    });

    applyMethodVisibility(methodTypeInput.value);
    renderStep();
});
</script>
</body>
</html>
