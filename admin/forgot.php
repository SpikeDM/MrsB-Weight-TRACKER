<?php
// ============================================================
// MrsB Tracker — Admin Forgot Password
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

session_start();

$submitted = false;
$error     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $admin = getAdminByEmail($email);

        if ($admin) {
            try {
                $token    = generateAdminPasswordReset((int)$admin['id']);
                $resetUrl = SITE_URL . '/admin/reset.php?rt=' . urlencode($token);
                sendAdminResetEmail($admin, $resetUrl);
            } catch (\Exception $e) {
                error_log('Admin forgot password error: ' . $e->getMessage());
            }
        }

        // Always show success — never reveal if email exists
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
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>

<div class="login-wrap">
    <div class="login-card">
        <div class="login-logo">
            <?php if (file_exists(__DIR__ . '/../assets/img/logo-dark.jpg')): ?>
                <img src="<?= SITE_URL ?>/assets/img/logo-dark.jpg" alt="MrsB Fitness" style="height:80px;">
            <?php else: ?>
                <div class="logo-text">Mrs<span>B</span></div>
            <?php endif; ?>
            <p>Programme Tracker — Admin</p>
        </div>

        <?php if ($submitted): ?>
            <div style="text-align:center;">
                <div style="font-size:3rem;">📧</div>
                <h2 style="color:#4c1044;margin:0.75rem 0 0.5rem;font-size:1.2rem;">Check your email</h2>
                <p style="color:#9a7a96;font-size:0.9rem;line-height:1.6;">
                    If we found an account with that email, a reset link has been sent. The link expires in 24 hours.
                </p>
                <a href="<?= SITE_URL ?>/admin/login.php"
                   style="display:inline-block;margin-top:1.5rem;font-size:0.88rem;color:#9a7a96;">
                    ← Back to login
                </a>
            </div>

        <?php else: ?>
            <h2 style="color:#4c1044;font-size:1.1rem;margin-bottom:0.5rem;text-align:center;">Forgot your password?</h2>
            <p style="color:#9a7a96;font-size:0.85rem;text-align:center;margin-bottom:1.5rem;">
                Enter your email address and we'll send you a reset link.
            </p>

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
                <button type="submit" class="btn btn-primary btn-full" style="margin-top:0.5rem;">
                    Send Reset Link
                </button>
            </form>

            <p style="text-align:center;margin-top:1.25rem;font-size:0.85rem;">
                <a href="<?= SITE_URL ?>/admin/login.php" style="color:#9a7a96;">← Back to login</a>
            </p>
        <?php endif; ?>

    </div>
</div>

</body>
</html>
