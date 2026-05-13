<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$clients   = getAllClients();
$total     = count($clients);
$active    = count(array_filter($clients, fn($c) => $c['status'] === 'active'));
$complete  = $total - $active;
$flash     = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=3">
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
    <div style="color:#d9c9d7;font-size:0.85rem;">
        Logged in as <strong style="color:#fff;"><?= h($_SESSION['admin_username']) ?></strong>
    </div>
</header>

<nav class="admin-nav">
    <a href="<?= SITE_URL ?>/admin/" class="active">Dashboard</a>
    <a href="<?= SITE_URL ?>/admin/create_client.php">+ New Client</a>
    <a href="<?= SITE_URL ?>/admin/change-password.php">Change Password</a>
    <a href="<?= SITE_URL ?>/admin/logout.php" class="nav-divider">Log Out</a>
</nav>

<div class="container-wide">

    <?php if ($flash): ?>
        <div class="flash flash-<?= h($flash['type']) ?>" style="margin-top:1.25rem;">
            <?= h($flash['message']) ?>
        </div>
    <?php endif; ?>

    <div style="margin-top:1.5rem;">

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-num"><?= $total ?></div>
                <div class="stat-label">Total Clients</div>
            </div>
            <div class="stat-card">
                <div class="stat-num"><?= $active ?></div>
                <div class="stat-label">Active</div>
            </div>
            <div class="stat-card">
                <div class="stat-num"><?= $complete ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>

        <!-- Client Table -->
        <div class="card" style="padding:0;overflow:hidden;">
            <div style="padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #f0ecef;">
                <div class="page-title" style="margin:0;border:none;">All Clients</div>
                <a href="<?= SITE_URL ?>/admin/create_client.php" class="btn btn-primary btn-sm">+ New Client</a>
            </div>

            <?php if (empty($clients)): ?>
                <div style="padding:3rem;text-align:center;color:#9a7a96;">
                    <div style="font-size:2rem;margin-bottom:0.5rem;">👥</div>
                    <p>No clients yet. <a href="<?= SITE_URL ?>/admin/create_client.php">Add your first client</a></p>
                </div>
            <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Email</th>
                            <th>Start Date</th>
                            <th>Progress</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($clients as $c): ?>
                        <tr>
                            <td><strong><?= h($c['name']) ?></strong></td>
                            <td><?= h($c['email']) ?></td>
                            <td><?= $c['start_date'] ? date('j M Y', strtotime($c['start_date'])) : '<span style="color:#bbb;">Not set</span>' ?></td>
                            <td>
                                <div class="progress-pips">
                                    <?php for ($i = 1; $i <= 6; $i++): ?>
                                        <div class="pip <?= $i <= (int)$c['weeks_saved'] ? 'done' : '' ?>" title="Week <?= $i ?>"></div>
                                    <?php endfor; ?>
                                </div>
                                <div style="font-size:0.75rem;color:#9a7a96;margin-top:3px;"><?= (int)$c['weeks_saved'] ?>/6 weeks</div>
                            </td>
                            <td>
                                <span class="status-badge status-<?= h($c['status']) ?>">
                                    <?= ucfirst(h($c['status'])) ?>
                                </span>
                            </td>
                            <td style="display:flex;gap:0.4rem;flex-wrap:wrap;">
                                <a href="<?= SITE_URL ?>/admin/view_client.php?id=<?= (int)$c['id'] ?>"
                                   class="btn btn-secondary btn-sm">View</a>
                                <a href="<?= SITE_URL ?>/admin/edit_client.php?id=<?= (int)$c['id'] ?>"
                                   class="btn btn-outline btn-sm">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

</body>
</html>
