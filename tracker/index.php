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

// --- Auth check ---------------------------------------------
if (!$client['password_set']) {
    header('Location: ' . SITE_URL . '/tracker/setup.php?t=' . urlencode($token));
    exit;
}

if (!clientIsAuthenticated($clientId)) {
    header('Location: ' . SITE_URL . '/tracker/login.php?t=' . urlencode($token));
    exit;
}

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
</head>
<body>

<!-- Header -->
<header class="site-header">
    <div class="logo-area">
        <?php if (file_exists(__DIR__ . '/../assets/img/logo.png')): ?>
            <img src="<?= SITE_URL ?>/assets/img/logo.png" alt="MrsB Fitness">
        <?php else: ?>
            <div class="logo-text">Mrs<span>B</span></div>
        <?php endif; ?>
        <div>
            <div style="color:#fff;font-weight:700;font-size:1.1rem;">Programme Record</div>
            <div style="color:#d9c9d7;font-size:0.82rem;">Record your weekly progress</div>
        </div>
    </div>
    <div class="header-meta" style="display:flex;align-items:center;gap:1rem;">
        <div>
            <strong><?= h($client['name']) ?></strong>
            <?php if ($client['start_date']): ?>
                <span style="font-size:0.82rem;color:#d9c9d7;"> &bull; Started <?= date('j M Y', strtotime($client['start_date'])) ?></span>
            <?php endif; ?>
            <?php if ($client['end_date']): ?>
                <span style="font-size:0.82rem;color:#d9c9d7;"> &bull; Ends <?= date('j M Y', strtotime($client['end_date'])) ?></span>
            <?php endif; ?>
        </div>
        <a href="<?= SITE_URL ?>/tracker/logout.php?t=<?= urlencode($token) ?>"
           style="color:#d9c9d7;font-size:0.82rem;border:1px solid #d9c9d750;padding:0.25rem 0.75rem;border-radius:6px;">
            Log Out
        </a>
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
                        Save Week <?= $w ?> Passwords
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
        MrsB Fitness Programme Tracker &mdash; Your progress is saved securely.
    </div>

</main>

<!-- Unsaved changes modal -->
<div id="unsaved-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(76,16,68,0.45);align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:14px;padding:2rem 1.75rem;max-width:400px;width:100%;box-shadow:0 8px 40px rgba(76,16,68,0.25);text-align:center;">
        <div style="font-size:2rem;margin-bottom:0.5rem;">⚠️</div>
        <h3 style="color:#4c1044;margin:0 0 0.75rem;font-size:1.05rem;">Unsaved entries</h3>
        <p id="unsaved-modal-msg" style="color:#6d3b67;font-size:0.9rem;line-height:1.6;margin:0 0 1.5rem;"></p>
        <div style="display:flex;gap:0.75rem;flex-direction:column;">
            <button id="unsaved-stay" class="btn btn-primary" style="width:100%;font-size:1rem;">
                ← Stay &amp; Save
            </button>
            <button id="unsaved-leave" style="width:100%;background:none;border:none;color:#9a7a96;font-size:0.85rem;cursor:pointer;padding:0.4rem;">
                Leave without saving
            </button>
        </div>
    </div>
</div>

<script>
// ── Unsaved changes guard ────────────────────────────────────────
(function() {
    // Tracks which forms are dirty: 'week_1'..'week_6' or 'measurements'
    const dirtyForms = new Set();
    let submitting = false;

    document.addEventListener('input', function(e) {
        if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') return;
        const form = e.target.closest('form');
        if (!form) return;
        const weekInput = form.querySelector('input[name="week_number"]');
        dirtyForms.add(weekInput ? 'week_' + weekInput.value : 'measurements');
    });

    document.addEventListener('submit', function() {
        submitting = true;
        dirtyForms.clear();
    });

    function buildMessage() {
        const weeks = [];
        dirtyForms.forEach(function(key) {
            if (key.startsWith('week_')) weeks.push(parseInt(key.replace('week_', '')));
        });
        weeks.sort(function(a, b) { return a - b; });

        let msg = '';
        if (weeks.length === 1) {
            msg += 'You have unsaved Class Password entries for Week ' + weeks[0] + '. '
                 + 'Please click the Save Week ' + weeks[0] + ' Passwords button.';
        } else if (weeks.length > 1) {
            const list = weeks.map(function(w) { return 'Week ' + w; }).join(', ');
            const btnList = weeks.map(function(w) { return 'Save Week ' + w + ' Passwords'; }).join(' and ');
            msg += 'You have unsaved Class Password entries for ' + list + '. '
                 + 'Please click the ' + btnList + ' buttons.';
        }
        if (dirtyForms.has('measurements')) {
            if (msg) msg += '\n\n';
            msg += 'You also have unsaved Measurements — click "Save Measurements & Weights" to save.';
        }
        return msg;
    }

    // Custom modal logic
    const modal    = document.getElementById('unsaved-modal');
    const modalMsg = document.getElementById('unsaved-modal-msg');
    const stayBtn  = document.getElementById('unsaved-stay');
    const leaveBtn = document.getElementById('unsaved-leave');
    let pendingHref = null;

    function showModal(msg, href) {
        pendingHref = href;
        modalMsg.textContent = msg;
        modal.style.display = 'flex';
    }

    stayBtn.addEventListener('click', function() {
        modal.style.display = 'none';
        pendingHref = null;
    });

    leaveBtn.addEventListener('click', function() {
        modal.style.display = 'none';
        dirtyForms.clear();
        if (pendingHref) window.location.href = pendingHref;
    });

    // Close on backdrop click
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
            pendingHref = null;
        }
    });

    window.addEventListener('beforeunload', function(e) {
        if (dirtyForms.size > 0 && !submitting) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    document.addEventListener('click', function(e) {
        const link = e.target.closest('a[href]');
        if (link && dirtyForms.size > 0 && !submitting) {
            e.preventDefault();
            showModal(buildMessage(), link.href);
        }
    });
})();

// ── Live total weight loss calculation ───────────────────────────
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
