<?php
// ============================================================
// MrsB Tracker — Client Tracker
// Access via: /tracker/?t=TOKEN
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

// --- Validate token -----------------------------------------
$token = trim($_GET['t'] ?? '');
if (!$token) {
    die('<p style="font-family:sans-serif;padding:2rem;color:#c00;">Invalid or missing tracker link. Please check the link in your email.</p>');
}

$client = getClientByToken($token);
if (!$client) {
    die('<p style="font-family:sans-serif;padding:2rem;color:#c00;">Tracker not found. Please check the link in your email or contact your trainer.</p>');
}

$clientId = (int)$client['id'];

// --- Handle POST actions ------------------------------------
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Save a single week
    if ($action === 'save_week') {
        $weekNum = (int)($_POST['week_number'] ?? 0);
        if ($weekNum >= 1 && $weekNum <= 6) {
            saveWeek($clientId, $weekNum, [
                'entry1' => $_POST['entry1'] ?? '',
                'entry2' => $_POST['entry2'] ?? '',
                'entry3' => $_POST['entry3'] ?? '',
                'entry4' => $_POST['entry4'] ?? '',
                'notes1' => $_POST['notes1'] ?? '',
                'notes2' => $_POST['notes2'] ?? '',
            ]);
            $flash = ['type' => 'success', 'message' => 'Week ' . $weekNum . ' saved successfully!'];
        }
    }

    // Save measurements
    if ($action === 'save_measurements') {
        saveMeasurements($clientId, $_POST);
        // Also save start date if provided
        if (!empty($_POST['start_date'])) {
            saveStartDate($clientId, $_POST['start_date']);
        }
        $flash = ['type' => 'success', 'message' => 'Measurements saved successfully!'];
    }

    // Reload client data after save
    $client = getClientByToken($token);

    // Redirect to avoid re-POST on refresh
    header('Location: ' . SITE_URL . '/tracker/?t=' . urlencode($token) . '&saved=' . urlencode($flash['message']));
    exit;
}

// Show saved message from redirect
if (!empty($_GET['saved'])) {
    $flash = ['type' => 'success', 'message' => h($_GET['saved'])];
}

// --- Load week data -----------------------------------------
$weeks = getWeeksForClient($clientId);

// --- Helpers ------------------------------------------------
$totalLoss = calcWeightLoss($client['start_weight'], $client['end_weight']);

function weekData(array $weeks, int $num, string $field): string {
    return h($weeks[$num][$field] ?? '');
}

