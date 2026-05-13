<?php
// ============================================================
// MrsB Tracker — Helper Functions
// ============================================================

/**
 * Generate a cryptographically secure 32-char hex token
 */
function generateToken(): string {
    return bin2hex(random_bytes(16));
}

/**
 * Sanitise output for HTML display
 */
function h(mixed $value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Get client by token. Returns array or null.
 */
function getClientByToken(string $token): ?array {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM clients WHERE token = ? LIMIT 1');
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Get all weeks for a client, keyed by week_number
 */
function getWeeksForClient(int $clientId): array {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM weeks WHERE client_id = ? ORDER BY week_number ASC');
    $stmt->execute([$clientId]);
    $rows = $stmt->fetchAll();
    $keyed = [];
    foreach ($rows as $row) {
        $keyed[(int)$row['week_number']] = $row;
    }
    return $keyed;
}

/**
 * Upsert a week record (insert or update)
 */
function saveWeek(int $clientId, int $weekNumber, array $data): bool {
    $db = getDB();
    $sql = '
        INSERT INTO weeks (client_id, week_number, entry1, entry2, entry3, entry4, notes1, notes2)
        VALUES (:client_id, :week_number, :entry1, :entry2, :entry3, :entry4, :notes1, :notes2)
        ON DUPLICATE KEY UPDATE
            entry1 = VALUES(entry1),
            entry2 = VALUES(entry2),
            entry3 = VALUES(entry3),
            entry4 = VALUES(entry4),
            notes1 = VALUES(notes1),
            notes2 = VALUES(notes2),
            updated_at = CURRENT_TIMESTAMP
    ';
    $stmt = $db->prepare($sql);
    return $stmt->execute([
        ':client_id'   => $clientId,
        ':week_number' => $weekNumber,
        ':entry1'      => trim($data['entry1'] ?? ''),
        ':entry2'      => trim($data['entry2'] ?? ''),
        ':entry3'      => trim($data['entry3'] ?? ''),
        ':entry4'      => trim($data['entry4'] ?? ''),
        ':notes1'      => trim($data['notes1'] ?? ''),
        ':notes2'      => trim($data['notes2'] ?? ''),
    ]);
}

/**
 * Save measurements + weights for a client
 */
function saveMeasurements(int $clientId, array $data): bool {
    $db = getDB();
    $fields = [
        'start_weight','end_weight',
        'chest_start','chest_end',
        'waist_start','waist_end',
        'bum_start','bum_end',
        'arms_start','arms_end',
        'belly_start','belly_end',
        'thigh_start','thigh_end',
    ];

    $sets = [];
    $params = [];
    foreach ($fields as $f) {
        if (isset($data[$f])) {
            $sets[] = "`$f` = ?";
            $params[] = trim($data[$f]);
        }
    }

    if (empty($sets)) return false;

    $params[] = $clientId;
    $sql = 'UPDATE clients SET ' . implode(', ', $sets) . ' WHERE id = ?';
    $stmt = $db->prepare($sql);
    return $stmt->execute($params);
}

/**
 * Save start date for a client
 */
function saveStartDate(int $clientId, string $startDate): bool {
    $db = getDB();
    $stmt = $db->prepare('UPDATE clients SET start_date = ? WHERE id = ?');
    return $stmt->execute([$startDate ?: null, $clientId]);
}

/**
 * Get all clients (for admin dashboard)
 */
function getAllClients(): array {
    $db = getDB();
    $stmt = $db->query('
        SELECT c.*,
               COUNT(w.id) AS weeks_saved
        FROM clients c
        LEFT JOIN weeks w ON w.client_id = c.id
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ');
    return $stmt->fetchAll();
}

/**
 * Get a single client by ID
 */
function getClientById(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM clients WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Create a new client and return their ID
 */
function createClient(array $data): int {
    $db = getDB();
    $token = generateToken();
    $stmt = $db->prepare('
        INSERT INTO clients (name, email, token, start_date)
        VALUES (:name, :email, :token, :start_date)
    ');
    $stmt->execute([
        ':name'       => trim($data['name']),
        ':email'      => trim($data['email']),
        ':token'      => $token,
        ':start_date' => $data['start_date'] ?: null,
    ]);
    return (int)$db->lastInsertId();
}

/**
 * Mark a client programme as complete
 */
function markClientComplete(int $clientId): bool {
    $db = getDB();
    $stmt = $db->prepare("UPDATE clients SET status = 'complete' WHERE id = ?");
    return $stmt->execute([$clientId]);
}

/**
 * Delete a client and all their data
 */
function deleteClient(int $clientId): bool {
    $db = getDB();
    $stmt = $db->prepare('DELETE FROM clients WHERE id = ?');
    return $stmt->execute([$clientId]);
}

/**
 * Calculate total weight loss from start/end (simple string subtraction attempt)
 */
function calcWeightLoss(?string $start, ?string $end): string {
    if (!$start || !$end) return '—';
    $s = (float) preg_replace('/[^0-9.]/', '', $start);
    $e = (float) preg_replace('/[^0-9.]/', '', $end);
    if ($s <= 0) return '—';
    $diff = round($s - $e, 1);
    $unit = preg_match('/kg/i', $start) ? 'kg' : (preg_match('/lb/i', $start) ? 'lbs' : '');
    if ($diff > 0) return '-' . $diff . $unit;
    if ($diff < 0) return '+' . abs($diff) . $unit;
    return '0' . $unit;
}

/**
 * Flash message helper — set
 */
function setFlash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Flash message helper — get and clear
 */
function getFlash(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Redirect helper
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}
