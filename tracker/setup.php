<?php
// ============================================================
// MrsB Tracker — Client First-Time Password Setup
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

$token = trim($_GET['t'] ?? '');
if (!$token) {
    die('<p style="font-family:sans-serif;padding:2rem;color:#c00;">Invalid link. Please check your email.</p>');
}

$client = getClientByToken($token);
if (!$client) {
    die('<p style="font-family:sans-serif;padding:2rem;color:#c00;">Tracker not found. Please contact your trainer.</p>');
}

// If password already set, redirect to login
if ($client['password_set']) {
    header('Location: ' . SITE_URL . '/tracker/login.php?t=' . urlencode($token));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        setClientPassword((int)$client['id'], $password);
        $_SESSION['client_logged_in'] = true;
        $_SESSION['client_id']        = (int)$client['id'];
        $_SESSION['client_name']      = $client['name'];
        header('Location: ' . SITE_URL . '/tracker/?t=' . urlencode($token));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
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
            <div style="color:#d9c9d7;font-size:0.82rem;">First Time Setup</div>
        </div>
    </div>
</header>

<div class="container" style="max-width:480px;margin-top:3rem;">
    <div class="login-card" style="background:#fff;border-radius:12px;padding:2rem;box-shadow:0 4px 24px rgba(74,25,66,0.10);">

        <div style="text-align:center;margin-bottom:1.5rem;">
            <div style="font-size:2.5rem;">🔐</div>
            <h2 style="color:#4a1942;margin-top:0.5rem;">Welcome, <?= h($client['name']) ?>!</h2>
            <p style="color:#9a7a96;font-size:0.9rem;margin-top:0.5rem;">
                Please set a password to secure your personal tracker.
                You'll need this every time you log in.
            </p>
        </div>

        <?php if ($error): ?>
            <div class="flash flash-error"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="password">Choose a Password</label>
                <div style="position:relative;">
                    <input type="password" name="password" id="password"
                           class="form-control" placeholder="Min. 8 characters"
                           style="padding-right:3rem;" autofocus>
                    <button type="button" onclick="toggleField('password', this)"
                            style="position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9a7a96;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label for="confirm">Confirm Password</label>
                <div style="position:relative;">
                    <input type="password" name="confirm" id="confirm"
                           class="form-control" placeholder="Repeat your password"
                           style="padding-right:3rem;">
                    <button type="button" onclick="toggleField('confirm', this)"
                            style="position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9a7a96;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-full" style="margin-top:1rem;">
                Set Password &amp; Open My Tracker 💪
            </button>
        </form>
    </div>
</div>

<script>
const EYE_OPEN = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
const EYE_CLOSED = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

function toggleField(id, btn) {
    const input = document.getElementById(id);
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    btn.innerHTML = isHidden ? EYE_CLOSED : EYE_OPEN;
}
</script>

</body>
</html>
