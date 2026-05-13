<?php
// ============================================================
// MrsB Tracker — Admin Password Reset (via token link)
// ============================================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

$resetToken = trim($_GET['rt'] ?? '');
if (!$resetToken) {
    die('<p style="font-family:sans-serif;padding:2rem;color:#c00;">Invalid or missing reset link.</p>');
}

$admin = getAdminByResetToken($resetToken);
if (!$admin) {
    die('<p style="font-family:sans-serif;padding:2rem;color:#c00;">This reset link has expired or is invalid. <a href="' . SITE_URL . '/admin/forgot.php">Request a new one</a>.</p>');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';

    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        setAdminPassword((int)$admin['id'], $password);
        clearAdminPasswordReset((int)$admin['id']);

        // Log admin in
        $_SESSION[ADMIN_SESSION_NAME] = true;
        header('Location: ' . SITE_URL . '/admin/');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — <?= SITE_NAME ?></title>
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

        <h2 style="color:#4c1044;font-size:1.1rem;margin-bottom:0.5rem;text-align:center;">Set a new password</h2>
        <p style="color:#9a7a96;font-size:0.85rem;text-align:center;margin-bottom:1.5rem;">
            Choose a new admin password below.
        </p>

        <?php if ($error): ?>
            <div class="flash flash-error"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="password">New Password</label>
                <div style="position:relative;">
                    <input type="password" name="password" id="password"
                           class="form-control"
                           placeholder="At least 8 characters"
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
                           class="form-control"
                           placeholder="Repeat your new password"
                           style="padding-right:3rem;">
                    <button type="button" onclick="toggleField('confirm', this)"
                            style="position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9a7a96;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-full" style="margin-top:0.5rem;">
                Set New Password
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
