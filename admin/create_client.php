<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

$newClient   = null;
$trackerUrl  = null;
$emailSent   = false;
$errors      = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name       = trim($_POST['name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $startDate  = trim($_POST['start_date'] ?? '');
    $endDate    = trim($_POST['end_date'] ?? '');

    if (!$name)  $errors[] = 'Client name is required.';
    if (!$email) $errors[] = 'Email address is required.';
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($errors)) {
        $clientId   = createClient(['name' => $name, 'email' => $email, 'start_date' => $startDate, 'end_date' => $endDate]);
        $newClient  = getClientById($clientId);
        $trackerUrl = SITE_URL . '/tracker/?t=' . urlencode($newClient['token']);
        $emailSent  = sendWelcomeEmail($newClient, $trackerUrl);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Client — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=4">
    <?php require_once __DIR__ . '/../includes/pwa_head.php'; ?>
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
</header>

<nav class="admin-nav">
    <a href="<?= SITE_URL ?>/admin/">Dashboard</a>
    <a href="<?= SITE_URL ?>/admin/create_client.php" class="active">+ New Client</a>
    <a href="<?= SITE_URL ?>/admin/logout.php" class="nav-divider">Log Out</a>
</nav>

<div class="container">
    <div style="margin-top:1.5rem;">

        <?php if ($newClient): ?>
        <!-- SUCCESS — show the tracker link -->
        <div class="card" style="border-top:4px solid #4caf50;">
            <div style="font-size:1.1rem;font-weight:700;color:#2e7d32;margin-bottom:0.75rem;">
                ✅ Client created successfully!
            </div>
            <?php if ($emailSent): ?>
                <div class="flash flash-success">📧 Welcome email sent to <strong><?= h($newClient['email']) ?></strong></div>
            <?php else: ?>
                <div class="flash flash-error">⚠️ Email could not be sent automatically — please copy the link below and send manually.</div>
            <?php endif; ?>
            <p style="margin-bottom:1rem;color:#555;">
                Send the link below to <strong><?= h($newClient['name']) ?></strong>.
                This is their unique tracker — it never expires.
            </p>

            <div class="client-token-box">
                <span class="token-label">Tracker Link</span>
                <span class="token-url" id="tracker-url"><?= h($trackerUrl) ?></span>
                <button type="button" class="btn btn-outline btn-sm" onclick="copyLink()">
                    Copy Link
                </button>
            </div>

            <div style="background:#f5f5f5;border-radius:6px;padding:1rem;margin:1rem 0;font-size:0.88rem;color:#555;white-space:pre-line;">Hi <?= h($newClient['name']) ?>,

Your MrsB Fitness Programme Tracker is ready! Use the link below to record your weekly progress, class passwords, and measurements throughout your 6-week programme.

Your personal tracker link:
<?= h($trackerUrl) ?>

Simply click the link each week to update your progress. You can return to it at any time.

Good luck with your programme! 💪</div>

            <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
                <button type="button" class="btn btn-primary" onclick="copyEmailText()">📋 Copy Email Text</button>
                <a href="<?= whatsAppLink($newClient['name'], $trackerUrl) ?>"
                   target="_blank"
                   class="btn btn-primary"
                   style="background:#25D366;border-color:#25D366;">
                   💬 Send via WhatsApp
                </a>
                <a href="<?= SITE_URL ?>/admin/view_client.php?id=<?= (int)$newClient['id'] ?>"
                   class="btn btn-secondary">View Client</a>
                <a href="<?= SITE_URL ?>/admin/create_client.php" class="btn btn-outline">Add Another Client</a>
            </div>
        </div>

        <?php else: ?>
        <!-- CREATE FORM -->
        <div class="page-title">Add New Client</div>

        <?php if (!empty($errors)): ?>
            <div class="flash flash-error">
                <?php foreach ($errors as $e): ?><?= h($e) ?><br><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST">
                <div class="form-group">
                    <label for="name">Client Full Name *</label>
                    <input type="text" name="name" id="name" class="form-control"
                           placeholder="e.g. Jane Smith"
                           value="<?= h($_POST['name'] ?? '') ?>" autofocus>
                </div>
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" name="email" id="email" class="form-control"
                           placeholder="e.g. jane@example.com"
                           value="<?= h($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="start_date">Programme Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="form-control"
                           value="<?= h($_POST['start_date'] ?? date('Y-m-d')) ?>"
                           onchange="autoEndDate()">
                </div>
                <div class="form-group">
                    <label for="end_date">Programme End Date</label>
                    <input type="date" name="end_date" id="end_date" class="form-control"
                           value="<?= h($_POST['end_date'] ?? date('Y-m-d', strtotime('next friday +5 weeks'))) ?>">
                </div>
                <div style="display:flex;gap:0.75rem;margin-top:1rem;flex-wrap:wrap;">
                    <button type="submit" class="btn btn-primary">Create Client &amp; Generate Link</button>
                    <a href="<?= SITE_URL ?>/admin/" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
function copyLink() {
    const url = document.getElementById('tracker-url').textContent.trim();
    navigator.clipboard.writeText(url).then(function() {
        const btn = event.target;
        const orig = btn.textContent;
        btn.textContent = '✓ Copied!';
        btn.style.background = '#4caf50';
        btn.style.color = '#fff';
        btn.style.borderColor = '#4caf50';
        setTimeout(() => { btn.textContent = orig; btn.style = ''; }, 2000);
    });
}

function copyEmailText() {
    const text = document.querySelector('.card pre, .card [style*="white-space"]').textContent.trim();
    navigator.clipboard.writeText(text).then(function() {
        const btn = event.target;
        const orig = btn.textContent;
        btn.textContent = '✓ Email Text Copied!';
        setTimeout(() => { btn.textContent = orig; }, 2500);
    });
}
</script>

<script>
function autoEndDate() {
    const start = document.getElementById('start_date').value;
    if (start) {
        const end = new Date(start);
        end.setDate(end.getDate() + 42); // 6 weeks from start
        // Move to Friday of that week (5 = Friday)
        const day = end.getDay();
        if (day !== 5) {
            const diff = (5 - day + 7) % 7;
            end.setDate(end.getDate() + diff);
        }
        document.getElementById('end_date').value = end.toISOString().split('T')[0];
    }
}
</script>
</body>
</html>
