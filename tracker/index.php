<?php
// ============================================================
// MrsB Tracker — Client Tracker
// Access via: /tracker/
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

// --- Auth check ---------------------------------------------
if (!isClientLoggedIn()) {
    header('Location: ' . SITE_URL . '/tracker/login.php');
    exit;
}

$clientId = (int)$_SESSION['client_id'];
$client   = getClientById($clientId);

if (!$client) {
    session_unset();
    session_destroy();
    header('Location: ' . SITE_URL . '/tracker/login.php');
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

    // Save measurements (start date is set by admin only, not editable here)
    if ($action === 'save_measurements') {
        saveMeasurements($clientId, $_POST);
        $flash = ['type' => 'success', 'message' => 'Measurements saved successfully!'];
    }

    // Reload client data after save
    $client = getClientById($clientId);

    // Redirect to avoid re-POST on refresh
    header('Location: ' . SITE_URL . '/tracker/?saved=' . urlencode($flash['message']));
    exit;
}

// Show saved message from redirect
if (!empty($_GET['saved'])) {
    $flash = ['type' => 'success', 'message' => h($_GET['saved'])];
}

// --- Load week data -----------------------------------------
$weeks = getWeeksForClient($clientId);

// --- Helpers ------------------------------------------------
$totalLoss       = calcWeightLoss($client['start_weight'], $client['end_weight']);
$weightChangeType = weightChangeType($client['start_weight'], $client['end_weight']);

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
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=4">
    <?php require_once __DIR__ . '/../includes/pwa_head.php'; ?>
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
        <a href="<?= SITE_URL ?>/tracker/logout.php"
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

    <!-- Programme Dates Bar -->
    <div class="start-date-bar" style="margin-top:1.25rem;flex-wrap:wrap;gap:0.75rem;">
        <?php if ($client['start_date']): ?>
        <div style="display:flex;align-items:center;gap:0.5rem;">
            <span style="font-size:0.78rem;font-weight:700;color:#6d2e60;text-transform:uppercase;letter-spacing:0.3px;">Start</span>
            <span style="font-size:0.95rem;font-weight:600;color:#4c1044;"><?= date('j M Y', strtotime($client['start_date'])) ?></span>
        </div>
        <?php endif; ?>
        <?php if ($client['start_date'] && $client['end_date']): ?>
            <span style="color:#c8b4c4;font-size:0.9rem;">→</span>
        <?php endif; ?>
        <?php if ($client['end_date']): ?>
        <div style="display:flex;align-items:center;gap:0.5rem;">
            <span style="font-size:0.78rem;font-weight:700;color:#6d2e60;text-transform:uppercase;letter-spacing:0.3px;">End</span>
            <span style="font-size:0.95rem;font-weight:600;color:#4c1044;"><?= date('j M Y', strtotime($client['end_date'])) ?></span>
        </div>
        <?php endif; ?>
        <?php if (!$client['start_date'] && !$client['end_date']): ?>
            <span style="font-size:0.85rem;color:#9a7a96;">Programme dates not yet set</span>
        <?php endif; ?>
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
                <form method="POST" action="<?= SITE_URL ?>/tracker/">
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

    <!-- Measurements Section (own form — no nesting issue) -->
    <form method="POST" action="<?= SITE_URL ?>/tracker/" id="measurements-form">
        <input type="hidden" name="action" value="save_measurements">
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
                    <label>Total Weight Change</label>
                    <div class="total-loss-badge<?= $weightChangeType === 'gain' ? ' total-gain-badge' : '' ?>" id="total-loss-badge">
                        <div class="loss-label" id="total-loss-label"><?= $weightChangeType === 'gain' ? 'Gained' : 'Loss' ?></div>
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

    </div>
    </form><!-- end measurements form -->

    <div style="text-align:center;padding:2rem 0 1rem;color:#9a7a96;font-size:0.82rem;">
        MrsB Fitness Programme Tracker &mdash; Your progress is saved securely.
    </div>

</main>

