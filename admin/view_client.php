<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';

$clientId = (int)($_GET['id'] ?? 0);
if (!$clientId) redirect(SITE_URL . '/admin/');

$client = getClientById($clientId);
if (!$client) redirect(SITE_URL . '/admin/');

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'mark_complete') {
        markClientComplete($clientId);
        setFlash('success', h($client['name']) . ' has been marked as programme complete.');
        redirect(SITE_URL . '/admin/view_client.php?id=' . $clientId);
    }

    if ($action === 'delete') {
        $name = $client['name'];
        deleteClient($clientId);
        setFlash('success', $name . ' and all their data has been deleted.');
        redirect(SITE_URL . '/admin/');
    }

    if ($action === 'reset_password') {
        resetClientPassword($clientId);
        $resetToken = generatePasswordReset($clientId);
        $resetUrl   = SITE_URL . '/tracker/reset.php?rt=' . urlencode($resetToken);
        $trackerUrl = SITE_URL . '/tracker/?t=' . urlencode($client['token']);
        $emailSent  = sendPasswordResetEmail($client, $resetUrl, $trackerUrl);
        $msg = h($client['name']) . '\'s password has been reset and a reset link has been ' . ($emailSent ? 'emailed to them.' : 'generated — email could not be sent. Reset URL: ' . $resetUrl);
        setFlash($emailSent ? 'success' : 'error', $msg);
        redirect(SITE_URL . '/admin/view_client.php?id=' . $clientId);
    }

    if ($action === 'resend_welcome') {
        $trackerUrl = SITE_URL . '/tracker/?t=' . urlencode($client['token']);
        $emailSent  = sendWelcomeEmail($client, $trackerUrl);
        $msg = $emailSent ? 'Welcome email resent to ' . h($client['email']) . ' successfully.' : 'Could not send email — please check SMTP settings.';
        setFlash($emailSent ? 'success' : 'error', $msg);
        redirect(SITE_URL . '/admin/view_client.php?id=' . $clientId);
    }
}

$weeks      = getWeeksForClient($clientId);
$trackerUrl = SITE_URL . '/tracker/?t=' . urlencode($client['token']);
$totalLoss  = calcWeightLoss($client['start_weight'], $client['end_weight']);
$flash      = getFlash();

