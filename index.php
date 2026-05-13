<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

session_name(ADMIN_SESSION_NAME);
session_start();

if (!empty($_SESSION['admin_logged_in'])) {
    redirect(SITE_URL . '/admin/');
} else {
    redirect(SITE_URL . '/admin/login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="logo-area">
        <?php if (file_exists(__DIR__ . '/assets/img/logo.png')): ?>
            <img src="<?= SITE_URL ?>/assets/img/logo.png" alt="MrsB Fitness">
        <?php else: ?>
            <div class="logo-text">Mrs<span>B</span></div>
        <?php endif; ?>
        <div>
            <div style="color:#fff;font-weight:700;font-size:1.1rem;">Programme Tracker</div>
            <div style="color:#d9c9d7;font-size:0.82rem;">MrsB Fitness</div>
        </div>
    </div>
</header>

<div class="container" style="max-width:520px;margin-top:3rem;text-align:center;">
    <div class="card" style="padding:2.5rem 2rem;">
        <?php if (file_exists(__DIR__ . '/assets/img/logo-dark.jpg')): ?>
            <img src="<?= SITE_URL ?>/assets/img/logo-dark.jpg" alt="MrsB Fitness" style="height:90px;margin-bottom:1.5rem;">
        <?php endif; ?>

        <h1 style="color:#4c1044;font-size:1.4rem;margin-bottom:0.5rem;">Welcome to the MrsB Fitness Programme Tracker</h1>
        <p style="color:#9a7a96;font-size:0.95rem;line-height:1.6;margin-bottom:2rem;">
            Your personal tracker link will have been sent to you by email when you joined the programme.
        </p>

        <div style="display:flex;flex-direction:column;gap:1rem;">
            <div style="background:#f5f0f4;border-radius:8px;padding:1.25rem;text-align:left;">
                <div style="font-weight:700;color:#4c1044;margin-bottom:0.4rem;">📧 Have your tracker link?</div>
                <p style="color:#9a7a96;font-size:0.88rem;margin:0;">Click the link in your welcome email to go straight to your tracker.</p>
            </div>

            <div style="background:#f5f0f4;border-radius:8px;padding:1.25rem;text-align:left;">
                <div style="font-weight:700;color:#4c1044;margin-bottom:0.4rem;">🔑 Lost your link or forgotten your password?</div>
                <p style="color:#9a7a96;font-size:0.88rem;margin-bottom:0.75rem;">Enter your email address and we'll send everything you need.</p>
                <a href="<?= SITE_URL ?>/tracker/forgot.php" class="btn btn-primary btn-full">
                    Get My Tracker Link
                </a>
            </div>
        </div>

        <p style="margin-top:2rem;font-size:0.82rem;color:#bba8b9;">
            Not on the MrsB Fitness programme yet?
            <a href="https://www.mrsbfitness.co.uk" target="_blank" style="color:#d60058;">Visit mrsbfitness.co.uk</a>
        </p>
    </div>
</div>

</body>
</html>
