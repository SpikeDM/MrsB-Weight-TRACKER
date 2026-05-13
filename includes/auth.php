<?php
// ============================================================
// MrsB Tracker — Admin Session Guard
// Include at the top of every admin page (after config)
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_name(ADMIN_SESSION_NAME);
    session_start();
}

if (empty($_SESSION['admin_logged_in'])) {
    $loginUrl = rtrim(SITE_URL, '/') . '/admin/login.php';
    header('Location: ' . $loginUrl);
    exit;
}