function wd(array $weeks, int $num, string $field): string {
    return h($weeks[$num][$field] ?? '—');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($client['name']) ?> — <?= SITE_NAME ?></title>
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
    <a href="<?= SITE_URL ?>/admin/create_client.php">+ New Client</a>
    <a href="<?= SITE_URL ?>/admin/logout.php" class="nav-divider">Log Out</a>
</nav>

<div class="container-wide">
    <div style="margin-top:1.5rem;">

        <?php if ($flash): ?>
            <div class="flash flash-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
        <?php endif; ?>

        <!-- Client profile bar -->
        <div class="client-profile-bar">
            <div>
                <div class="client-name"><?= h($client['name']) ?></div>
                <div class="client-meta">
                    <?= h($client['email']) ?>
                    <?php if ($client['start_date']): ?>
                        &nbsp;&bull;&nbsp; Started <?= date('j M Y', strtotime($client['start_date'])) ?>
                    <?php endif; ?>
                    <?php if ($client['end_date']): ?>
                        &nbsp;&bull;&nbsp; Ends <?= date('j M Y', strtotime($client['end_date'])) ?>
                    <?php endif; ?>
                    &nbsp;&bull;&nbsp; Added <?= date('j M Y', strtotime($client['created_at'])) ?>
                </div>
            </div>
            <div style="display:flex;gap:0.5rem;flex-wrap:wrap;" class="admin-actions">
                <a href="<?= SITE_URL ?>/admin/edit_client.php?id=<?= $clientId ?>"
                   class="btn btn-sm" style="background:#fff3;color:#fff;border:1px solid #fff5;">
                    ✏️ Edit Details
                </a>
                <?php if ($client['status'] === 'active'): ?>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Mark this programme as complete?')">
                        <input type="hidden" name="action" value="mark_complete">
                        <button type="submit" class="btn btn-outline btn-sm" style="border-color:#fff;color:#fff;">
                            ✓ Mark Complete
                        </button>
                    </form>
                <?php else: ?>
                    <span class="status-badge status-complete" style="padding:0.4rem 0.9rem;">Programme Complete</span>
                <?php endif; ?>
                <form method="POST" style="display:inline;" onsubmit="return confirm('DELETE this client and ALL their data? This cannot be undone.')">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn btn-danger btn-sm">Delete Client</button>
                </form>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Resend welcome email with tracker link?')">
                    <input type="hidden" name="action" value="resend_welcome">
                    <button type="submit" class="btn btn-sm" style="background:#fff3;color:#fff;border:1px solid #fff5;">
                        📧 Resend Welcome
                    </button>
                </form>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Reset this client\'s password? A reset link will be emailed to them.')">
                    <input type="hidden" name="action" value="reset_password">
                    <button type="submit" class="btn btn-sm" style="background:#fff3;color:#fff;border:1px solid #fff5;">
                        🔑 Reset Password
                    </button>
                </form>
                <a href="javascript:window.print()" class="btn btn-sm" style="background:#fff3;color:#fff;border:1px solid #fff5;">
                    🖨 Print
                </a>
            </div>
        </div>

        <!-- Tracker link -->
        <div class="client-token-box">
            <span class="token-label">Client Tracker URL</span>
            <span class="token-url" id="tracker-url"><?= h($trackerUrl) ?></span>
            <button type="button" class="btn btn-outline btn-sm" onclick="copyLink()">Copy Link</button>
            <a href="<?= h($trackerUrl) ?>" target="_blank" class="btn btn-secondary btn-sm">Open Tracker</a>
        </div>

        <!-- Progress pips -->
        <div class="card" style="padding:1rem 1.25rem;">
            <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
                <div style="font-weight:700;color:#4a1942;font-size:0.88rem;text-transform:uppercase;letter-spacing:0.3px;">
                    Weekly Progress
                </div>
                <div class="progress-pips" style="gap:6px;">
                    <?php for ($i = 1; $i <= 6; $i++): ?>
                        <div style="display:flex;flex-direction:column;align-items:center;gap:2px;">
                            <div class="pip <?= isset($weeks[$i]) ? 'done' : '' ?>"
                                 style="width:22px;height:22px;"
                                 title="Week <?= $i ?>: <?= isset($weeks[$i]) ? 'Saved ' . date('j M', strtotime($weeks[$i]['updated_at'])) : 'Not saved' ?>">
                            </div>
                            <div style="font-size:0.68rem;color:#9a7a96;">W<?= $i ?></div>
                        </div>
                    <?php endfor; ?>
                </div>
                <div style="margin-left:auto;font-size:0.85rem;color:#9a7a96;">
                    <?= count($weeks) ?> / 6 weeks saved
                </div>
            </div>
        </div>

        <!-- Weights & Measurements -->
        <div class="card">
            <div style="font-weight:700;color:#4a1942;margin-bottom:1rem;font-size:0.95rem;text-transform:uppercase;letter-spacing:0.3px;">
                Weights &amp; Measurements
            </div>
            <div class="weight-row" style="margin-bottom:1rem;">
                <div>
                    <div style="font-size:0.78rem;color:#9a7a96;text-transform:uppercase;letter-spacing:0.3px;margin-bottom:0.25rem;">Start Weight</div>
                    <div style="font-size:1.2rem;font-weight:700;color:#4a1942;"><?= h($client['start_weight'] ?: '—') ?></div>
                </div>
                <div>
                    <div style="font-size:0.78rem;color:#9a7a96;text-transform:uppercase;letter-spacing:0.3px;margin-bottom:0.25rem;">End Weight</div>
                    <div style="font-size:1.2rem;font-weight:700;color:#4a1942;"><?= h($client['end_weight'] ?: '—') ?></div>
                </div>
                <div>
                    <div style="font-size:0.78rem;color:#9a7a96;text-transform:uppercase;letter-spacing:0.3px;margin-bottom:0.25rem;">Total Loss</div>
                    <div style="font-size:1.2rem;font-weight:700;color:#d4006e;"><?= h($totalLoss) ?></div>
                </div>
            </div>

            <?php
            $measures = [
                'chest' => 'Chest', 'waist' => 'Waist/BB', 'bum' => 'Bum',
                'arms'  => 'Arms',  'belly' => 'Belly',    'thigh' => 'Thigh',
            ];
            ?>
            <div class="measure-grid">
                <?php foreach ($measures as $key => $label): ?>
                <div>
                    <div style="font-size:0.78rem;color:#6d3b67;font-weight:600;text-transform:uppercase;letter-spacing:0.3px;margin-bottom:0.3rem;"><?= $label ?></div>
                    <div style="display:flex;gap:0.75rem;font-size:0.92rem;">
                        <span>Start: <strong><?= h($client[$key . '_start'] ?: '—') ?></strong></span>
                        <span>End: <strong><?= h($client[$key . '_end'] ?: '—') ?></strong></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Weekly Entries -->
        <div class="weeks-grid">
            <?php for ($w = 1; $w <= 6; $w++): ?>
            <div class="week-card">
                <div class="week-card-header">
                    <div class="week-badge">
                        <span style="font-size:0.62rem;text-transform:uppercase;letter-spacing:0.5px;">Week</span>
                        <span class="week-num"><?= $w ?></span>
                    </div>
                    <div class="week-title">Week <?= $w ?></div>
                    <?php if (isset($weeks[$w])): ?>
                        <div class="week-saved saved">✓ Saved <?= date('j M', strtotime($weeks[$w]['updated_at'])) ?></div>
                    <?php else: ?>
                        <div class="week-saved">Not filled in</div>
                    <?php endif; ?>
                </div>
                <div class="week-card-body">
                    <?php if (isset($weeks[$w])): ?>
                        <?php for ($e = 1; $e <= 4; $e++): ?>
                            <?php $val = $weeks[$w]['entry' . $e] ?? ''; ?>
                            <div class="week-entry-line">
                                <span class="line-num"><?= $e ?>:</span>
                                <span style="font-size:0.92rem;color:<?= $val ? '#2c2c2c' : '#bbb' ?>;">
                                    <?= $val ? h($val) : '—' ?>
                                </span>
                            </div>
                        <?php endfor; ?>
                        <?php if ($weeks[$w]['notes1'] || $weeks[$w]['notes2']): ?>
                            <hr class="week-divider">
                            <?php if ($weeks[$w]['notes1']): ?>
                                <div style="font-size:0.88rem;color:#555;margin-bottom:0.3rem;"><?= h($weeks[$w]['notes1']) ?></div>
                            <?php endif; ?>
                            <?php if ($weeks[$w]['notes2']): ?>
                                <div style="font-size:0.88rem;color:#555;"><?= h($weeks[$w]['notes2']) ?></div>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <div style="color:#bbb;font-size:0.88rem;padding:0.5rem 0;">Client has not filled in this week yet.</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endfor; ?>
        </div>

        <div style="margin-top:1.5rem;padding-bottom:2rem;">
            <a href="<?= SITE_URL ?>/admin/" class="btn btn-outline">← Back to Dashboard</a>
        </div>

    </div>
</div>

<script>
function copyLink() {
    const url = document.getElementById('tracker-url').textContent.trim();
    navigator.clipboard.writeText(url).then(function() {
        const btn = event.target;
        const orig = btn.textContent;
        btn.textContent = '✓ Copied!';
        setTimeout(() => { btn.textContent = orig; }, 2000);
    });
}
</script>

</body>
</html>
