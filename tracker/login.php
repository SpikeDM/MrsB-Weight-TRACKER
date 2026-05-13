<?php
// ============================================================
// MrsB Tracker — Client Login (email-based, no token needed)
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

// Already logged in — go straight to tracker
if (isClientLoggedIn()) {
    redirect(SITE_URL . '/tracker/');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        $error = 'Please enter your email address and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $client = getClientByEmail($email);

        if (!$client) {
            $error = 'No account found with that email. Please contact your trainer.';
        } elseif (!$client['password_set']) {
            // First time — send them to setup
            $_SESSION['pending_client_id'] = (int)$client['id'];
            redirect(SITE_URL . '/tracker/setup.php');
        } elseif (verifyClientPassword($client, $password)) {
            // Success — log in
            $_SESSION['client_logged_in'] = true;
            $_SESSION['client_id']        = (int)$client['id'];
            $_SESSION['client_name']      = $client['name'];
            redirect(SITE_URL . '/tracker/');
        } else {
            $error = 'Incorrect password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=3">
    <?php require_once __DIR__ . '/../includes/pwa_head.php'; ?>
</head>
<body>

<header class="site-header">
    <div class="logo-area">
        <?php if (file_exists(__DIR__ . '/../assets/img/logo.png')): ?>
            <img src="<?= SITE_URL ?>/assets/img/logo.png" alt="MrsB Fitness">
        <?php else: ?>
            <div class="logo-text">Mrs<span>B</span></div>
        <?php endif; ?>
        <div>
            <div style="color:#fff;font-weight:700;font-size:1.1rem;">Programme Tracker</div>
            <div style="color:#d9c9d7;font-size:0.82rem;">Client Login</div>
        </div>
    </div>
</header>

<div class="container" style="max-width:480px;margin-top:3rem;">
    <div class="login-card" style="background:#fff;border-radius:12px;padding:2rem;box-shadow:0 4px 24px rgba(74,25,66,0.10);">

        <div style="text-align:center;margin-bottom:1.5rem;">
            <div style="font-size:2.5rem;">💪</div>
            <h2 style="color:#4a1942;margin-top:0.5rem;">Welcome back!</h2>
            <p style="color:#9a7a96;font-size:0.9rem;margin-top:0.5rem;">
                Enter your email and password to access your tracker.
            </p>
        </div>

        <?php if ($error): ?>
            <div class="flash flash-error"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" name="email" id="email"
                       class="form-control"
                       placeholder="Your email address"
                       value="<?= h($_POST['email'] ?? '') ?>"
                       autocomplete="email" autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div style="position:relative;">
                    <input type="password" name="password" id="password"
                           class="form-control"
                           placeholder="Your password"
                           style="padding-right:3rem;"
                           autocomplete="current-password">
                    <button type="button" onclick="togglePwd()"
                            style="position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9a7a96;">
                        <svg id="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-full" style="margin-top:1rem;">
                Log In
            </button>
        </form>

        <p style="text-align:center;margin-top:1.5rem;font-size:0.85rem;">
            <a href="<?= SITE_URL ?>/tracker/forgot.php" style="color:#9a7a96;">Forgot your password?</a>
        </p>
    </div>
</div>

<script>
function togglePwd() {
    const f = document.getElementById('password');
    f.type = f.type === 'password' ? 'text' : 'password';
}
</script>

</body>
</html>
