<?php
// ============================================================
// MrsB Tracker — Configuration
// Edit these values before deploying to the live server
// ============================================================

// Fix HTTPS detection behind 20i proxy
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}


// --- Database ---
define('DB_HOST', 'sdb-82.hosting.stackcp.net');
define('DB_NAME', 'tracker-353038391d1f');
define('DB_USER', 'tracker-353038391d1f');
define('DB_PASS', '*u-G)q2wRdxf');
define('DB_CHARSET', 'utf8mb4');

// --- Site ---
define('SITE_URL', 'https://tracker.mrsbfitness.co.uk');  // No trailing slash
define('SITE_NAME', 'MrsB Fitness Tracker');

// --- Email ---

define('SMTP_HOST',   'smtp.mrsbfitness.co.uk');
define('SMTP_PORT',   465);
define('SMTP_USER',   'tracker@mrsbfitness.co.uk');
define('SMTP_PASS',   'Bw9a2824f');
define('SMTP_SECURE', 'ssl');
define('MAIL_FROM', 'tracker@mrsbfitness.co.uk');
define('MAIL_FROM_NAME', 'MrsB Fitness');

// --- Admin ---
// Default admin password is: MrsB2024!
// To generate a new hash, run: php -r "echo password_hash('YourNewPassword', PASSWORD_BCRYPT);"
// Then update the admin_users table in the DB.
define('ADMIN_SESSION_NAME', 'mrsb_admin');

// --- Timezone ---
date_default_timezone_set('Europe/London');

// --- Environment ---
// Set to 'production' on live server to suppress errors
define('APP_ENV', 'production');

if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}