function weekSaved(array $weeks, int $num): bool {
    return isset($weeks[$num]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Programme Record — <?= h($client['name']) ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <?php require_once __DIR__ . '/../includes/pwa_head.php'; ?>
</head>
<body>

<!-- Header -->
<header class="site-header">
    <div class="logo-area">
        <?php if (file_exists(__DIR__ . '/../assets/img/logo.png')): ?>
            <img src="<?= SITE_URL ?>/assets/img/logo.png" alt="Mrs B Fitness">
        <?php else: ?>
            <div class="logo-text">Mrs <span>B</span></div>
        <?php endif; ?>
        <div>
            <div style="color:#fff;font-weight:700;font-size:1.1rem;">Programme Record</div>
            <div style="color:#d9c9d7;font-size:0.82rem;">Record your weekly progress</div>
        </div>
    </div>
    <div class="header-meta">
        <strong><?= h($client['name']) ?></strong>
        <?php if ($client['start_date']): ?>
            Started <?= date('j M Y', strtotime($client['start_date'])) ?>
        <?php endif; ?>
    </div>
</header>

<main class="container">

    <?php if ($flash): ?>
        <div class="flash flash-<?= h($flash['type']) ?>" style="margin-top:1rem;">
            <?= h($flash['message']) ?>
        </div>
    <?php endif; ?>

    <!-- Start Date Bar -->
    <form method="POST" action="<?= SITE_URL ?>/tracker/?t=<?= urlencode($token) ?>" style="margin-top:1.25rem;">
        <input type="hidden" name="action" value="save_measurements">
        <div class="start-date-bar">
            <label for="start_date">Start Date:</label>
            <input type="date"
                   name="start_date"
                   id="start_date"
                   class="form-control"
                   style="max-width:180px;"
                   value="<?= h($client['start_date']) ?>">
            <div style="margin-left:auto;font-size:0.8rem;color:#9a7a96;">
                Scroll down to save weights &amp; measurements
            </div>
        </div>

    <!-- 6 Week Grid -->
    <div class="weeks-grid">
        <?php for ($w = 1; $w <= 6; $w++): ?>
        <div class="week-card">
            <div class="week-card-header">
                <div class="week-badge">
                    <span style="font-size:0.62rem;text-transform:uppercase;letter-spacing:0.5px;">Week</span>
                    <span class="week-num"><?= $w ?></span>
                </div>
                <div class="week-title">Weekly Entries</div>
                <?php if (weekSaved($weeks, $w)): ?>
                    <div class="week-saved saved">✓ Saved</div>
                <?php else: ?>
                    <div class="week-saved">Not saved yet</div>
                <?php endif; ?>
            </div>
            <div class="week-card-body">
                <!-- This week uses its own mini form -->
                <form method="POST" action="<?= SITE_URL ?>/tracker/?t=<?= urlencode($token) ?>">
                    <input type="hidden" name="action" value="save_week">
                    <input type="hidden" name="week_number" value="<?= $w ?>">

                    <?php for ($e = 1; $e <= 4; $e++): ?>
                    <div class="week-entry-line">
                        <span class="line-num"><?= $e ?>:</span>
                        <input type="text"
                               name="entry<?= $e ?>"
                               class="form-control"
                               placeholder="Class password / entry..."
                               value="<?= weekData($weeks, $w, 'entry' . $e) ?>">
                    </div>
                    <?php endfor; ?>

                    <hr class="week-divider">

                    <div class="week-notes-line">
                        <input type="text"
                               name="notes1"
                               class="form-control"
                               placeholder="Extra notes / exercise..."
                               value="<?= weekData($weeks, $w, 'notes1') ?>">
                    </div>
                    <div class="week-notes-line">
                        <input type="text"
                               name="notes2"
                               class="form-control"
                               placeholder="Extra notes / exercise..."
                               value="<?= weekData($weeks, $w, 'notes2') ?>">
                    </div>

                    <button type="submit" class="btn btn-primary week-save-btn">
                        Save Week <?= $w ?>
                    </button>
                </form>
            </div>
        </div>
        <?php endfor; ?>
    </div>

    <!-- Measurements Section -->
    <div class="measurements-section">
        <div class="measurements-header">
            📏 Weights &amp; Measurements
        </div>
        <div class="measurements-body">

            <!-- Weight row -->
            <div class="weight-row">
                <div class="form-group">
                    <label for="start_weight">Start Weight</label>
                    <input type="text" name="start_weight" id="start_weight"
                           class="form-control" placeholder="e.g. 72kg"
                           value="<?= h($client['start_weight']) ?>">
                </div>
                <div class="form-group">
                    <label for="end_weight">End Weight</label>
                    <input type="text" name="end_weight" id="end_weight"
                           class="form-control" placeholder="e.g. 68kg"
                           value="<?= h($client['end_weight']) ?>">
                </div>
                <div class="form-group">
                    <label>Total Weight Loss</label>
                    <div class="total-loss-badge">
                        <div class="loss-label">Loss</div>
                        <div class="loss-value" id="total-loss-display"><?= h($totalLoss) ?></div>
                    </div>
                </div>
            </div>

            <!-- Body measurements grid -->
            <div class="measure-grid">

                <?php
                $measures = [
                    'chest'       => 'Chest',
                    'waist'       => 'Waist / BB',
                    'bum'         => 'Bum',
                    'arms'        => 'Arms',
                    'belly'       => 'Belly',
                    'thigh'       => 'Thigh',
                ];
                foreach ($measures as $key => $label):
                ?>
                <div class="measure-item">
                    <label><?= $label ?></label>
                    <div class="measure-pair">
                        <div>
                            <span class="sub-label">Start</span>
                            <input type="text"
                                   name="<?= $key ?>_start"
                                   class="form-control"
                                   placeholder="Start..."
                                   value="<?= h($client[$key . '_start']) ?>">
                        </div>
                        <div>
                            <span class="sub-label">End</span>
                            <input type="text"
                                   name="<?= $key ?>_end"
                                   class="form-control"
                                   placeholder="End..."
                                   value="<?= h($client[$key . '_end']) ?>">
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

            </div>

            <div style="margin-top:1.25rem;">
                <button type="submit" class="btn btn-secondary">
                    Save Measurements &amp; Weights
                </button>
            </div>
        </div>
    </div>

    </form><!-- end measurements form (wraps start date + measurements) -->

    <div style="text-align:center;padding:2rem 0 1rem;color:#9a7a96;font-size:0.82rem;">
        Mrs B Fitness Programme Tracker &mdash; Your progress is saved securely.
    </div>

</main>

<script>
// Live total weight loss calculation
(function() {
    const sw = document.getElementById('start_weight');
    const ew = document.getElementById('end_weight');
    const display = document.getElementById('total-loss-display');

    function calcLoss() {
        const s = parseFloat((sw.value || '').replace(/[^0-9.]/g, ''));
        const e = parseFloat((ew.value || '').replace(/[^0-9.]/g, ''));
        if (!s || !e) { display.textContent = '—'; return; }
        const diff = Math.round((s - e) * 10) / 10;
        const unit = /kg/i.test(sw.value) ? 'kg' : (/lb/i.test(sw.value) ? 'lbs' : '');
        if (diff > 0) display.textContent = '-' + diff + unit;
        else if (diff < 0) display.textContent = '+' + Math.abs(diff) + unit;
        else display.textContent = '0' + unit;
    }

    if (sw && ew) {
        sw.addEventListener('input', calcLoss);
        ew.addEventListener('input', calcLoss);
    }
})();
</script>

</body>
</html>
