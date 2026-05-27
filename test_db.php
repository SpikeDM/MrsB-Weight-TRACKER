<?php
// TEMPORARY DIAGNOSTIC — DELETE THIS FILE AFTER USE
// Upload to server root, visit once, then delete immediately.

require_once __DIR__ . '/config/config.php';

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

echo '<pre style="font-family:monospace;padding:2rem;">';
echo "Host:    " . DB_HOST . "\n";
echo "DB Name: " . DB_NAME . "\n";
echo "User:    " . DB_USER . "\n";
echo "Charset: " . DB_CHARSET . "\n\n";

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "✅ CONNECTION SUCCESSFUL\n";
    $row = $pdo->query("SELECT VERSION() AS v")->fetch();
    echo "MySQL version: " . $row['v'] . "\n";
} catch (PDOException $e) {
    echo "❌ CONNECTION FAILED\n\n";
    echo "Error: " . $e->getMessage() . "\n";
}

echo '</pre>';
