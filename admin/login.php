<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

session_name(ADMIN_SESSION_NAME);
session_start();

// Already logged in
if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: /admin/');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM admin_users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username']  = $user['username'];
            header('Location: /admin/');
            exit;
        } else {
            $error = 'Incorrect username or password. Please try again.';
            // Debug - remove after testing
            if ($user) {
                $error .= ' (user found, hash mismatch)';
            } else {
                $error .= ' (user not found)';
            }
        }
    } else {
        $error = 'Please enter both username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=4">
    <?php require_once __DIR__ . '/../includes/pwa_head.php'; ?>
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

        <?php if ($error): ?>
            <div class="flash flash-error"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username"
                       class="form-control" autocomplete="username"
                       value="<?= h($_POST['username'] ?? '') ?>" autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div style="position:relative;">
                    <input type="password" name="password" id="password"
                           class="form-control" autocomplete="current-password"
                           style="padding-right:3rem;">
                    <button type="button" onclick="toggleField('password', this)"
                            style="position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9a7a96;"
                            title="Show/hide password">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-full" style="margin-top:0.5rem;">
                Log In
            </button>
        </form>

        <p style="text-align:center;margin-top:1.25rem;font-size:0.85rem;">
            <a href="<?= SITE_URL ?>/tracker/forgot.php" style="color:#9a7a96;">Forgot your password?</a>
        </p>
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
