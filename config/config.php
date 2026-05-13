<?php
// ============================================================
// MrsB Tracker — Configuration
// Edit these values before deploying to the live server
// ============================================================

// --- Database ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('DB_CHARSET', 'utf8mb4');

// --- Site ---
define('SITE_URL', 'https://tracker.mrsbfitness.co.uk');  // No trailing slash
define('SITE_NAME', 'Mrs B Fitness Tracker');

// --- Admin ---
// Default admin password is: MrsB2024!
// To generate a new hash, run: php -r "echo password_hash('YourNewPassword', PASSWORD_BCRYPT);"
// Then update the admin_users table in the DB.
define('ADMIN_SESSION_NAME', 'mrsb_admin');

// --- Timezone ---
date_default_timezone_set('Europe/London');

// --- Environment ---
// Set to 'production' on live server to suppress errors
define('APP_ENV', 'development');

if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}
