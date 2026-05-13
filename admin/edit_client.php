<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$clientId = (int)($_GET['id'] ?? 0);
if (!$clientId) redirect(SITE_URL . '/admin/');

$client = getClientById($clientId);
if (!$client) redirect(SITE_URL . '/admin/');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name      = trim($_POST['name']       ?? '');
    $email     = trim($_POST['email']      ?? '');
    $startDate = trim($_POST['start_date'] ?? '');
    $endDate   = trim($_POST['end_date']   ?? '');

    if (!$name)  $errors[] = 'Client name is required.';
    if (!$email) $errors[] = 'Email address is required.';
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($errors)) {
        updateClient($clientId, [
            'name'       => $name,
            'email'      => $email,
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ]);
        setFlash('success', h($name) . '\'s details have been updated.');
        redirect(SITE_URL . '/admin/view_client.php?id=' . $clientId);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit <?= h($client['name']) ?> — <?= SITE_NAME ?></title>
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
</header>

<nav class="admin-nav">
    <a href="<?= SITE_URL ?>/admin/">← Dashboard</a>
    <a href="<?= SITE_URL ?>/admin/view_client.php?id=<?= $clientId ?>">← <?= h($client['name']) ?></a>
    <a href="<?= SITE_URL ?>/admin/logout.php" class="nav-divider">Log Out</a>
</nav>

<div class="container" style="max-width:640px;">
    <div style="margin-top:1.5rem;">

        <div class="page-title">Edit Client — <?= h($client['name']) ?></div>

        <?php if (!empty($errors)): ?>
            <div class="flash flash-error">
                <?php foreach ($errors as $e): ?><?= h($e) ?><br><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="POST">
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" name="name" id="name" class="form-control"
                           placeholder="e.g. Jane Smith"
                           value="<?= h($_POST['name'] ?? $client['name']) ?>" autofocus>
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" name="email" id="email" class="form-control"
                           placeholder="e.g. jane@example.com"
                           value="<?= h($_POST['email'] ?? $client['email']) ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Programme Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="form-control"
                               value="<?= h($_POST['start_date'] ?? $client['start_date']) ?>"
                               onchange="autoEndDate()">
                    </div>
                    <div class="form-group">
                        <label for="end_date">Programme End Date</label>
                        <input type="date" name="end_date" id="end_date" class="form-control"
                               value="<?= h($_POST['end_date'] ?? $client['end_date']) ?>">
                    </div>
                </div>

                <div style="display:flex;gap:0.75rem;margin-top:1rem;flex-wrap:wrap;">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="<?= SITE_URL ?>/admin/view_client.php?id=<?= $clientId ?>" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>

    </div>
</div>

<script>
function autoEndDate() {
    const start = document.getElementById('start_date').value;
    if (start) {
        const end = new Date(start);
        end.setDate(end.getDate() + 42);
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
