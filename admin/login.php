<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

session_name(ADMIN_SESSION_NAME);
session_start();

// Already logged in
if (!empty($_SESSION['admin_logged_in'])) {
    redirect(SITE_URL . '/admin/');
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
            redirect(SITE_URL . '/admin/');
        } else {
            $error = 'Incorrect username or password. Please try again.';
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
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <?php require_once __DIR__ . '/../includes/pwa_head.php'; ?>
</head>
<body>

<div class="login-wrap">
    <div class="login-card">
        <div class="login-logo">
            <?php if (file_exists(__DIR__ . '/../assets/img/logo.png')): ?>
                <img src="<?= SITE_URL ?>/assets/img/logo.png" alt="Mrs B" style="height:60px;">
            <?php else: ?>
                <div class="logo-text">Mrs <span>B</span></div>
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
                <input type="password" name="password" id="password"
                       class="form-control" autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary btn-full" style="margin-top:0.5rem;">
                Log In
            </button>
        </form>
    </div>
</div>

</body>
</html>
