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
 * Update client details
 */
function updateClient(int $clientId, array $data): bool {
    $db   = getDB();
    $stmt = $db->prepare('
        UPDATE clients
        SET name       = :name,
            email      = :email,
            start_date = :start_date,
            end_date   = :end_date
        WHERE id = :id
    ');
    return $stmt->execute([
        ':name'       => trim($data['name']),
        ':email'      => trim($data['email']),
        ':start_date' => $data['start_date'] ?: null,
        ':end_date'   => $data['end_date']   ?: null,
        ':id'         => $clientId,
    ]);
}

/**
 * Create a new client and return their ID
 */
function createClient(array $data): int {
    $db = getDB();
    $token = generateToken();
    $stmt = $db->prepare('
        INSERT INTO clients (name, email, token, start_date, end_date)
        VALUES (:name, :email, :token, :start_date, :end_date)
    ');
    $stmt->execute([
        ':name'       => trim($data['name']),
        ':email'      => trim($data['email']),
        ':token'      => $token,
        ':start_date' => $data['start_date'] ?: null,
        ':end_date'   => $data['end_date'] ?: null,
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
 * Generate a WhatsApp Web share link
 */
function whatsAppLink(string $name, string $trackerUrl): string {
    $message = "Hi {$name}! 👋\n\n"
             . "Your MrsB Fitness Programme Tracker is ready! 💪\n\n"
             . "Use the link below each week to record your progress, class passwords, and measurements throughout your 6-week programme.\n\n"
             . "👉 Your personal tracker link:\n"
             . $trackerUrl . "\n\n"
             . "On your first visit you'll be asked to set a password to keep your data secure.\n\n"
             . "Good luck with your programme — I'm rooting for you! 🌟";

    return 'https://wa.me/?text=' . rawurlencode($message);
}

/**
 * Redirect helper
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Set client password
 */
function setClientPassword(int $clientId, string $password): bool {
    $db   = getDB();
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare('UPDATE clients SET client_password_hash = ?, password_set = 1 WHERE id = ?');
    return $stmt->execute([$hash, $clientId]);
}

/**
 * Verify client password
 */
function verifyClientPassword(array $client, string $password): bool {
    if (empty($client['client_password_hash'])) return false;
    return password_verify($password, $client['client_password_hash']);
}

/**
 * Reset client password (admin) — clears password so token-based reset takes over
 */
function resetClientPassword(int $clientId): bool {
    $db   = getDB();
    $stmt = $db->prepare('UPDATE clients SET client_password_hash = NULL, password_set = 0 WHERE id = ?');
    return $stmt->execute([$clientId]);
}

/**
 * Generate a password reset token for a client (24hr expiry)
 */
function generatePasswordReset(int $clientId): string {
    $db      = getDB();
    $token   = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
    $stmt    = $db->prepare('UPDATE clients SET reset_token = ?, reset_expires = ? WHERE id = ?');
    $stmt->execute([$token, $expires, $clientId]);
    return $token;
}

/**
 * Get client by a valid (non-expired) reset token
 */
function getClientByResetToken(string $token): ?array {
    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM clients WHERE reset_token = ? AND reset_expires > NOW() LIMIT 1');
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Clear the reset token after use
 */
function clearPasswordReset(int $clientId): bool {
    $db   = getDB();
    $stmt = $db->prepare('UPDATE clients SET reset_token = NULL, reset_expires = NULL WHERE id = ?');
    return $stmt->execute([$clientId]);
}

/**
 * Find a client by email address
 */
function getClientByEmail(string $email): ?array {
    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM clients WHERE email = ? LIMIT 1');
    $stmt->execute([trim($email)]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Find an admin by email address
 */
function getAdminByEmail(string $email): ?array {
    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM admin_users WHERE email = ? LIMIT 1');
    $stmt->execute([trim($email)]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Generate a password reset token for an admin (24hr expiry)
 */
function generateAdminPasswordReset(int $adminId): string {
    $db      = getDB();
    $token   = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
    $stmt    = $db->prepare('UPDATE admin_users SET reset_token = ?, reset_expires = ? WHERE id = ?');
    $stmt->execute([$token, $expires, $adminId]);
    return $token;
}

/**
 * Get admin by a valid (non-expired) reset token
 */
function getAdminByResetToken(string $token): ?array {
    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM admin_users WHERE reset_token = ? AND reset_expires > NOW() LIMIT 1');
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Get admin by username (for login)
 */
function getAdminByUsername(string $username): ?array {
    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM admin_users WHERE username = ? LIMIT 1');
    $stmt->execute([trim($username)]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Clear admin reset token after use
 */
function clearAdminPasswordReset(int $adminId): bool {
    $db   = getDB();
    $stmt = $db->prepare('UPDATE admin_users SET reset_token = NULL, reset_expires = NULL WHERE id = ?');
    return $stmt->execute([$adminId]);
}

/**
 * Set new admin password
 */
function setAdminPassword(int $adminId, string $password): bool {
    $db   = getDB();
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?');
    return $stmt->execute([$hash, $adminId]);
}

/**
 * Check if client is authenticated in session
 */
function clientIsAuthenticated(int $clientId): bool {
    return !empty($_SESSION['client_logged_in']) && (int)$_SESSION['client_id'] === $clientId;
}

/**
 * Check if any client is logged in (no ID needed)
 */
function isClientLoggedIn(): bool {
    return !empty($_SESSION['client_logged_in']) && !empty($_SESSION['client_id']);
}
