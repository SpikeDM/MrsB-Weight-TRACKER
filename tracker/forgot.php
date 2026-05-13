<?php
// ============================================================
// MrsB Tracker — Forgot Password
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

session_start();

$submitted = false;
$error     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email  = trim($_POST['email'] ?? '');

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $client = getClientByEmail($email);

        // Always show success — never reveal if email exists
        if ($client) {
            $resetToken = generatePasswordReset((int)$client['id']);
            $resetUrl   = SITE_URL . '/tracker/reset.php?rt=' . urlencode($resetToken);
            sendPasswordResetEmail($client, $resetUrl);
        }

        $submitted = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — <?= SITE_NAME ?></title>
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
            <div style="color:#d9c9d7;font-size:0.82rem;">Password Reset</div>
        </div>
    </div>
</header>

<div class="container" style="max-width:480px;margin-top:3rem;">
    <div class="login-card" style="background:#fff;border-radius:12px;padding:2rem;box-shadow:0 4px 24px rgba(76,16,68,0.10);">

        <?php if ($submitted): ?>
            <div style="text-align:center;">
                <div style="font-size:3rem;">📧</div>
                <h2 style="color:#4c1044;margin:0.75rem 0 0.5rem;">Check your email</h2>
                <p style="color:#9a7a96;font-size:0.95rem;line-height:1.6;">
                    If we found an account with that email address, we've sent a password reset link.
                    The link will expire in 24 hours.
                </p>
                <p style="color:#9a7a96;font-size:0.85rem;margin-top:1rem;">
                    Can't find the email? Check your spam folder.
                </p>
                <p style="margin-top:1.25rem;font-size:0.85rem;">
                    <a href="<?= SITE_URL ?>/tracker/find.php" style="color:#d60058;">Lost your tracker link too? Get it here →</a>
                </p>
                <p style="margin-top:0.75rem;font-size:0.85rem;">
                    <a href="javascript:history.back()" style="color:#9a7a96;">← Back to login</a>
                </p>
            </div>

        <?php else: ?>
            <div style="text-align:center;margin-bottom:1.5rem;">
                <div style="font-size:2.5rem;">🔑</div>
                <h2 style="color:#4c1044;margin-top:0.5rem;">Forgot your password?</h2>
                <p style="color:#9a7a96;font-size:0.9rem;margin-top:0.5rem;">
                    Enter your email address and we'll send you a password reset link.
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
                           placeholder="Enter your email address"
                           value="<?= h($_POST['email'] ?? '') ?>"
                           autofocus>
                </div>
                <button type="submit" class="btn btn-primary btn-full" style="margin-top:1rem;">
                    Send Reset Link
                </button>
            </form>

            <p style="text-align:center;margin-top:1.5rem;font-size:0.85rem;">
                <a href="javascript:history.back()" style="color:#9a7a96;">← Back to login</a>
            </p>
        <?php endif; ?>

    </div>
</div>

</body>
</html>
