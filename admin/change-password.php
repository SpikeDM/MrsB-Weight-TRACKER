<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current  = $_POST['current_password'] ?? '';
    $new      = $_POST['new_password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!$current || !$new || !$confirm) {
        $error = 'Please fill in all fields.';
    } elseif ($new !== $confirm) {
        $error = 'New passwords do not match.';
    } elseif (strlen($new) < 8) {
        $error = 'New password must be at least 8 characters.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare('SELECT * FROM admin_users WHERE username = ? LIMIT 1');
        $stmt->execute([$_SESSION['admin_username']]);
        $user = $stmt->fetch();

        if ($user && password_verify($current, $user['password_hash'])) {
            $hash = password_hash($new, PASSWORD_BCRYPT);
            $stmt = $db->prepare('UPDATE admin_users SET password_hash = ? WHERE username = ?');
            $stmt->execute([$hash, $_SESSION['admin_username']]);
            $success = 'Password changed successfully!';
        } else {
            $error = 'Current password is incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password — <?= SITE_NAME ?></title>
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
            <div style="color:#d9c9d7;font-size:0.82rem;">Admin Panel</div>
        </div>
    </div>
    <div style="color:#d9c9d7;font-size:0.85rem;">
        Logged in as <strong style="color:#fff;"><?= h($_SESSION['admin_username']) ?></strong>
    </div>
</header>

<nav class="admin-nav">
    <a href="<?= SITE_URL ?>/admin/">Dashboard</a>
    <a href="<?= SITE_URL ?>/admin/create_client.php">+ New Client</a>
    <a href="<?= SITE_URL ?>/admin/change-password.php" class="active">Change Password</a>
    <a href="<?= SITE_URL ?>/admin/logout.php" class="nav-divider">Log Out</a>
</nav>

<div class="container-wide">
    <div style="max-width:500px;margin:2rem auto;">
        <div class="card" style="padding:2rem;">
            <h2 style="margin-bottom:1.5rem;">Change Password</h2>

            <?php if ($error): ?>
                <div class="flash flash-error"><?= h($error) ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="flash flash-success"><?= h($success) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <div style="position:relative;">
                        <input type="password" name="current_password" id="current_password"
                               class="form-control" style="padding-right:3rem;">
                        <button type="button" onclick="toggleField('current_password', this)"
                                style="position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9a7a96;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <div style="position:relative;">
                        <input type="password" name="new_password" id="new_password"
                               class="form-control" style="padding-right:3rem;">
                        <button type="button" onclick="toggleField('new_password', this)"
                                style="position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9a7a96;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div style="position:relative;">
                        <input type="password" name="confirm_password" id="confirm_password"
                               class="form-control" style="padding-right:3rem;">
                        <button type="button" onclick="toggleField('confirm_password', this)"
                                style="position:absolute;right:0.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9a7a96;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-full" style="margin-top:0.5rem;">
                    Update Password
                </button>
            </form>
        </div>
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