<?php
// --- WhatsApp contact button (floating) ---------------------
if (defined('MRSB_WHATSAPP_NUMBER') && MRSB_WHATSAPP_NUMBER !== '') {
    $waFirstName = trim(explode(' ', $client['name'] ?? '')[0]);
    $waMessage   = 'Hi Mrs B, it\'s ' . $waFirstName . ' — ';
    $waUrl       = 'https://wa.me/' . rawurlencode(MRSB_WHATSAPP_NUMBER)
                 . '?text=' . rawurlencode($waMessage);
?>
<a href="<?= h($waUrl) ?>"
   class="whatsapp-fab"
   target="_blank"
   rel="noopener noreferrer"
   aria-label="Message Mrs B on WhatsApp">
    <span class="whatsapp-fab-label">Message Mrs B</span>
    <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
        <path d="M16.003 3C9.374 3 4 8.373 4 15.001c0 2.385.717 4.6 1.946 6.45L4 28l6.74-1.768A11.94 11.94 0 0 0 16 27c6.629 0 12-5.373 12-12S22.632 3 16.003 3zm0 21.84a9.83 9.83 0 0 1-5.005-1.367l-.359-.214-4.001 1.049 1.07-3.9-.234-.4a9.836 9.836 0 0 1-1.509-5.236c0-5.444 4.43-9.873 9.876-9.873 2.638 0 5.118 1.029 6.984 2.895a9.81 9.81 0 0 1 2.892 6.984c-.001 5.444-4.43 9.062-9.714 9.062zm5.418-7.385c-.297-.149-1.757-.867-2.029-.967-.272-.099-.471-.149-.669.149-.198.297-.767.967-.94 1.165-.173.198-.347.223-.644.074-.297-.149-1.254-.462-2.388-1.473-.882-.787-1.477-1.76-1.65-2.057-.173-.297-.019-.458.13-.606.134-.133.297-.347.446-.521.149-.173.198-.297.297-.495.099-.198.05-.371-.025-.521-.075-.149-.669-1.611-.916-2.207-.241-.578-.486-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.521.074-.793.371-.272.297-1.04 1.016-1.04 2.478 0 1.461 1.065 2.875 1.214 3.073.149.198 2.097 3.204 5.083 4.493.71.306 1.265.488 1.697.625.713.227 1.362.195 1.875.118.572-.085 1.757-.717 2.005-1.411.247-.694.247-1.288.173-1.412-.074-.124-.272-.198-.569-.347z"/>
    </svg>
</a>
<?php } ?>

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
    const dirtyForms = new Set();
    let leaveCallback = null;

    // Track which form went dirty
    document.addEventListener('input', function(e) {
        if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') return;
        const form = e.target.closest('form');
        if (!form) return;
        const weekInput = form.querySelector('input[name="week_number"]');
        dirtyForms.add(weekInput ? 'week_' + weekInput.value : 'measurements');
    });

    // Only clear the form that was actually submitted
    document.addEventListener('submit', function(e) {
        const form = e.target;
        const weekInput = form.querySelector('input[name="week_number"]');
        const key = weekInput ? 'week_' + weekInput.value : 'measurements';
        dirtyForms.delete(key);
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
            const list    = weeks.map(function(w) { return 'Week ' + w; }).join(', ');
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

    // Modal elements
    const modal    = document.getElementById('unsaved-modal');
    const modalMsg = document.getElementById('unsaved-modal-msg');
    const stayBtn  = document.getElementById('unsaved-stay');
    const leaveBtn = document.getElementById('unsaved-leave');

    function showModal(msg, onLeave) {
        leaveCallback = onLeave;
        modalMsg.textContent = msg;
        modal.style.display = 'flex';
    }

    function hideModal() {
        modal.style.display = 'none';
        leaveCallback = null;
    }

    stayBtn.addEventListener('click', hideModal);

    leaveBtn.addEventListener('click', function() {
        const cb = leaveCallback;
        dirtyForms.clear();
        hideModal();
        if (cb) cb();
    });

    modal.addEventListener('click', function(e) {
        if (e.target === modal) hideModal();
    });

    // ── Intercept all link clicks ──────────────────────────────
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a[href]');
        if (!link || dirtyForms.size === 0) return;
        const href = link.getAttribute('href');
        if (!href || href === '#' || href.startsWith('javascript:')) return;
        // Links that open in a new tab/window don't lose current form state,
        // so don't warn the user — the data is safe in this tab.
        if (link.target === '_blank') return;
        e.preventDefault();
        showModal(buildMessage(), function() { window.location.href = link.href; });
    });

    // ── Intercept browser back button ──────────────────────────
    history.pushState(null, '', window.location.href);
    window.addEventListener('popstate', function() {
        if (dirtyForms.size > 0) {
            history.pushState(null, '', window.location.href); // push back so we stay
            showModal(buildMessage(), function() {
                dirtyForms.clear();
                history.back();
            });
        }
    });

    // ── Native dialog for tab close / refresh (unavoidable) ───
    window.addEventListener('beforeunload', function(e) {
        if (dirtyForms.size > 0) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

})();

// ── Live total weight loss calculation ───────────────────────────
(function() {
    const sw    = document.getElementById('start_weight');
    const ew    = document.getElementById('end_weight');
    const display = document.getElementById('total-loss-display');
    const label   = document.getElementById('total-loss-label');
    const badge   = document.getElementById('total-loss-badge');

    function calcLoss() {
        const s = parseFloat((sw.value || '').replace(/[^0-9.]/g, ''));
        const e = parseFloat((ew.value || '').replace(/[^0-9.]/g, ''));
        if (!s || !e) {
            display.textContent = '—';
            label.textContent   = 'Loss';
            badge.classList.remove('total-gain-badge');
            return;
        }
        const diff = Math.round((s - e) * 10) / 10;
        const unit = /kg/i.test(sw.value) ? 'kg' : (/lb/i.test(sw.value) ? 'lbs' : '');
        if (diff > 0) {
            display.textContent = '-' + diff + unit;
            label.textContent   = 'Loss';
            badge.classList.remove('total-gain-badge');
        } else if (diff < 0) {
            display.textContent = '+' + Math.abs(diff) + unit;
            label.textContent   = 'Gained';
            badge.classList.add('total-gain-badge');
        } else {
            display.textContent = '0' + unit;
            label.textContent   = 'No Change';
            badge.classList.remove('total-gain-badge');
        }
    }

    if (sw && ew) {
        sw.addEventListener('input', calcLoss);
        ew.addEventListener('input', calcLoss);
    }
})();
</script>

</body>
</html>
